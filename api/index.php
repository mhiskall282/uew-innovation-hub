<?php

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
| Vercel functions have a read-only file system except /tmp, so Laravel's
| storage and bootstrap caches are redirected there before the normal
| public/index.php front controller runs. Persistent data must live in an
| external database (DB_URL) and documents in S3-compatible storage
| (DOCUMENTS_DISK=s3). See docs/deployment.md.
*/

$storage = '/tmp/storage';

foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/private'] as $dir) {
    if (! is_dir("{$storage}/{$dir}")) {
        @mkdir("{$storage}/{$dir}", 0755, true);
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $storage;

require __DIR__.'/../public/index.php';
