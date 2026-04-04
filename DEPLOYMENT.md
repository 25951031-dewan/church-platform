# Automated Deployment - Quick Start

## 🚀 Files Created

I've set up **3 automated deployment methods** for your Church Platform:

### Method 1: GitHub Webhook (Recommended)
- **File:** `deploy-webhook.php`
- **Auto-deploys** when you push to GitHub
- **Most reliable** for shared hosting

### Method 2: Manual Browser Trigger
- **File:** `deploy-manual.php`
- **One-click** deployment from browser
- **Great for** testing and manual updates

### Method 3: Cron Job Scheduler
- **File:** `cron-deploy.sh`
- **Scheduled** automatic updates
- **Best for** regular sync without webhooks

---

## ⚡ Quick Setup (5 Minutes)

### Step 1: Generate Secret Token
```bash
openssl rand -hex 32
```
Copy the output (e.g., `a1b2c3d4e5f6...`)

### Step 2: Update .env on Shared Hosting
SSH into your server:
```bash
ssh username@yourserver.com
cd /path/to/church
nano .env
```

Add this line:
```env
DEPLOY_SECRET=paste_your_token_here
```
Save and exit (Ctrl+X, Y, Enter)

### Step 3: Upload Files to Shared Hosting
Upload these files from your project to shared hosting:
```
deploy-webhook.php  → public/deploy-webhook.php
deploy-manual.php   → public/deploy-manual.php
cron-deploy.sh      → cron-deploy.sh
```

### Step 4: Set Permissions
```bash
chmod +x deploy.sh cron-deploy.sh
chmod -R 775 storage bootstrap/cache
```

### Step 5: Configure GitHub Webhook
1. Go to: https://github.com/25951031-dewan/church-platform/settings/hooks
2. Click **Add webhook**
3. Fill in:
   - Payload URL: `https://yourdomain.com/deploy-webhook.php`
   - Content type: `application/json`
   - Secret: (your DEPLOY_SECRET from .env)
   - Events: "Just the push event"
4. Click **Add webhook**

### Step 6: Test It! 🎉
```bash
# On your local machine
git commit --allow-empty -m "Test auto-deployment"
git push origin v5-foundation
```

Watch it deploy automatically! Check logs:
```bash
tail -f storage/logs/deploy.log
```

---

## 📖 Full Documentation

See complete guide: `automated-deployment-guide.md`

---

## 🔧 Manual Deployment

Anytime you need to deploy manually:

**Via Browser:**
```
https://yourdomain.com/deploy-manual.php?secret=your_secret
```

**Via SSH:**
```bash
bash deploy.sh
```

---

## 📊 Monitoring

### View deployment logs:
```bash
tail -50 storage/logs/deploy.log
```

### View Laravel logs:
```bash
tail -50 storage/logs/laravel.log
```

---

## ✅ Next Steps

1. ✅ Commit these files to Git
2. ✅ Upload to shared hosting  
3. ✅ Add DEPLOY_SECRET to .env
4. ✅ Configure GitHub webhook
5. ✅ Test with a push

---

## 🆘 Troubleshooting

**Webhook not working?**
- Check GitHub webhook delivery logs
- Verify DEPLOY_SECRET matches in .env and GitHub
- Check storage/logs/deploy.log

**Deployment fails?**
- Run `bash deploy.sh` manually to see errors
- Check PHP version: `php -v` (needs 8.3+)
- Check permissions: `ls -la storage`

**Still stuck?**
- Check complete guide in `automated-deployment-guide.md`
- View logs: `tail -f storage/logs/laravel.log`

---

**Repository:** https://github.com/25951031-dewan/church-platform  
**Branch:** v5-foundation
