# Common ERP – Database Backup & Deployment Guide

## 1. System Requirements & Prerequisites

Common ERP is engineered for enterprise-grade deployment on modern Linux servers (Ubuntu 22.04 LTS / 24.04 LTS, Debian 12, or RHEL 9 recommended).

### 1.1 Software Dependencies
- **PHP:** `^8.2` (Extensions required: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`)
- **Database Engine:** MySQL 8.0+ or MariaDB 10.6+ (InnoDB default storage engine, `utf8mb4_unicode_ci` character set)
- **Web Server:** Nginx 1.22+ or Apache 2.4+ (with `mod_rewrite` enabled)
- **Package Managers:** Composer 2.5+, Node.js 18+ & NPM 9+
- **Process & Cache (Optional / High-Volume):** Redis 6.0+

---

## 2. Production Deployment Procedure

### Step 1: Clone Repository & Install PHP Dependencies
```bash
cd /var/www
git clone <repository_url> common-erp
cd /var/www/common-erp

# Install production dependencies without development packages
composer install --no-dev --optimize-autoloader
```

### Step 2: Environment Configuration
Copy `.env.example` to `.env` and configure production variables:
```bash
cp .env.example .env
php artisan key:generate
```

Configure `.env`:
```ini
APP_NAME="Common ERP"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://erp.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=common_erp_prod
DB_USERNAME=erp_user
DB_PASSWORD=Secure_Enterprise_Password_2026!

SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database
CACHE_DRIVER=file
FILESYSTEM_DISK=public
```

### Step 3: Safe Database Migration
> [!CAUTION]
> **STRICT DATABASE RULE:**
> Never execute `php artisan migrate:fresh`, `migrate:refresh`, or `migrate:reset` on a staging or production database. These commands permanently drop all existing multi-company business records, charts of accounts, and ledger history.

Execute safe forward migrations:
```bash
php artisan migrate --force
```

### Step 4: Storage Symlink & File Permissions
```bash
php artisan storage:link

# Assign ownership to the web server user (www-data)
sudo chown -R www-data:www-data /var/www/common-erp
sudo find /var/www/common-erp -type f -exec chmod 644 {} \;
sudo find /var/www/common-erp -type d -exec chmod 755 {} \;

# Storage and cache must be writable by www-data
sudo chmod -R 775 /var/www/common-erp/storage
sudo chmod -R 775 /var/www/common-erp/bootstrap/cache
```

### Step 5: Production Optimization & Caching
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. Web Server Configuration

### 3.1 Nginx Server Block (`/etc/nginx/sites-available/common-erp.conf`)
```nginx
server {
    listen 80;
    server_name erp.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name erp.yourdomain.com;
    root /var/www/common-erp/public;

    ssl_certificate /etc/letsencrypt/live/erp.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/erp.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    index index.php index.html;
    charset utf-8;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 4. Enterprise Database Backup Strategy

### 4.1 Automated Daily Backup Script (`/usr/local/bin/backup-common-erp.sh`)
Create the executable script:
```bash
sudo nano /usr/local/bin/backup-common-erp.sh
sudo chmod +x /usr/local/bin/backup-common-erp.sh
```

Script contents:
```bash
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/var/backups/common-erp"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="common_erp_prod"
DB_USER="erp_backup_user"
DB_PASS="SecureBackupPassword_2026!"
RETENTION_DAYS=30

mkdir -p "${BACKUP_DIR}"

BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sql.gz"

echo "[$(date)] Starting MySQL backup for ${DB_NAME}..."

# Execute transaction-safe mysqldump with single transaction to avoid table locks
mysqldump \
  --user="${DB_USER}" \
  --password="${DB_PASS}" \
  --host="127.0.0.1" \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8mb4 \
  "${DB_NAME}" | gzip -9 > "${BACKUP_FILE}"

echo "[$(date)] Backup completed: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"

# Purge backups older than retention period
find "${BACKUP_DIR}" -name "${DB_NAME}_*.sql.gz" -type f -mtime +"${RETENTION_DAYS}" -delete
echo "[$(date)] Archived backups older than ${RETENTION_DAYS} days removed."
```

### 4.2 Automated Cron Scheduling
Configure crontab for `root`:
```bash
# Run backup nightly at 01:00 AM
0 1 * * * /usr/local/bin/backup-common-erp.sh >> /var/log/erp_backup.log 2>&1
```

### 4.3 Database Restoration Procedure
In the event of disaster recovery:
```bash
# Decompress and restore dump into MySQL instance
gunzip < /var/backups/common-erp/common_erp_prod_YYYYMMDD_HHMMSS.sql.gz | mysql -u root -p common_erp_prod

# Re-run optimizations
php artisan config:cache
php artisan route:cache
```

---

## 5. Deployment Verification Checklist

- [ ] `.env` has `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] MySQL user privileges restricted (no raw schema modification privileges for application runtime user).
- [ ] HTTPS enforced with valid SSL/TLS certificate.
- [ ] Daily backup cron job scheduled and verified with a test restore.
- [ ] All 67 automated test cases pass (`php artisan test`).
- [ ] Active demo companies verified via Super Admin company switch dashboard.
