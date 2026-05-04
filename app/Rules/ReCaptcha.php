<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class ReCaptcha implements ValidationRule
{
    protected float $minScore;
    protected string $action;

    public function __construct(float $minScore = 0.5, string $action = '')
    {
        $this->minScore = $minScore;
        $this->action = $action;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        if (!config('services.recaptcha.enabled', false)) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? request()->ip();
        if (config('app.debug', true) && in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $result = $response->json();

        if (!$result || !($result['success'] ?? false)) {
            $fail('The reCAPTCHA verification failed. Please try again.');
            return;
        }

        if ($this->minScore > 0 && isset($result['score'])) {
            if ($result['score'] < $this->minScore) {
                $fail('The reCAPTCHA verification failed. Please try again.');
                return;
            }
        }

        if ($this->action && isset($result['action'])) {
            if ($result['action'] !== $this->action) {
                $fail('The reCAPTCHA verification failed. Please try again.');
            }
        }
    }
}
