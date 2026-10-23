 <?php
/* Serves report and publication PDFs (public read-only). */

require_once __DIR__ . '/auth.php';
session_write_close(); /* don't block other requests during big downloads */

$folders = [
    'report'      => __DIR__ . '/reports/',
    'publication' => __DIR__ . '/publications/',
];

$type = $_GET['type'] ?? '';
$name = basename($_GET['name'] ?? '');

if (!isset($folders[$type]) || !preg_match('/^[A-Za-z0-9._-]+\.pdf$/i', $name)) {
    http_response_code(400);
    exit('Invalid file request.');
}

$path = $folders[$type] . $name;

if (!is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

$disposition = isset($_GET['download']) ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($path);
exit;