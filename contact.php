<?php
/**
 * Kontaktformular-Handler
 * Sendet (1) Benachrichtigung an Dennis und (2) Bestätigung an Besucher.
 * Benötigt PHPMailer: composer require phpmailer/phpmailer
 */

// DEBUG – nach Test wieder entfernen
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['ok' => false, 'debug' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]));
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

header('Content-Type: application/json; charset=utf-8');

// Nur POST zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'msg' => 'Method not allowed']));
}

require_once __DIR__ . '/config.php';

require __DIR__ . '/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Eingaben bereinigen & validieren ─────────────────────────────────────────
$name      = trim(strip_tags($_POST['name']     ?? ''));
$email     = filter_var(trim($_POST['email']    ?? ''), FILTER_VALIDATE_EMAIL);
$phone     = trim(strip_tags($_POST['phone']    ?? ''));
$anliegen  = trim(strip_tags($_POST['anliegen'] ?? ''));
$nachricht = trim(strip_tags($_POST['nachricht'] ?? ''));
$consent   = !empty($_POST['consent']);
$honeypot  = $_POST['_hp'] ?? ''; // Bot-Falle: muss leer bleiben

if (!$name || !$email || !$nachricht || !$consent || $honeypot !== '') {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'msg' => 'Bitte alle Pflichtfelder ausfüllen.']));
}

// ── SMTP-Hilfsfunktion ────────────────────────────────────────────────────────
function buildMailer(): PHPMailer {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = SMTP_HOST;
    $m->SMTPAuth   = true;
    $m->Username   = SMTP_USER;
    $m->Password   = SMTP_PASS;
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $m->Port       = SMTP_PORT;
    $m->CharSet    = 'UTF-8';
    return $m;
}

// ── 1. Benachrichtigung an Dennis ────────────────────────────────────────────
try {
    $m = buildMailer();
    $m->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $m->addAddress(MAIL_TO, MAIL_TO_NAME);
    $m->addReplyTo($email, $name);
    $m->isHTML(true);
    $m->Subject = "Neue Anfrage: {$anliegen} – {$name}";
    $m->Body    = notificationHtml($name, $email, $phone, $anliegen, $nachricht);
    $m->AltBody = notificationText($name, $email, $phone, $anliegen, $nachricht);
    $m->send();
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['ok' => false, 'msg' => 'Senden fehlgeschlagen. Bitte direkt an mail@dennis-augustin.com schreiben.']));
}

// ── 2. Bestätigung an Besucher ────────────────────────────────────────────────
try {
    $c = buildMailer();
    $c->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $c->addAddress($email, $name);
    $c->isHTML(true);
    $c->Subject = 'Ihre Anfrage ist angekommen – Dennis Augustin';
    $c->Body    = confirmationHtml($name, $anliegen);
    $c->AltBody = "Hallo {$name},\n\nvielen Dank für Ihre Anfrage. Ich melde mich schnellstmöglich bei Ihnen – in der Regel innerhalb von 24 Stunden.\n\nMit freundlichen Grüßen\nDennis Augustin\nmail@dennis-augustin.com";
    $c->send();
} catch (Exception $e) {
    // Bestätigungs-Mail optional – Fehler nicht an Besucher weitergeben
}

exit(json_encode(['ok' => true]));


// ════════════════════════════════════════════════════════════════════════════════
// E-MAIL-TEMPLATES
// ════════════════════════════════════════════════════════════════════════════════

