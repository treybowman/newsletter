# 📬 Flarum Newsletter System

A lightweight, self-hosted PHP solution to send automated HTML newsletters using Atom feeds from your [Flarum](https://flarum.org) community. Now powered by a centralized `config.php` for easy setup and customization.

---

## 🔧 Features

- Pulls newest and top discussions from Atom feeds
- Generates branded HTML email with your site logo
- Sends personalized emails to all users with valid emails
- Logs opens using pixel tracking
- Centralized `config.php` to manage site info, DB access, and more
- Test mode to preview emails without spamming your users
- Secure unsubscribe via one-time token system
- Cron-friendly automation

---

## 🗃 Folder Structure

```
/newsletter/
├── config.php
├── run_newsletter.php
├── unsubscribe.php
├── open.php
├── log_viewer.php
├── index.php (optional archive viewer)
├── /tokens/ (auto-generated, requires write access)
├── /newsletters/ (stores HTML snapshots)
```

---

## ⚙️ Setup Instructions

### 1. 🔌 Install Syndication Extension

Make sure you have the [Syndication](https://discuss.flarum.org/d/27687-syndication-rss-atom-feeds) extension enabled.

### 2. 🧱 Create Required Tables

```sql
CREATE TABLE newsletter_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  open_token VARCHAR(64) NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  opened BOOLEAN DEFAULT FALSE
);

CREATE TABLE unsubscribed_emails (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE
);
```

### 3. ⚙️ Create `config.php`

```php
<?php
return [
    'db_host'         => 'localhost',
    'db_name'         => 'your_database',
    'db_user'         => 'your_user',
    'db_pass'         => 'your_password',
    'log_token'       => 'runDaily2025',
    'site_name'       => 'CobbTalk',
    'site_url'        => 'https://cobbtalk.com',
    'users_table'     => 'ct_users',
    'settings_table'  => 'ct_settings',
    'test_mode'       => false,
    'test_email'      => 'your@email.com'
];
```

### 4. 🔐 Protect `/tokens/` Folder

Create a `.htaccess` in `/tokens/` with:

```
Deny from all
```

Or for newer Apache:

```
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
```

---

## 🧪 Test It

Run in your browser:

```
https://YourSite.com/newsletter/run_newsletter.php?key=YOURPPRIVATEKEY
```

- Only sends to `test_email` if `test_mode` is enabled
- Saves a copy of the email to `/newsletters/`

---

## ⏰ Automate It (Cron Job)

Send every Thursday at 8:15 AM ET:

```
15 12 * * 4 curl -s 'https://YourSite.com/newsletter/run_newsletter.php?key=YOURPPRIVATEKEY' > /dev/null 2>&1
```

---

## 📊 View Logs

Go to:

```
https://YourSite.com/newsletter/log_viewer.php?token=YOURPPRIVATEKEY
```

You’ll see:
- Total sends
- Total opens
- Open rate
- Last 50 sends

---

## 🛑 Unsubscribe

Each email includes:

```
https://YourSite.com/newsletter/unsubscribe.php?email=user@example.com&token=abcdef123456
```

Unsubscribe:
- Validates the token
- Adds email to `unsubscribed_emails`
- Deletes the token file
- Sends a confirmation

---

## ✅ Done!

You now have a fully working, token-secured, auto-sending Flarum newsletter system.

---

MIT Licensed  
Built by [TreyB](https://treyb.com)
