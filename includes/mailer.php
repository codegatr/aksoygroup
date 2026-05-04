<?php
/**
 * AKSOY GROUP — SMTP Mailer (Native, no dependencies)
 * SSL/TLS desteği. ag_settings tablosundan credentials okur.
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class Mailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $secure;
    private string $fromEmail;
    private string $fromName;
    private array $errors = [];

    public function __construct()
    {
        $this->host      = (string)setting('smtp_host', '');
        $this->port      = (int)setting('smtp_port', 465);
        $this->user      = (string)setting('smtp_user', '');
        $this->pass      = (string)setting('smtp_pass', '');
        $this->secure    = (string)setting('smtp_secure', 'ssl');
        $this->fromEmail = (string)setting('smtp_from_email', AG_MAIL_FROM_EMAIL);
        $this->fromName  = (string)setting('smtp_from_name', AG_MAIL_FROM_NAME);
    }

    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        if (!$this->host) {
            $this->errors[] = 'SMTP yapılandırılmamış.';
            return false;
        }

        $boundary = '=_b_' . bin2hex(random_bytes(8));
        $text = $text ?? trim(strip_tags($html));

        $headers = [];
        $headers[] = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromEmail}>";
        $headers[] = "To: <{$to}>";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . bin2hex(random_bytes(16)) . "@{$_SERVER['HTTP_HOST']}>";
        $headerStr = implode("\r\n", $headers);

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        return $this->smtpSend($to, $headerStr, $body);
    }

    private function smtpSend(string $to, string $headers, string $body): bool
    {
        $hostPrefix = $this->secure === 'ssl' ? 'ssl://' : '';
        $errno = 0;
        $errstr = '';
        $sock = @stream_socket_client(
            $hostPrefix . $this->host . ':' . $this->port,
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT
        );
        if (!$sock) {
            $this->errors[] = "Bağlantı: $errstr ($errno)";
            return false;
        }
        stream_set_timeout($sock, 15);

        $expect = function(int $code) use ($sock): bool {
            $line = '';
            while (($r = fgets($sock, 515)) !== false) {
                $line .= $r;
                if (isset($r[3]) && $r[3] === ' ') break;
            }
            if (!str_starts_with($line, (string)$code)) {
                $this->errors[] = trim($line);
                return false;
            }
            return true;
        };

        $send = function(string $cmd) use ($sock): void {
            fwrite($sock, $cmd . "\r\n");
        };

        if (!$expect(220)) { fclose($sock); return false; }

        $hostname = $_SERVER['HTTP_HOST'] ?? 'aksoy.web.tr';
        $send("EHLO {$hostname}");
        if (!$expect(250)) { fclose($sock); return false; }

        if ($this->secure === 'tls') {
            $send("STARTTLS");
            if (!$expect(220)) { fclose($sock); return false; }
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO {$hostname}");
            if (!$expect(250)) { fclose($sock); return false; }
        }

        $send("AUTH LOGIN");
        if (!$expect(334)) { fclose($sock); return false; }
        $send(base64_encode($this->user));
        if (!$expect(334)) { fclose($sock); return false; }
        $send(base64_encode($this->pass));
        if (!$expect(235)) { fclose($sock); return false; }

        $send("MAIL FROM:<{$this->fromEmail}>");
        if (!$expect(250)) { fclose($sock); return false; }
        $send("RCPT TO:<{$to}>");
        if (!$expect(250)) { fclose($sock); return false; }
        $send("DATA");
        if (!$expect(354)) { fclose($sock); return false; }

        $payload = $headers . "\r\n\r\n" . $body . "\r\n.";
        $send($payload);
        if (!$expect(250)) { fclose($sock); return false; }

        $send("QUIT");
        fclose($sock);
        return true;
    }

    public function lastError(): string
    {
        return end($this->errors) ?: '';
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
