<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\BroadcastStatus;
use App\Http\Controllers\Auth\EmailLinkController;
use App\Jobs\SendBroadcast;
use App\Mail\TemplatedMail;
use App\Models\Broadcast;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\BroadcastService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Signed links in e-mails (verify, reset, unsubscribe) and the e-mail design. */
class EmailLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function linkSentTo(User $user): string
    {
        $link = null;
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($user, &$link) {
            if ($mail->hasTo($user->email) && $mail->actionUrl) {
                $link = $mail->actionUrl;

                return true;
            }

            return false;
        });

        return (string) $link;
    }

    public function test_verification_button_confirms_on_post_only_and_works_once(): void
    {
        $user = User::factory()->member()->unonboarded()->create(['email_verified_at' => null]);
        app(VerificationService::class)->issue($user, VerificationCode::VERIFY_EMAIL);
        $link = $this->linkSentTo($user);

        $this->assertStringContainsString('/email/verify/'.$user->id.'/', $link);
        $this->assertStringContainsString('signature=', $link);

        // Opening the link (as a mail scanner would) changes nothing.
        $this->get($link)->assertOk()->assertSee('Confirm my e-mail')->assertDontSee($user->email);
        $this->assertNull($user->fresh()->email_verified_at);

        $this->post($link)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'member.verified', 'subject_id' => $user->id]);

        // The code in the same e-mail was used up by the link.
        $this->assertSame(0, VerificationCode::where('user_id', $user->id)->whereNull('consumed_at')->count());
    }

    public function test_verifying_in_your_own_session_continues_to_onboarding(): void
    {
        $user = User::factory()->member()->unonboarded()->create(['email_verified_at' => null]);
        app(VerificationService::class)->issue($user, VerificationCode::VERIFY_EMAIL);

        $this->actingAs($user)->post($this->linkSentTo($user))->assertRedirect(route('onboarding.show'));
    }

    public function test_tampered_expired_and_replaced_links_are_refused(): void
    {
        $user = User::factory()->member()->create(['email_verified_at' => null]);
        $other = User::factory()->member()->create(['email_verified_at' => null]);
        $codes = app(VerificationService::class);
        $codes->issue($user, VerificationCode::VERIFY_EMAIL);
        $link = $this->linkSentTo($user);

        // Pointing a valid signature at another account breaks the signature.
        $this->post(str_replace("/email/verify/{$user->id}/", "/email/verify/{$other->id}/", $link))
            ->assertForbidden()->assertSee('This link has expired');
        $this->assertNull($other->fresh()->email_verified_at);

        // A newer code e-mail replaces the older link.
        $this->travel(61)->seconds();
        $codes->issue($user, VerificationCode::VERIFY_EMAIL);
        $this->post($link)->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);

        // Expired.
        $fresh = collect(Mail::sent(TemplatedMail::class))->last()->actionUrl;
        $this->travel(11)->minutes();
        $this->post($fresh)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_reset_button_sets_a_new_password_once_and_revokes_sessions(): void
    {
        $user = User::factory()->member()->create(['password' => 'Old-Pass-2026x']);
        app(VerificationService::class)->issue($user, VerificationCode::RESET_PASSWORD);
        $link = $this->linkSentTo($user);

        $this->assertStringContainsString('/password/reset/'.$user->id.'/', $link);
        $this->get($link)->assertOk()->assertSee('Choose a new password');

        // A weak password is rejected without using up the link.
        $this->from($link)->post($link, ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');

        $this->post($link, ['password' => 'New-Pass-2026x', 'password_confirmation' => 'New-Pass-2026x'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('New-Pass-2026x', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_reset', 'subject_id' => $user->id]);

        $this->post($link, ['password' => 'Third-Pass-2026x', 'password_confirmation' => 'Third-Pass-2026x'])->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('New-Pass-2026x', $user->fresh()->password));
    }

    public function test_reset_links_do_not_work_for_suspended_accounts(): void
    {
        $user = User::factory()->member()->create();
        app(VerificationService::class)->issue($user, VerificationCode::RESET_PASSWORD);
        $link = $this->linkSentTo($user);
        $user->forceFill(['is_active' => false])->save();

        $this->get($link)->assertNotFound();
        $this->post($link, ['password' => 'New-Pass-2026x', 'password_confirmation' => 'New-Pass-2026x'])->assertNotFound();
    }

    public function test_code_emails_are_branded_with_button_and_inline_styles(): void
    {
        $user = User::factory()->member()->create(['name' => 'Ama Owusu', 'email_verified_at' => null]);
        app(VerificationService::class)->issue($user, VerificationCode::VERIFY_EMAIL);
        $mail = collect(Mail::sent(TemplatedMail::class))->last();
        $html = $mail->render();

        // The crest is always a hosted image (never an attachment), from MAIL_ASSET_URL.
        $this->assertMatchesRegularExpression('/<img src="[^"]+\/images\/uew\/email-crest\.png"[^>]*alt="University of Education, Winneba crest"/', $html);
        $this->assertStringNotContainsString('cid:', $html);
        config(['mail.asset_url' => 'https://hub.example.org']);
        $this->assertStringContainsString('src="https://hub.example.org/images/uew/email-crest.png"', $mail->render());
        $this->assertStringContainsString('UEW Innovation Hub', $html);
        $this->assertStringContainsString('Verify my e-mail', $html);
        $this->assertStringContainsString(e($mail->actionUrl), $html);
        $this->assertStringContainsString('#25267a', $html);                   // UEW crest blue
        $this->assertStringContainsString('#e41824', $html);                   // UEW crest red accent
        $this->assertMatchesRegularExpression('/<h1 class="code" style="[^"]*letter-spacing/', $html);
        $this->assertStringContainsString('Your account', $html);              // section label
        $this->assertStringContainsString((string) $mail->values['code'], $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_member_supplied_text_cannot_inject_html_into_emails(): void
    {
        $html = (new TemplatedMail('application_submitted', [
            'first_name' => '<img src=x onerror=alert(1)>', 'program' => '[click](javascript:alert(2))', 'reference' => 'X',
        ]))->render();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function test_broadcasts_carry_one_click_unsubscribe_headers_that_work(): void
    {
        $member = User::factory()->member()->create(['marketing_opt_in' => true, 'interests' => ['STEM']]);
        $broadcast = new Broadcast(['subject' => 'News', 'body' => 'Hello {{ first_name }}', 'audience' => ['interests' => ['STEM'], 'affiliations' => []]]);
        $broadcast->created_by = $this->staff()->id;
        $broadcast->status = BroadcastStatus::Sending;
        $broadcast->save();

        (new SendBroadcast($broadcast->id))->handle(app(BroadcastService::class));

        $mail = collect(Mail::queued(TemplatedMail::class))->sole();
        $headers = $mail->headers()->text;
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
        $url = trim($headers['List-Unsubscribe'], '<>');

        // What Gmail/Yahoo send: a POST with this body and no CSRF token.
        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertNoContent();
        $this->assertFalse($member->fresh()->marketing_opt_in);

        // Without a valid signature nothing happens.
        $member->refresh()->forceFill(['marketing_opt_in' => true])->save();
        $this->post(route('site.unsubscribe.store', $member), ['List-Unsubscribe' => 'One-Click'])->assertForbidden();
        $this->assertTrue($member->fresh()->marketing_opt_in);
    }

    public function test_masked_email_hides_the_local_part(): void
    {
        $this->assertSame('j••••••@gmail.com', EmailLinkController::mask('johnotc@gmail.com'));
        $this->assertSame('a•••@uew.edu.gh', EmailLinkController::mask('ab@uew.edu.gh'));
        $this->assertNotNull(URL::to('/'));
    }
}
