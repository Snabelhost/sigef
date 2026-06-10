<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RejectUnsafeEmailInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $violations = [];

        $this->scan($request->query->all(), '', $violations);
        $this->scan($request->request->all(), '', $violations);

        if ($violations !== []) {
            throw ValidationException::withMessages(
                array_fill_keys(array_values($violations), 'O e-mail informado contem caracteres invalidos.')
            );
        }

        return $next($request);
    }

    private function scan(array $data, string $prefix, array &$violations): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $this->scan($value, $path, $violations);
                continue;
            }

            if (! is_string($value) || ! $this->isEmailField((string) $key)) {
                continue;
            }

            if (str_contains($value, "\r") || str_contains($value, "\n")) {
                $violations[$path] = $path;
            }
        }
    }

    private function isEmailField(string $key): bool
    {
        $key = strtolower($key);

        return $key === 'email'
            || $key === 'mail_from_address'
            || $key === 'from_address'
            || str_ends_with($key, '_email')
            || str_contains($key, 'email');
    }
}
