# DevSecOps checks for this repository. Read-only: reports, changes nothing.
Set-Location (Split-Path $PSScriptRoot -Parent)
$ErrorActionPreference = 'Continue'
function Section($t) { "`n=== $t" }

Section 'Dependencies: known vulnerabilities'
composer audit --locked --no-interaction 2>&1 | Select-Object -Last 2
npm audit 2>&1 | Select-Object -Last 2

Section 'Supply chain: npm registry signatures and composer lock integrity'
npm audit signatures 2>&1 | Select-Object -Last 4
composer validate --no-check-publish --no-interaction 2>&1 | Select-Object -Last 2
composer check-platform-reqs --lock --no-interaction 2>&1 | Select-String -Pattern 'missing|failed' | Select-Object -First 5
"abandoned packages:"; composer audit --locked --abandoned=report --format=plain 2>&1 | Select-String -Pattern 'abandoned' | Select-Object -First 5

Section 'Unexpected executable files where uploads or assets live'
$php = Get-ChildItem public, storage -Recurse -File -Include *.php, *.phtml, *.phar, *.php5, *.php7, *.cgi, *.pl, *.sh, *.exe, *.dll -ErrorAction SilentlyContinue |
    Where-Object { $_.FullName -notmatch '\\storage\\framework\\views\\' -and $_.FullName -notmatch '\\public\\index\.php$' -and $_.FullName -notmatch '\\storage\\[^\\]+\.(ps1|php)$' }
if ($php) { $php | ForEach-Object { "  FOUND: $($_.FullName)" } } else { '  none (only public/index.php)' }

Section 'Web-shell / backdoor patterns in first-party code (app, routes, config, bootstrap, public, resources, database, tests, scripts)'
$hits = git grep -n -I -E 'eval\s*\(\s*(base64_decode|gzinflate|str_rot13|\$_(GET|POST|REQUEST|COOKIE))|(system|exec|shell_exec|passthru|popen|proc_open)\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)|assert\s*\(\s*\$_|preg_replace\s*\(.*/e[''"]|create_function\s*\(|\$\{\s*\$_(GET|POST)' -- app routes config bootstrap public resources database tests scripts
if ($hits) { $hits } else { '  none' }

Section 'Dangerous functions used anywhere in app/ (review list)'
$uses = git grep -n -I -E '\b(exec|shell_exec|system|passthru|proc_open|popen|unserialize|eval)\s*\(' -- app
if ($uses) { $uses } else { '  none' }

Section 'Raw SQL with variable interpolation in app/ (SQL injection review)'
$raw = git grep -n -I -E '(DB::(select|statement|unprepared)|->(whereRaw|selectRaw|orderByRaw|havingRaw|groupByRaw)|DB::raw)\s*\(\s*"[^"]*\$' -- app
if ($raw) { $raw } else { '  none with interpolated variables' }

Section 'Secrets in tracked files'
$sec = git grep -n -I -E '(AKIA[0-9A-Z]{16}|-----BEGIN [A-Z ]*PRIVATE KEY|ghp_[A-Za-z0-9]{36}|github_pat_[A-Za-z0-9_]{40,}|xox[baprs]-[A-Za-z0-9-]{10,}|sk_live_[A-Za-z0-9]{20,}|AIza[0-9A-Za-z_-]{35}|MAIL_PASSWORD=[^\s"]{4,}|APP_KEY=base64:[A-Za-z0-9+/=]{40,})' -- . ':!composer.lock' ':!package-lock.json'
if ($sec) { $sec } else { '  none' }

Section 'Secrets anywhere in git history (all commits)'
$hist = git log --all -p --no-color -G '(AKIA[0-9A-Z]{16}|-----BEGIN [A-Z ]*PRIVATE KEY|ghp_[A-Za-z0-9]{36}|MAIL_PASSWORD=[^\s"]{6,}|APP_KEY=base64:[A-Za-z0-9+/=]{40,})' --format='commit %h %s' 2>$null |
    Select-String -Pattern '^commit |^\+.*(AKIA[0-9A-Z]{16}|-----BEGIN [A-Z ]*PRIVATE KEY|ghp_[A-Za-z0-9]{36}|MAIL_PASSWORD=[^\s"]{6,}|APP_KEY=base64:[A-Za-z0-9+/=]{40,})'
if ($hist) { $hist | ForEach-Object { $_.Line.Substring(0, [Math]::Min(140, $_.Line.Length)) -replace '(=|:)[A-Za-z0-9+/=_-]{8,}', '$1<redacted>' } } else { '  none' }
"previously committed tester password still in history: " + [bool](git log --all -S 'Uew-H5KM' --format=%h 2>$null)

Section 'Files that must never be tracked'
$bad = git ls-files | Select-String -Pattern '(^|/)\.env$|\.env\.(local|production|backup)$|\.sqlite$|\.pem$|\.key$|id_rsa|\.p12$|\.pfx$|\.log$'
if ($bad) { $bad } else { '  none' }
'.env ignored: ' + [bool](git check-ignore .env)

Section 'Repository integrity'
git fsck --no-progress 2>&1 | Select-Object -First 5
'remote: ' + (git remote get-url origin)
'unpushed commits: ' + (git log --oneline origin/main..HEAD | Measure-Object).Count

Section 'Production configuration'
'render.yaml APP_DEBUG: ' + ((Select-String render.yaml -Pattern 'key: APP_DEBUG' -Context 0,1).Context.PostContext -join '').Trim()
'php.ini display_errors: ' + ((Select-String docker/php.ini -Pattern '^display_errors').Line)
'php.ini expose_php: ' + ((Select-String docker/php.ini -Pattern '^expose_php').Line)
if (Select-String docker/entrypoint.sh -Pattern 'runuser -u www-data' -Quiet) { 'Container: queue worker runs as www-data; Apache workers run as www-data' } else { 'Container: check user' }
