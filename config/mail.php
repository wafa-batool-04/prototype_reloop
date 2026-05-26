<?php
/**
 * Local email via Mailpit (SMTP :1025, web UI :8025).
 * Start Mailpit: mailpit\mailpit.exe
 */

define('MAILPIT_SMTP_HOST', '127.0.0.1');
define('MAILPIT_SMTP_PORT', 1025);
define('MAILPIT_WEB_UI', 'http://localhost:8025');
define('MAIL_FROM_EMAIL', 'noreply@reloophub.com');
define('MAIL_FROM_NAME', 'Reloop Electronic Hub');

/**
 * Send HTML email (with optional plain-text alternative) through Mailpit.
 *
 * @return array{ok: bool, error: ?string}
 */
function send_mail_via_mailpit(string $to, string $subject, string $htmlBody, ?string $plainBody = null): array
{
    $from = MAIL_FROM_EMAIL;
    $fromHeader = MAIL_FROM_NAME . ' <' . $from . '>';
    $boundary = 'reloop_' . bin2hex(random_bytes(8));

    if ($plainBody === null) {
        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    }

    $headers = [
        'From: ' . $fromHeader,
        'Reply-To: support@reloophub.com',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'X-Mailer: Reloop/PHP',
    ];

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $plainBody . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--\r\n";

    $message = 'Subject: ' . encode_mail_header($subject) . "\r\n";
    $message .= implode("\r\n", $headers) . "\r\n\r\n";
    $message .= $body;

    return smtp_send_to_mailpit($from, $to, $message);
}

function encode_mail_header(string $text): string
{
    if (preg_match('/[^\x20-\x7E]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
    return $text;
}

/**
 * @return array{ok: bool, error: ?string}
 */
function smtp_send_to_mailpit(string $from, string $to, string $message): array
{
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen(MAILPIT_SMTP_HOST, MAILPIT_SMTP_PORT, $errno, $errstr, 10);

    if (!$socket) {
        return [
            'ok' => false,
            'error' => 'Cannot connect to Mailpit at ' . MAILPIT_SMTP_HOST . ':' . MAILPIT_SMTP_PORT
                . '. Start mailpit\\mailpit.exe and try again.',
        ];
    }

    stream_set_timeout($socket, 10);

    $read = function () use ($socket): string {
        $data = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };

    $expect = function (string $response, array $codes) use (&$read): bool {
        $code = (int) substr($response, 0, 3);
        return in_array($code, $codes, true);
    };

    $write = function (string $command) use ($socket, $read): string {
        fwrite($socket, $command . "\r\n");
        return $read();
    };

    $banner = $read();
    if (!$expect($banner, [220])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'Mailpit SMTP did not respond (220).'];
    }

    $ehlo = $write('EHLO localhost');
    if (!$expect($ehlo, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP EHLO failed.'];
    }

    $mailFrom = $write('MAIL FROM:<' . $from . '>');
    if (!$expect($mailFrom, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP MAIL FROM rejected.'];
    }

    $rcptTo = $write('RCPT TO:<' . $to . '>');
    if (!$expect($rcptTo, [250, 251])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP RCPT TO rejected.'];
    }

    $dataCmd = $write('DATA');
    if (!$expect($dataCmd, [354])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP DATA command rejected.'];
    }

    $lines = preg_split("/\r\n|\n|\r/", $message);
    foreach ($lines as $line) {
        if (isset($line[0]) && $line[0] === '.') {
            $line = '.' . $line;
        }
        fwrite($socket, $line . "\r\n");
    }
    fwrite($socket, ".\r\n");

    $dataEnd = $read();
    if (!$expect($dataEnd, [250])) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP message was not accepted.'];
    }

    $write('QUIT');
    fclose($socket);

    return ['ok' => true, 'error' => null];
}
