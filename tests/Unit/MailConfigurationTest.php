<?php

use App\Support\MailConfiguration;

test('falls back to failover in production when smtp is local', function (): void {
    expect(MailConfiguration::defaultMailer('smtp', '127.0.0.1', 1025, 'production'))->toBe('failover');
    expect(MailConfiguration::defaultMailer('smtp', 'localhost', 2525, 'production'))->toBe('failover');
});

test('keeps the configured mailer outside the localhost production case', function (): void {
    expect(MailConfiguration::defaultMailer('smtp', 'mail.example.com', 587, 'production'))->toBe('smtp');
    expect(MailConfiguration::defaultMailer('log', '127.0.0.1', 1025, 'production'))->toBe('log');
    expect(MailConfiguration::defaultMailer('smtp', '127.0.0.1', 1025, 'local'))->toBe('smtp');
});
