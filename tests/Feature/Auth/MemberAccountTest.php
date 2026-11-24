<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Mail\TemplatedMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Sign-up, e-mail OTP verification, onboarding and OTP password reset. */
class MemberAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function codeSentTo(string $email): string
    {
        $code = null;
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($email, &$code) {
            if ($mail->hasTo($email) && isset($mail->values['code'])) {
                $code = (string) $mail->values['code'];

                return true;
            }

            return false;
        });

        return $code;
    }

    private function register(string $email = 'ama@example.com'): User
    {
        $this->post('/sign-up', [
            'name' => 'Ama Serwaa', 'email' => $email,
            'phone' => '+233 24 555 1234', 'gender' => 'female', 'date_of_birth' => '2002-05-14', 'region' => 'Central',
            'password' => 'Secret-pass-123', 'password_confirmation' => 'Secret-pass-123', 'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        return User::where('email', $email)->sole();
    }

    public function test_sign_up_creates_an_unverified_member_and_emails_a_hashed_code(): void
    {
        $user = $this->register();

        $this->assertSame(Role::Member, $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('+233 24 555 1234', $user->phone);
        $this->assertSame('female', $user->gender);

        $code = $this->codeSentTo('ama@example.com');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $stored = VerificationCode::where('user_id', $user->id)->sole();
        $this->assertNotSame($code, $stored->code_hash);
        $this->assertTrue(Hash::check($code, $stored->code_hash));
    }

    public function test_role_cannot_be_injected_at_sign_up(): void
    {
        $this->post('/sign-up', [
            'name' => 'Mallory', 'email' => 'm@example.com', 'role' => 'super_admin', 'is_active' => 1, 'email_verified_at' => now(),
            'phone' => '+233 24 555 9999', 'gender' => 'prefer_not', 'date_of_birth' => '1990-01-01', 'region' => 'Volta',
            'password' => 'Secret-pass-123', 'password_confirmation' => 'Secret-pass-123', 'terms' => '1',
        ]);

        $user = User::where('email', 'm@example.com')->sole();
        $this->assertSame(Role::Member, $user->role);
        $this->assertNull($user->email_verified_at);
    }

    public function test_unverified_members_cannot_use_the_portal(): void
    {
        $this->register();

        $this->get('/portal')->assertRedirect(route('verification.notice'));
        $this->get('/onboarding')->assertRedirect(route('verification.notice'));
    }

    public function test_correct_code_verifies_then_onboarding_is_required(): void
    {
        $user = $this->register();
        $code = $this->codeSentTo('ama@example.com');

        $this->post('/verify-email', ['code' => $code])->assertRedirect(route('onboarding.show'));
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get('/portal')->assertRedirect(route('onboarding.show'));

        $this->post('/onboarding', [
            'name' => 'Ama Serwaa', 'phone' => '+233 24 555 1234', 'affiliation' => 'student',
            'gender' => 'female', 'date_of_birth' => '2002-05-14', 'region' => 'Central', 'education_level' => 'undergraduate',
            'interests' => ['STEM', 'Fellowships'], 'marketing_opt_in' => '1',
        ])->assertRedirect(route('portal.dashboard'));

        $user->refresh();
        $this->assertSame('Central', $user->region);
        $this->assertSame('2002-05-14', $user->date_of_birth->toDateString());
        $this->assertNotNull($user->onboarded_at);
        $this->assertSame(['STEM', 'Fellowships'], $user->interests);
        $this->assertTrue($user->marketing_opt_in);
        $this->get('/portal')->assertOk()->assertSee('Hello, Ama');
    }

    public function test_wrong_codes_are_limited_to_five_attempts(): void
    {
        $user = $this->register();
        $code = $this->codeSentTo('ama@example.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $i) {
            $this->post('/verify-email', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        // Even the right code is refused once attempts are used up.
        $this->post('/verify-email', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_codes_are_rejected(): void
    {
        $user = $this->register();
        $code = $this->codeSentTo('ama@example.com');

        $this->travel(11)->minutes();

        $this->post('/verify-email', ['code' => $code])->assertSessionHasErrors(['code' => 'This code has expired. Ask for a new one.']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_is_throttled(): void
    {
        $this->register();

        $this->post('/verify-email/resend')->assertSessionHasErrors('code');
        $this->travel(61)->seconds();
        $this->post('/verify-email/resend')->assertSessionHasNoErrors();
    }

    public function test_registration_can_be_paused(): void
    {
        app(SettingsService::class)->update(['registration_open' => false]);

        $this->get('/sign-up')->assertOk()->assertSee('registrations are paused');
        $this->post('/sign-up', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'Secret-pass-123', 'password_confirmation' => 'Secret-pass-123', 'terms' => '1',
        ])->assertForbidden();
    }

    public function test_password_reset_with_email_code(): void
    {
        $user = User::factory()->member()->create(['email' => 'reset@example.com']);

        $this->post('/forgot-password', ['email' => 'reset@example.com'])->assertRedirect(route('password.reset'));
        $code = $this->codeSentTo('reset@example.com');

        $this->post('/reset-password', ['code' => $code, 'password' => 'Brand-new-pass-1', 'password_confirmation' => 'Brand-new-pass-1'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Brand-new-pass-1', $user->fresh()->password));
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        $known = $this->post('/forgot-password', ['email' => 'nobody@example.com']);
        $known->assertRedirect(route('password.reset'))->assertSessionHas('status', 'If an account uses that address, we have sent it a 6-digit code.');

        Mail::assertNothingSent();
    }
}
