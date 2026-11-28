<?php

// Documentation validator: php scripts/validate-docs.php [--fix-mojibake]
// Checks every docs/**/*.md (and README.md) for: broken relative links and
// anchors, missing referenced source paths, mojibake, Mermaid basics, secrets,
// and the required header.

$root = realpath(__DIR__.'/..');
$fix = in_array('--fix-mojibake', $argv, true);
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/docs', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (str_ends_with($f->getPathname(), '.md')) {
        $files[] = $f->getPathname();
    }
}
$files[] = $root.'/README.md';
sort($files);

$slug = function (string $heading): string {
    $h = strtolower(trim(preg_replace('/[`*]/', '', $heading)));
    $h = preg_replace('/[^\p{L}\p{N}_ -]/u', '', $h);

    return str_replace(' ', '-', $h);
};

$anchors = [];
foreach ($files as $file) {
    $text = file_get_contents($file);
    $text = preg_replace('/```.*?```/s', '', $text);
    preg_match_all('/^#{1,6}\s+(.+?)\s*#*$/m', $text, $m);
    $seen = [];
    foreach ($m[1] as $h) {
        $s = $slug($h);
        $n = $seen[$s] ?? 0;
        $seen[$s] = $n + 1;
        $anchors[realpath($file)][] = $n ? "{$s}-{$n}" : $s;
    }
    preg_match_all('/<a\s+(?:name|id)="([^"]+)"/', $text, $m2);
    foreach ($m2[1] as $a) {
        $anchors[realpath($file)][] = $a;
    }
}

