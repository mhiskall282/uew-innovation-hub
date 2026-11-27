<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Services\GalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('gallery');
    }

    /** A real JPEG made with GD, with an EXIF block containing a fake GPS marker. */
    private function jpeg(string $name = 'photo.jpg', int $w = 1200, int $h = 900): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, (int) imagecolorallocate($img, 37, 38, 122));
        ob_start();
        imagejpeg($img, null, 90);
        $jpeg = (string) ob_get_clean();

        // Insert an APP1 "Exif" segment after SOI carrying a recognisable marker.
        $payload = "Exif\0\0".'GPS-LOCATION-5.35N-0.62W';
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
        $jpeg = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        return UploadedFile::fake()->createWithContent($name, $jpeg);
    }

    /** @var list<UploadedFile> keeps fake uploads (and their temp files) alive for the test */
    private array $keep = [];

    private function jpegPath(): string
    {
        $this->keep[] = $file = $this->jpeg();

        return (string) $file->getRealPath();
    }

    private function album(array $attributes = []): Album
    {
        $album = new Album(['title' => 'Pitch Day 2026', 'event_date' => '2026-11-20']);
        $album->forceFill($attributes + ['is_published' => true, 'published_at' => now()])->save();

        return $album;
    }

    public function test_officer_creates_an_album_and_uploads_photos_which_are_resized_and_stripped(): void
    {
        $this->signInAs(Role::Officer);

        $this->post(route('workspace.albums.store'), ['title' => 'Bootcamp 2026', 'event_date' => '2026-11-01', 'location' => 'North Campus', 'is_published' => '1'])
            ->assertRedirect();
        $album = Album::sole();
        $this->assertTrue($album->is_published);

        $this->post(route('workspace.albums.photos.store', $album), ['photos' => [$this->jpeg('a.jpg', 3000, 2000), $this->jpeg('b.jpg')]])
            ->assertRedirect(route('workspace.albums.edit', $album));

        $this->assertSame(2, $album->photos()->count());
        $photo = $album->photos()->first();
        $this->assertSame($photo->id, $album->fresh()->cover_photo_id);
        $this->assertSame([1600, 1067], [$photo->width, $photo->height]);

        $large = Storage::disk('gallery')->get($photo->path_large);
        $this->assertStringStartsWith("\xFF\xD8", $large);
        $this->assertStringNotContainsString('GPS-LOCATION', $large);
        $this->assertStringNotContainsString('GPS-LOCATION', Storage::disk('gallery')->get($photo->path_thumb));
        [$tw] = getimagesizefromstring(Storage::disk('gallery')->get($photo->path_thumb));
        $this->assertSame(640, $tw);
    }

    public function test_non_images_disguised_files_and_tiny_images_are_rejected(): void
    {
        $this->signInAs(Role::Officer);
        $album = $this->album();

        $php = UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>');
        $this->post(route('workspace.albums.photos.store', $album), ['photos' => [$php]])->assertSessionHasErrors('photos.0');

        $pdf = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
        $this->post(route('workspace.albums.photos.store', $album), ['photos' => [$pdf]])->assertSessionHasErrors('photos.0');

        $this->post(route('workspace.albums.photos.store', $album), ['photos' => [$this->jpeg('tiny.jpg', 100, 100)]])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, AlbumPhoto::count());
        $this->assertSame([], Storage::disk('gallery')->allFiles());
    }

    public function test_interns_upload_but_cannot_publish_or_delete_and_viewers_have_no_access(): void
    {
        $this->signInAs(Role::Intern);

        $this->post(route('workspace.albums.store'), ['title' => 'Workshop', 'is_published' => '1', 'is_featured' => '1'])->assertRedirect();
        $album = Album::sole();
        $this->assertFalse($album->is_published);
        $this->assertFalse($album->is_featured);

        $this->post(route('workspace.albums.photos.store', $album), ['photos' => [$this->jpeg()]])->assertRedirect();
        $this->assertSame(1, $album->photos()->count());
        $this->delete(route('workspace.albums.destroy', $album))->assertForbidden();

        // Once published by an officer, interns can't edit it any more.
        $album->forceFill(['is_published' => true])->save();
        $this->put(route('workspace.albums.update', $album), ['title' => 'Changed'])->assertForbidden();

        $this->signInAs(Role::Viewer);
        $this->get(route('workspace.albums.index'))->assertForbidden();
        $this->post(route('workspace.albums.store'), ['title' => 'Nope'])->assertForbidden();
    }

    public function test_photos_in_draft_albums_are_hidden_from_the_public(): void
    {
        $album = $this->album(['is_published' => false]);
        $photo = app(GalleryService::class)->addFromPath($album, $this->jpegPath());

        $this->get(route('media.photo', [$photo, 'thumb']))->assertNotFound();
        $this->get(route('site.gallery.show', $album))->assertNotFound();

        $this->actingAs($this->staff(Role::Intern))->get(route('media.photo', [$photo, 'thumb']))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($this->staff(Role::Viewer))->get(route('media.photo', [$photo, 'thumb']))->assertNotFound();
    }

    public function test_public_gallery_pages_homepage_and_cached_photos(): void
    {
        $album = $this->album(['is_featured' => true]);
        $photo = app(GalleryService::class)->addFromPath($album, $this->jpegPath(), caption: 'Winners on stage');

        $this->get(route('site.gallery.index'))->assertOk()->assertSee('Pitch Day 2026');
        $this->get(route('site.gallery.show', $album))->assertOk()->assertSee('Winners on stage')->assertSee('aria-modal="true"', false);
        $this->get('/')->assertOk()->assertSee('Moments from the Hub');
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('site.gallery.show', $album), false);

        $response = $this->get(route('media.photo', [$photo, 'large']))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->get('/media/photos/'.$photo->id.'/original')->assertNotFound();
    }

    public function test_arranging_captions_order_and_cover(): void
    {
        $this->signInAs(Role::Officer);
        $album = $this->album();
        $a = app(GalleryService::class)->addFromPath($album, $this->jpegPath());
        $b = app(GalleryService::class)->addFromPath($album, $this->jpegPath());

        $this->put(route('workspace.albums.photos.arrange', $album), [
            'photos' => [$a->id => ['caption' => 'First', 'sort_order' => 5], $b->id => ['caption' => 'Second', 'sort_order' => 1]],
            'cover' => $b->id,
        ])->assertRedirect();

        $this->assertSame([$b->id, $a->id], $album->photos()->pluck('id')->all());
        $this->assertSame('First', $a->fresh()->caption);
        $this->assertSame($b->id, $album->fresh()->cover_photo_id);
    }

    public function test_deleting_photos_and_albums_removes_their_files(): void
    {
        $this->signInAs(Role::Officer);
        $album = $this->album();
        $a = app(GalleryService::class)->addFromPath($album, $this->jpegPath());
        $b = app(GalleryService::class)->addFromPath($album, $this->jpegPath());
        $this->assertCount(4, Storage::disk('gallery')->allFiles());

        $this->delete(route('workspace.albums.photos.destroy', [$album, $a]))->assertRedirect();
        $this->assertCount(2, Storage::disk('gallery')->allFiles());
        $this->assertSame($b->id, $album->fresh()->cover_photo_id);

        $this->delete(route('workspace.albums.destroy', $album))->assertRedirect(route('workspace.albums.index'));
        $this->assertSame([], Storage::disk('gallery')->allFiles());
        $this->assertSame(0, Album::count());
    }

    public function test_a_photo_cannot_be_deleted_through_another_album(): void
    {
        $this->signInAs(Role::Officer);
        $one = $this->album();
        $two = $this->album(['title' => 'Other']);
        $photo = app(GalleryService::class)->addFromPath($one, $this->jpegPath());

        $this->delete(route('workspace.albums.photos.destroy', [$two, $photo]))->assertNotFound();
        $this->assertNotNull($photo->fresh());
    }

    public function test_exif_orientation_is_read_without_the_exif_extension(): void
    {
        $img = imagecreatetruecolor(400, 300);
        ob_start();
        imagejpeg($img);
        $jpeg = (string) ob_get_clean();
        // Little-endian TIFF with one IFD entry: Orientation (0x0112) = 6.
        $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1).pack('vvVv', 0x0112, 3, 1, 6).pack('v', 0).pack('V', 0);
        $payload = "Exif\0\0".$tiff;
        $path = tempnam(sys_get_temp_dir(), 'exif');
        file_put_contents($path, substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2));

        $this->assertSame(6, GalleryService::exifOrientation($path));

        // Rotated to portrait when processed.
        $album = $this->album();
        $photo = app(GalleryService::class)->addFromPath($album, $path);
        $this->assertSame([300, 400], [$photo->width, $photo->height]);
        @unlink($path);
    }
}
