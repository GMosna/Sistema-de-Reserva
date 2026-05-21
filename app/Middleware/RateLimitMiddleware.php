<?php
namespace App\Middleware;

class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 5, int $windowSeconds = 60)
    {
        $this->maxAttempts   = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . md5($ip);
        $now = time();

        $bucket = $_SESSION[$key] ?? ['attempts' => 0, 'window_start' => $now];

        // Reset window if expired
        if ($now - $bucket['window_start'] >= $this->windowSeconds) {
            $bucket = ['attempts' => 0, 'window_start' => $now];
        }

        $bucket['attempts']++;
        $_SESSION[$key] = $bucket;

        if ($bucket['attempts'] > $this->maxAttempts) {
            $retryAfter = $this->windowSeconds - ($now - $bucket['window_start']);
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            exit('Muitas tentativas. Aguarde ' . $retryAfter . ' segundos.');
        }
    }

    // Call on successful login to clear the bucket
    public static function clear(string $ip): void
    {
        $key = 'rate_limit_' . md5($ip);
        unset($_SESSION[$key]);
    }
}
