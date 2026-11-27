// Rebuilds the isolated E2E database with demo data, then serves the app with
// PHP's built-in server so the test environment variables reach Laravel.
import { execFileSync, spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const db = process.env.DB_DATABASE;
if (!db || !db.endsWith('e2e.sqlite')) {
    throw new Error('Refusing to run: DB_DATABASE must point at the isolated e2e.sqlite database.');
}
fs.rmSync(db, { force: true });
fs.writeFileSync(db, '');

const php = process.env.PHP_BINARY ?? 'php';
execFileSync(php, ['artisan', 'migrate:fresh', '--seed', '--force'], { stdio: 'inherit' });
execFileSync(php, ['artisan', 'config:clear'], { stdio: 'inherit' });

// Laravel's router script loads index.php from the current directory, so the
// server must start inside public/ (as `php artisan serve` does).
const router = path.resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
const server = spawn(php, ['-S', `127.0.0.1:${process.env.E2E_PORT}`, router], {
    cwd: path.resolve('public'),
    stdio: 'inherit',
});
const stop = () => server.kill();
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
process.on('exit', stop);
