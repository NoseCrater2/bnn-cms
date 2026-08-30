<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class Recaptcha implements ValidationRule
{
    /**
     * Validate the Google reCAPTCHA response token.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (! is_string($secret) || $secret === '') {
            report(new \RuntimeException('Google reCAPTCHA is enabled but RECAPTCHA_SECRET_KEY is not configured.'));
            $fail('El captcha no está disponible en este momento.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(config('services.recaptcha.verify_url'), [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful() || $response->json('success') !== true) {
                $fail('No fue posible validar el captcha. Inténtalo nuevamente.');
            }
        } catch (Throwable $exception) {
            report($exception);
            $fail('No fue posible validar el captcha. Inténtalo nuevamente.');
        }
    }
}