function notificationHtml(string $name, string $email, string $phone, string $anliegen, string $nachricht): string {
    $date     = date('d.m.Y, H:i') . ' Uhr';
    $phoneRow = $phone
        ? "<tr><td style='padding:6px 0;color:#888;font-size:13px;width:110px'>Telefon</td><td style='padding:6px 0;font-size:14px;color:#1a1a1a'>" . htmlspecialchars($phone) . "</td></tr>"
        : '';
    $nachrichtHtml = nl2br(htmlspecialchars($nachricht));
    $replyLink     = 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode("Re: Ihre Anfrage – " . $name);

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f0f0f2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f2;padding:40px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

  <!-- Header -->
  <tr><td style="background:#0e0e12;border-radius:12px 12px 0 0;padding:24px 36px">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="color:#fff;font-size:16px;font-weight:600;letter-spacing:.02em">
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ff5a30;margin-right:8px;vertical-align:middle"></span>Dennis Augustin
        </td>
        <td align="right" style="color:#666;font-size:12px">Neue Kontaktanfrage</td>
      </tr>
    </table>
  </td></tr>

  <!-- Orange bar -->
  <tr><td style="background:#ff5a30;height:3px;font-size:0;line-height:0">&nbsp;</td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 36px">

    <!-- Intro -->
    <p style="margin:0 0 8px 0;font-size:11px;color:#ff5a30;font-weight:600;letter-spacing:.1em;text-transform:uppercase">Neue Anfrage über dennis-augustin.com</p>
    <h1 style="margin:0 0 32px 0;font-size:24px;font-weight:700;color:#0e0e12;line-height:1.3">{$name}</h1>

    <!-- Info-Karte -->
    <table cellpadding="0" cellspacing="0" width="100%" style="background:#f7f7f9;border-radius:10px;border:1px solid #e8e8ec;padding:24px;margin-bottom:32px">
      <tr><td style="padding:0">
        <table cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td style="padding:6px 0;color:#888;font-size:13px;width:110px">E-Mail</td>
            <td style="padding:6px 0;font-size:14px;color:#1a1a1a"><a href="mailto:{$email}" style="color:#ff5a30;text-decoration:none">{$email}</a></td>
          </tr>
          {$phoneRow}
          <tr>
            <td style="padding:6px 0;color:#888;font-size:13px">Anliegen</td>
            <td style="padding:6px 0;font-size:14px;color:#1a1a1a">
              <span style="background:#ff5a30;color:#fff;font-size:11px;font-weight:600;padding:3px 10px;border-radius:100px;letter-spacing:.04em">{$anliegen}</span>
            </td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#888;font-size:13px">Eingang</td>
            <td style="padding:6px 0;font-size:14px;color:#1a1a1a">{$date}</td>
          </tr>
        </table>
      </td></tr>
    </table>

    <!-- Nachricht -->
    <p style="margin:0 0 12px 0;font-size:11px;color:#888;font-weight:600;letter-spacing:.08em;text-transform:uppercase">Nachricht</p>
    <div style="background:#f7f7f9;border-left:3px solid #ff5a30;border-radius:0 8px 8px 0;padding:20px 24px;font-size:15px;line-height:1.7;color:#333;margin-bottom:36px">{$nachrichtHtml}</div>

    <!-- CTA -->
    <table cellpadding="0" cellspacing="0">
      <tr><td style="border-radius:100px;background:#ff5a30">
        <a href="{$replyLink}" style="display:inline-block;padding:14px 28px;color:#fff;font-size:14px;font-weight:600;text-decoration:none;letter-spacing:.02em">Jetzt antworten &rarr;</a>
      </td></tr>
    </table>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#0e0e12;border-radius:0 0 12px 12px;padding:20px 36px">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="color:#555;font-size:12px">Diese Nachricht wurde über das Kontaktformular auf dennis-augustin.com gesendet.</td>
        <td align="right" style="color:#444;font-size:12px">&copy; {$date}</td>
      </tr>
    </table>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function notificationText(string $name, string $email, string $phone, string $anliegen, string $nachricht): string {
    $date = date('d.m.Y, H:i') . ' Uhr';
    return "Neue Kontaktanfrage über dennis-augustin.com\n"
         . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
         . "Name:     {$name}\n"
         . "E-Mail:   {$email}\n"
         . ($phone ? "Telefon:  {$phone}\n" : '')
         . "Anliegen: {$anliegen}\n"
         . "Eingang:  {$date}\n\n"
         . "NACHRICHT\n"
         . "─────────\n"
         . "{$nachricht}\n";
}

function confirmationHtml(string $name, string $anliegen): string {
    $firstName = explode(' ', trim($name))[0];

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f0f0f2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f2;padding:40px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

  <!-- Header -->
  <tr><td style="background:#0e0e12;border-radius:12px 12px 0 0;padding:24px 36px">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td style="color:#fff;font-size:16px;font-weight:600;letter-spacing:.02em">
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ff5a30;margin-right:8px;vertical-align:middle"></span>Dennis Augustin
        </td>
      </tr>
    </table>
  </td></tr>

  <!-- Orange bar -->
  <tr><td style="background:#ff5a30;height:3px;font-size:0;line-height:0">&nbsp;</td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:48px 36px;text-align:center">

    <!-- Check-Icon -->
    <div style="width:60px;height:60px;background:#ff5a30;border-radius:50%;margin:0 auto 28px;line-height:60px;font-size:26px;color:#fff">&#10003;</div>

    <h1 style="margin:0 0 12px;font-size:26px;font-weight:700;color:#0e0e12">Ihre Nachricht ist angekommen.</h1>
    <p style="margin:0 0 32px;font-size:16px;line-height:1.7;color:#555;max-width:420px;margin-left:auto;margin-right:auto">
      Hallo {$firstName},<br><br>
      vielen Dank für Ihre Anfrage zum Thema <strong style="color:#0e0e12">{$anliegen}</strong>.<br>
      Ich melde mich schnellstmöglich bei Ihnen – in der Regel innerhalb von 24 Stunden.
    </p>

    <!-- Divider -->
    <div style="border-top:1px solid #e8e8ec;margin:32px auto;max-width:300px"></div>

    <p style="margin:0;font-size:13px;color:#888;line-height:1.7">
      Falls etwas Dringendes ist:<br>
      <a href="mailto:mail@dennis-augustin.com" style="color:#ff5a30;text-decoration:none;font-weight:600">mail@dennis-augustin.com</a>
    </p>

  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#0e0e12;border-radius:0 0 12px 12px;padding:20px 36px;text-align:center">
    <p style="margin:0;color:#444;font-size:12px">
      <a href="https://dennis-augustin.com" style="color:#ff5a30;text-decoration:none">dennis-augustin.com</a>
      &nbsp;&middot;&nbsp;
      <a href="https://dennis-augustin.com/datenschutz.html" style="color:#555;text-decoration:none">Datenschutz</a>
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}