$problems = [];
// Real secret values from .env (never printed): docs must not contain any of them.
$envSecrets = [];
foreach (@file($root.'/.env', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    if (preg_match('/^(MAIL_PASSWORD|APP_KEY|AWS_SECRET_ACCESS_KEY|AWS_ACCESS_KEY_ID|ADMIN_PASSWORD|SEED_PASSWORD|DB_PASSWORD|REDIS_PASSWORD)="?([^"]*)"?$/', $line, $em) && strlen($em[2]) >= 10) {
        $envSecrets[$em[1]] = $em[2];
        $envSecrets[$em[1].' (no spaces)'] = str_replace(' ', '', $em[2]);
    }
}
$mojibake = '/(Ã.|Â.|â€|â†|â‚|ðŸ)/u';
$secretPatterns = [
    'app key' => '/base64:[A-Za-z0-9+\/]{40,}={0,2}/',
    'aws key' => '/AKIA[0-9A-Z]{16}/',
    'private key' => '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
    'tester password' => '/Uew-H5KM/',
    'github token' => '/gh[pousr]_[A-Za-z0-9]{30,}/',
];

foreach ($files as $file) {
    $rel = substr($file, strlen($root) + 1);
    $text = file_get_contents($file);

    if (preg_match($mojibake, $text)) {
        if ($fix) {
            // Undo UTF-8 read as Windows-1252 and re-saved (possibly twice).
            for ($i = 0; $i < 3 && preg_match($mojibake, $text); $i++) {
                $text = preg_replace_callback('/[\x{00C0}-\x{00FF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}\x{2014}\x{2018}-\x{201E}\x{2020}-\x{2022}\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}][\x{0080}-\x{00FF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}\x{2014}\x{2018}-\x{201E}\x{2020}-\x{2022}\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}]{1,3}/u', function ($m) {
                    $bytes = @mb_convert_encoding($m[0], 'Windows-1252', 'UTF-8');

                    return ($bytes !== false && mb_check_encoding($bytes, 'UTF-8') && $bytes !== '') ? $bytes : $m[0];
                }, $text);
            }
            file_put_contents($file, $text);
        }
        if (preg_match($mojibake, $text, $mm)) {
            $problems[] = "$rel: mojibake '{$mm[0]}'";
        }
    }

    if (str_starts_with($text, "\xEF\xBB\xBF")) {
        $problems[] = "$rel: UTF-8 BOM";
    }

    if (! str_ends_with($rel, 'README.md') || str_contains($rel, 'docs')) {
        if (! preg_match('/^# .+\n/', $text)) {
            $problems[] = "$rel: missing H1 on first line";
        }
    }
    if (str_starts_with($rel, 'docs'.DIRECTORY_SEPARATOR) && preg_match('#^docs[\\\\/]\d\d-#', $rel) && ! str_contains(substr($text, 0, 600), '**Audience:**')) {
        $problems[] = "$rel: missing Audience header";
    }

    foreach ($secretPatterns as $name => $re) {
        if (preg_match($re, $text, $sm)) {
            $problems[] = "$rel: possible secret ($name): ".substr($sm[0], 0, 12).'...';
        }
    }

    foreach ($envSecrets as $key => $value) {
        if ($value !== '' && str_contains($text, $value)) {
            $problems[] = "$rel: contains the real value of $key from .env";
        }
    }

    // Mermaid basics.
    preg_match_all('/```mermaid\s*\n(.*?)```/s', $text, $mer);
    foreach ($mer[1] as $i => $block) {
        $first = trim(strtok($block, "\n"));
        if (! preg_match('/^(flowchart|graph|sequenceDiagram|erDiagram|stateDiagram-v2|stateDiagram|classDiagram|gantt|journey|pie|mindmap|timeline)\b/', $first)) {
            $problems[] = "$rel: mermaid block ".($i + 1)." starts with '$first'";
        }
        if (substr_count($block, '[') !== substr_count($block, ']') && ! str_starts_with($first, 'sequenceDiagram')) {
            $problems[] = "$rel: mermaid block ".($i + 1).' has unbalanced [ ]';
        }
        if (substr_count($block, '"') % 2 !== 0) {
            $problems[] = "$rel: mermaid block ".($i + 1).' has an odd number of quotes';
        }
        if (preg_match('/<(?!br\s*\/?>)[a-z]+[^>]*>/i', $block, $hm)) {
            $problems[] = "$rel: mermaid block ".($i + 1)." contains HTML {$hm[0]}";
        }
    }

    // Links (outside code blocks and inline code).
    $plain = preg_replace('/```.*?```/s', '', $text);
    $plain = preg_replace('/`[^`\n]*`/', '', $plain);
    preg_match_all('/\[[^\]]*\]\(([^)\s]+)(?:\s+"[^"]*")?\)/', $plain, $links);
    foreach ($links[1] as $link) {
        if (preg_match('#^(https?:|mailto:|tel:)#', $link)) {
            continue;
        }
        [$path, $anchor] = array_pad(explode('#', $link, 2), 2, null);
        $target = $path === '' ? $file : dirname($file).'/'.urldecode($path);
        $real = realpath($target);
        if ($real === false) {
            $problems[] = "$rel: broken link $link";

            continue;
        }
        if ($anchor !== null && $anchor !== '' && str_ends_with($real, '.md')) {
            if (! in_array(strtolower($anchor), $anchors[$real] ?? [], true)) {
                $problems[] = "$rel: missing anchor #$anchor in ".substr($real, strlen($root) + 1);
            }
        }
    }

    // Source paths mentioned in inline code, e.g. `app/Services/GalleryService.php`.
    preg_match_all('/`((?:app|config|routes|database|resources|tests|scripts|docker|bootstrap|public|\.github)\/[A-Za-z0-9_\/.\-{},*]+)`/', $text, $paths);
    foreach (array_unique($paths[1]) as $p) {
        if (preg_match('/[{}*]/', $p)) {
            continue;
        }
        $p = rtrim($p, '.,:;');
        if (! file_exists($root.'/'.$p)) {
            $problems[] = "$rel: referenced path not found: $p";
        }
    }
}

echo count($files)." files checked\n";
echo count($problems)." problems\n";
foreach ($problems as $p) {
    echo $p."\n";
}
