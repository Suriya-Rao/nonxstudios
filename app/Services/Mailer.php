<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Dependency-free SMTP mailer (sockets). Only used when MAIL_ENABLED=true and
 * credentials are configured. If SMTP is unavailable the inquiry is still
 * persisted, and the failure is logged rather than shown to the visitor.
 */
final class Mailer
{
    public static function enabled(): bool
    {
        return (bool) config('app.mail.enabled') && config('app.mail.host') !== '';
    }

    public static function send(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool
    {
        if (!self::enabled()) {
            Logger::info('Mail skipped (SMTP disabled)', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        $cfg  = config('app.mail');
        $host = (string) $cfg['host'];
        $port = (int) $cfg['port'];
        $enc  = strtolower((string) $cfg['encryption']);

        $transport = $enc === 'ssl' ? 'ssl://' : '';
        $timeout   = 15;

        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            Logger::error('SMTP connect failed', ['host' => $host, 'port' => $port, 'err' => $errstr]);
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            self::expect($socket, [220]);

            $hostname = gethostname() ?: 'localhost';
            self::command($socket, "EHLO $hostname", [250]);

            if ($enc === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Unable to start TLS');
                }
                self::command($socket, "EHLO $hostname", [250]);
            }

            $username = (string) $cfg['username'];
            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode((string) $cfg['password']), [235]);
            }

            $from = (string) $cfg['from'];
            $fromName = (string) $cfg['from_name'];
            self::command($socket, "MAIL FROM:<$from>", [250]);
            self::command($socket, "RCPT TO:<$to>", [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . self::encodeName($fromName) . " <$from>",
                "To: <$to>",
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'Date: ' . date('r'),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($hostname) . '>',
            ];
            if ($replyTo !== '') {
                $headers[] = "Reply-To: <$replyTo>";
            }

            $body = chunk_split(base64_encode($htmlBody), 76, "\r\n");
            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;

            // Dot-stuffing per RFC 5321.
            $data = preg_replace('/^\./m', '..', $data);

            fwrite($socket, $data . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);

            fclose($socket);
            Logger::info('Mail sent', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (\Throwable $e) {
            Logger::error('SMTP send failed: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    private static function command($socket, string $command, array $expect): void
    {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expect);
    }

    private static function expect($socket, array $codes): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException("SMTP unexpected response: " . trim($response));
        }
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private static function encodeName(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? self::encodeHeader($value) : '"' . $value . '"';
    }
}
