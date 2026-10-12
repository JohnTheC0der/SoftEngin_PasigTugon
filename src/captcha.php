<?php
// Generates a CAPTCHA image and remembers the answer in the visitor's session.
// Used by citizen_concern_station.html: <img src="../src/captcha.php">
// Self-hosted (no Google/Cloudflare account or keys needed). Requires PHP's GD
// extension, which is enabled by default in XAMPP.

// Buffer everything so a stray PHP warning can't corrupt the PNG data
ob_start();
session_start();

// GD missing? Say so in plain text (visible if you open this URL directly in the browser)
if (!function_exists('imagecreatetruecolor')) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain');
    exit("CAPTCHA unavailable: PHP's GD extension is not enabled.\n"
       . "Open php.ini (XAMPP Control Panel > Apache > Config > PHP (php.ini)), find the line\n"
       . ";extension=gd , remove the leading semicolon, save, and restart Apache.");
}

// No look-alike characters (0/O, 1/I/L)
$chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 5; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha_code'] = $code;
$_SESSION['captcha_time'] = time();

// Draw small, then scale up: built-in GD fonts are tiny, and resizing gives
// uneven, harder-to-read-by-script letters without needing a TTF font file.
$smallW = 80; $smallH = 24;
$small = imagecreatetruecolor($smallW, $smallH);
imagefill($small, 0, 0, imagecolorallocate($small, 235, 244, 255));

for ($i = 0; $i < 6; $i++) {   // noise lines
    $c = imagecolorallocate($small, random_int(120, 200), random_int(150, 210), random_int(190, 240));
    imageline($small, random_int(0, $smallW), random_int(0, $smallH), random_int(0, $smallW), random_int(0, $smallH), $c);
}
for ($i = 0; $i < 5; $i++) {   // letters with random vertical jitter and shade
    $c = imagecolorallocate($small, random_int(0, 60), random_int(30, 90), random_int(100, 170));
    imagestring($small, 5, 8 + $i * 13, random_int(2, 8), $code[$i], $c);
}
for ($i = 0; $i < 60; $i++) {  // speckles
    imagesetpixel($small, random_int(0, $smallW - 1), random_int(0, $smallH - 1),
        imagecolorallocate($small, random_int(100, 220), random_int(100, 220), random_int(100, 220)));
}

$w = 240; $h = 72;
$img = imagecreatetruecolor($w, $h);
imagecopyresampled($img, $small, 0, 0, 0, 0, $w, $h, $smallW, $smallH);

ob_end_clean();   // discard any warnings printed so far
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
imagepng($img);
