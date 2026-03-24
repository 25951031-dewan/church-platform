# Church Platform — Fresh Installation Guide

This guide walks you through installing the Church Platform on a new server from scratch.

---

## Requirements

Before you begin, make sure your server meets these requirements:

| Requirement | Minimum |
|---|---|
| PHP | 8.2 or higher |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `zip` |
| MySQL / MariaDB | MySQL 8.0+ or MariaDB 10.6+ |
| Web server | Apache (with `mod_rewrite`) or Nginx |
| Composer | 2.x |
| Node.js | 18+ (for building frontend assets) |
| Git | Any recent version |
| Disk space | Minimum 500 MB |

---

## Option A — Web Installer (Recommended)

The platform includes a 3-step browser-based installer. Use this for shared hosting or cPanel environments.

### Step 1 — Upload Files

1. Download or clone the repository:
   ```bash
   git clone https://github.com/25951031-dewan/church-platform.git
   cd church-platform
   ```

2. Install PHP dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Build the frontend assets (requires Node.js locally):
   ```bash
   npm install
   npm run build
   ```

4. Upload the entire project folder to your server's web root (e.g. `public_html/` or `/var/www/html/`).

   > **cPanel / Shared Hosting:** Upload files so that the `public/` folder maps to your domain's `public_html/`. Your root `.htaccess` handles this automatically.

5. Make sure these directories are writable by the web server:
   ```
   storage/
   bootstrap/cache/
   ```
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

---

### Step 2 — Run the Web Installer

Open your browser and visit:
```
https://yourdomain.com/install/step1
```

You will be guided through 3 steps:

---

#### Step 1 — Requirements Check

The installer checks that all PHP extensions are available.

- ✅ Green items are ready
- ❌ Red items must be fixed before continuing

If any requirement fails, contact your hosting provider or install the missing PHP extension. Common fixes:
- Missing `pdo_mysql` → enable in `php.ini` or via cPanel PHP extensions
- Missing `mbstring` → same as above

Click **Continue** once all requirements pass.

---

#### Step 2 — Database & App Setup

Fill in your database credentials:

| Field | Example | Description |
|---|---|---|
| App Name | `Dewan Labung Church` | Shown in the browser title and emails |
| Database Host | `127.0.0.1` | Usually `localhost` or `127.0.0.1` for shared hosting |
| Database Port | `3306` | Default MySQL port |
| Database Name | `alphaome_dew` | Must already exist — create it in cPanel/phpMyAdmin first |
| Database Username | `alphaome_dew` | MySQL user with full privileges on that database |
| Database Password | `yourpassword` | Leave blank if no password |

> **Important:** Create the database in cPanel (MySQL Databases) **before** this step. The installer does not create the database itself — it only creates tables inside an existing database.

Click **Continue**. The installer will:
1. Test the database connection
2. Write credentials to `.env`
3. Run all database migrations (creates all tables)

---

#### Step 3 — Admin Account & Church Setup

Set up your first administrator and church profile:

| Field | Example |
|---|---|
| Admin Name | `Pastor John` |
| Admin Email | `admin@yourchurch.com` |
| Admin Password | *(min 8 characters)* |
| Church Name | `Grace Community Church` |
| Church Slug | `grace-community` *(auto-generated)* |

Click **Complete Installation**. The installer will:
1. Seed user roles (`admin`, `church_leader`, `member`)
2. Create your admin user account
3. Create the default church page
4. Lock the installer (creates `storage/installed.lock`)
5. Cache routes and config for performance

---

#### Installation Complete

You will see a success screen. Your platform is now live at:
```
https://yourdomain.com
```

Log in with the admin credentials you just created.

> **Note:** The `/install/` routes are permanently disabled once installation completes. To re-run the installer, you would need to delete `storage/installed.lock` — only do this if you want to wipe and restart.

---

## Option B — CLI Installer (VPS / SSH)

For servers where you have SSH access:

```bash
# 1. Clone and install dependencies
git clone https://github.com/25951031-dewan/church-platform.git
cd church-platform
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 2. Copy environment file
cp .env.example .env
php artisan key:generate

# 3. Edit .env with your database credentials
nano .env
# Set: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Set: APP_URL=https://yourdomain.com

# 4. Run the interactive CLI installer
php artisan church:install

# 5. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

The `church:install` command walks you through the same 3 steps interactively in the terminal.

---

## Post-Installation Checklist

After installation, do the following:

- [ ] **Test login** at `https://yourdomain.com` with your admin credentials
- [ ] **Set APP_ENV=production** in `.env` (should already be set by installer)
- [ ] **Configure email** — edit `.env` with your SMTP settings:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.yourprovider.com
  MAIL_PORT=587
  MAIL_USERNAME=you@yourdomain.com
  MAIL_PASSWORD=yourpassword
  MAIL_FROM_ADDRESS=noreply@yourdomain.com
  ```
- [ ] **Set up cron job** for scheduled tasks (event reminders, etc.):
  ```bash
  # Add to crontab (cPanel → Cron Jobs, or crontab -e on VPS)
  * * * * * cd /path/to/church-platform && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] **Configure file storage** — by default uses local disk. For S3, set `FILESYSTEM_DISK=s3` and fill in S3 credentials.
- [ ] **Enable HTTPS** — make sure your SSL certificate is active. The installer sets `APP_URL` to `https://`.

---

## Troubleshooting

### "The page isn't working" / 500 error after installation
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Database connection error
- Double-check credentials in `.env`
- Confirm the database user has `ALL PRIVILEGES` on the database
- On shared hosting, the host is often `127.0.0.1`, not `localhost`

### White screen / blank page
- Check `storage/logs/laravel.log` for the actual error
- Make sure `storage/` and `bootstrap/cache/` are writable

### "Route not found" after install
The installer caches routes on completion. If you see this:
```bash
php artisan route:clear
```

### Sessions not working (logged out immediately)
Check `.env` has these lines:
```
SESSION_DRIVER=database
SESSION_CONNECTION=mysql
```
Then run: `php artisan migrate` (creates the `sessions` table if missing).

---

## File Structure Reference

```
church-platform/
├── public/              ← Document root (point your domain here)
│   ├── index.php
│   ├── .htaccess
│   └── build/           ← Compiled React/CSS assets (must be present)
├── storage/
│   ├── installed.lock   ← Created by installer — locks out /install routes
│   └── logs/laravel.log ← Error logs
├── bootstrap/cache/     ← Cached config/routes
├── plugins/             ← All feature plugins
└── .env                 ← Your environment configuration
```
