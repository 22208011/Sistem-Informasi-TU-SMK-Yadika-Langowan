<?php

namespace App\Support;

class MailConfiguration
{
    public static function defaultMailer(string $configuredMailer, string $host, int $port, string $environment): string
    {
        if ($configuredMailer === 'smtp' && $environment === 'production' && self::isLocalSmtp($host, $port)) {
            return 'failover';
        }

        return $configuredMailer;
    }

    public static function isLocalSmtp(string $host, int $port): bool
    {
        return in_array($host, ['127.0.0.1', 'localhost'], true) && in_array($port, [25, 1025, 2525], true);
    }
}
