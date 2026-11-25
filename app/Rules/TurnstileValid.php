<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileValid implements ValidationRule
{
    public function __construct(private readonly ?string $ip = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(TurnstileService::class)->verifikasi($value, $this->ip)) {
            $fail('Verifikasi CAPTCHA gagal. Silakan ulangi centang verifikasi.');
        }
    }
}
