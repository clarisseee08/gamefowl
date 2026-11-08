<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Fortify is headless - it ships no views. These bind our own Blade
        // views to its routes.
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        /*
         * The reset email, from the farm by name rather than Laravel's stock
         * "Reset Password Notification" signed with APP_NAME. The views carry
         * inline hex from config/gfms-brand.php, because email clients strip
         * stylesheets. The link is built the way the stock notification builds
         * it, so it opens the same reset screen.
         */
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $farm = config('gfms.farm');

            return (new MailMessage)
                ->subject('Reset your password · '.$farm['name'])
                ->view(['html' => 'mail.password-reset', 'text' => 'mail.password-reset-text'], [
                    'farm' => $farm,
                    'email' => $user->getEmailForPasswordReset(),
                    'url' => url(route('password.reset', [
                        'token' => $token,
                        'email' => $user->getEmailForPasswordReset(),
                    ], false)),
                    'minutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                    'brand' => config('gfms-brand'),
                ]);
        });

        /*
         * Custom authentication.
         *
         * Beyond checking the password, this refuses deactivated accounts. A
         * deactivated user keeps their audit history but must not be able to
         * sign in. The error is deliberately specific here - an owner who
         * disabled the account wants the person to understand why, rather than
         * assume they mistyped their password.
         */
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This account has been deactivated. Please contact the farm owner.',
                ]);
            }

            return $user;
        });

        // Throttle sign-in attempts per email+IP. This is the cheapest
        // meaningful defence against password guessing.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
