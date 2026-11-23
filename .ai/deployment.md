# Deployment & DevOps Conventions

> **Platform**: UEW Innovation Hub Enterprise  
> **Server Target**: Ubuntu LTS / Debian / Windows Server (PHP 8.4+, Nginx / Apache, MySQL 8.0+)  

---

## 1. Environment Architecture

1. **Local Development**:
   - `APP_ENV=local`, `APP_DEBUG=true`
   - Local MySQL or SQLite
   - Vite hot module reloading (`npm run dev`)
2. **Staging / Institutional UAT**:
   - `APP_ENV=staging`, `APP_DEBUG=false`
   - Production clone database
   - Verified automated regression suites
3. **Production**:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - Managed MySQL 8.0+ with daily automated backups
   - Redis or database-driven queues and cache
   - Optimized asset bundle (`npm run build`)

---

## 2. Standard Atomic Deployment Script

```bash
#!/usr/bin/env bash
set -e

echo "Deploying UEW Innovation Hub..."

# 1. Put application into maintenance mode with secret bypass
php artisan down --secret="uew-inno-hub-deploy-bypass" --retry=60

# 2. Pull latest code
git pull origin main

# 3. Install production PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Compile frontend assets
npm ci
npm run build

# 5. Run database migrations safely
php artisan migrate --force

# 6. Rebuild application caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Ensure private storage symlink & permissions
php artisan storage:link

# 8. Restart background queues (if active)
php artisan queue:restart

# 9. Bring application back online
php artisan up

echo "Deployment complete."
```

---

## 3. Server Security & Execution Lockdown

### Nginx Storage Protection Snippet
```nginx
# Deny direct execution of scripts inside storage
location ^~ /storage/ {
    location ~ \.(php|phtml|phar|sh|cgi|py)$ {
        deny all;
        return 404;
    }
}
```

### File Upload Limits (php.ini)
```ini
upload_max_filesize = 30M
post_max_size = 35M
memory_limit = 256M
max_execution_time = 120
session.upload_progress.enabled = 0
```
