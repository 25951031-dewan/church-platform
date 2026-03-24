# Church Platform — Update Guide

This guide explains how to update an existing Church Platform installation to a newer version.

> **Important:** Always back up your database and files before updating.

---

## Before You Update

### 1. Back Up Your Database

**cPanel / phpMyAdmin:**
1. Open phpMyAdmin → select your database
2. Click **Export** → Quick → Go
3. Save the `.sql` file somewhere safe

**Via SSH:**
```bash
mysqldump -u DB_USERNAME -p DB_DATABASE > backup_$(date +%Y%m%d).sql
```

### 2. Back Up Your `.env` File

```bash
cp .env .env.backup
```

Your `.env` file contains all your credentials and settings. Keep a copy outside the project folder.

### 3. Note Your Current Version

Check your current version at:
```
https://yourdomain.com/update
```
Or in the admin dashboard sidebar.

---

## Option A — Web Updater (Recommended)

The platform includes a one-click web updater accessible from the admin dashboard.

### Step 1 — Open the Update Dashboard

Visit:
```
https://yourdomain.com/update
```

You will see:
- Your **current version**
- The **latest available version**
- A **"Run Update"** button (if a new version is available)

> The update dashboard is only accessible when logged in as an admin. If you are not logged in, you will be redirected to the login page.

---

### Step 2 — Run the Update

Click **"Run Update"**.

The updater will run these steps in sequence and show live progress:

| Step | What it does |
|---|---|
| **Pull latest code** | Downloads the new version via `git pull` (or zip download if git is unavailable) |
| **Install dependencies** | Runs `composer install --no-dev` to update PHP packages |
| **Run migrations** | Runs `php artisan migrate --force` to add any new database tables/columns |
| **Clear caches** | Runs `config:clear`, `route:clear`, `view:clear`, `cache:clear` |
| **Cache for production** | Runs `config:cache` and `route:cache` for performance |
| **Complete** | ✅ Update done |

Each step shows a ✅ (success) or ❌ (failed) indicator in real time.

---

### Step 3 — After Update

Once all steps show ✅:

1. **Hard refresh** your browser (Ctrl+Shift+R / Cmd+Shift+R) to load new frontend assets
2. **Test your site** — log in, browse the feed, check the admin panel
3. If anything looks wrong, check `storage/logs/laravel.log`

---

## Option B — Manual Update (SSH)

Use this if you have SSH access or if the web updater fails.

### Step 1 — Pull the latest code

```bash
cd /path/to/church-platform

# Save any local changes (if any)
git stash

# Pull latest version
git pull origin main
```

### Step 2 — Update PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### Step 3 — Rebuild frontend assets

If you have Node.js on the server:
```bash
npm install
npm run build
```

If Node.js is **not on the server** (shared hosting):
- Build locally on your computer:
  ```bash
  npm install
  npm run build
  ```
- Upload the `public/build/` folder to the server via FTP/SFTP, replacing the old one.

### Step 4 — Run database migrations

```bash
php artisan migrate --force
```

This safely adds new columns/tables. It does **not** delete any existing data.

### Step 5 — Clear and rebuild caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-cache for production performance
php artisan config:cache
php artisan route:cache
```

### Step 6 — Verify

```bash
# Check no migration errors
php artisan migrate:status

# Check the app loads
curl -I https://yourdomain.com
# Should return: HTTP/2 200
```

---

## Option C — cPanel File Manager (No SSH)

If you only have cPanel access (no SSH, no Git):

1. **Download** the latest release `.zip` from GitHub
2. **Extract** locally on your computer
3. **Run locally:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```
4. **Upload via FTP/cPanel File Manager** — overwrite all files **except:**
   - `.env` ← keep your existing one
   - `storage/` ← keep your uploads and logs
5. **Open phpMyAdmin** and run any new migration SQL manually (found in `plugins/*/database/migrations/`)

> **Tip:** This method is error-prone. Use Option A (web updater) or Option B (SSH) when possible.

---

## Troubleshooting Updates

### Update fails at "Pull latest code" step
The server may not have Git configured. Solution:
- Download the release zip manually and use Option C above, or
- Ask your hosting provider to enable Git

### Update fails at "composer install" step
```bash
# Try with increased memory limit
php -d memory_limit=512M $(which composer) install --no-dev
```

### White screen / 500 error after update
```bash
# Clear everything
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Check error logs
tail -50 storage/logs/laravel.log
```

### "Class not found" error after update
```bash
composer dump-autoload
```

### Database error after update
New migrations may have failed. Run manually:
```bash
php artisan migrate --force
```
If a migration fails due to an existing column, it means the column was already added. You can safely mark it as run:
```bash
php artisan migrate:status        # identify the failed migration
php artisan migrate --pretend     # see what SQL it would run
```

### Frontend looks outdated (old styles/layout)
The browser is serving cached assets. Force a hard refresh:
- **Windows/Linux:** Ctrl + Shift + R
- **Mac:** Cmd + Shift + R

Or clear browser cache entirely.

If the issue persists, the `public/build/` folder may be outdated — rebuild and re-upload it.

---

## Rollback (If Update Goes Wrong)

### Restore database
```bash
# Via SSH
mysql -u DB_USERNAME -p DB_DATABASE < backup_20260323.sql
```

### Restore code
```bash
# Via git
git log --oneline -10          # find the previous version commit hash
git checkout <commit-hash>     # go back to that version
```

### Restore .env
```bash
cp .env.backup .env
php artisan config:clear
```

---

## Version History

| Version | What's new |
|---|---|
| 1.0 | Initial release — Core auth, Feed, Posts, Events, Communities, Pages |
| 1.1 | Web installer + CLI installer + one-click updater |
| 1.2 | Community join approval, sub-pages, post moderation, page insights |

---

## Frequently Asked Questions

**Q: Will updating delete my data?**
No. Migrations only add new tables/columns. Your existing users, posts, events, and church data are never deleted by an update.

**Q: Do I need to re-run the installer after an update?**
No. The installer only runs once (on fresh install). Updates use the updater at `/update`.

**Q: Can I update from any version to the latest?**
Yes. All migrations are cumulative and run in order. You can jump multiple versions safely.

**Q: How often should I update?**
Check the `/update` page regularly. Security fixes should be applied immediately; feature updates can wait for a convenient maintenance window.

**Q: What if I have customizations in the code?**
If you have modified core files (not recommended), use `git stash` before pulling, then `git stash pop` after. Resolve any merge conflicts carefully. Consider putting customizations in a new plugin instead to avoid conflicts with future updates.
