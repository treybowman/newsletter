> **📝 NOTE:** This README uses `YourSite` and `YourSite.com` as placeholders.
> After setup, search and replace these with your actual community or domain name.
>

# 📬 Flarum Newsletter Automation

This is a lightweight, self-hosted PHP system to automatically generate and email weekly newsletters from your [Flarum](https://flarum.org) community using Atom feeds.

Built to work with the [Syndication extension](https://discuss.flarum.org/d/27687-syndication-rss-atom-feeds).

---

## 🔧 Features
- Pulls top and newest discussions via Atom feed
- Generates HTML newsletter (UTF-8 emoji-friendly)
- Sends emails to all users in your `ct_users` table
- Logs and tracks opens with pixel tracking
- Auto-saves each send as a static HTML file
- Secured cron-trigger via secret key
- Test mode and unsubscribe support

## 💡 Ideal for:
- Local forums
- Niche communities
- Hobby or project-based Flarum installs

## 📂 File Overview

| File | Purpose |
|------|---------|
| `run_newsletter.php` | Main newsletter generator & sender |
| `open.php` | Pixel tracking script |
| `log_viewer.php` | Admin view of sends/opens |
| `README.md` | Setup instructions |

---

## ✅ Quick Setup

1. **Enable Atom feeds** with the [Syndication extension](https://discuss.flarum.org/d/27687-syndication-rss-atom-feeds)
2. **Create the database tables** from the README
3. **Edit your secret key** in `run_newsletter.php`
4. **Schedule a cron job** for Thursday 8:15 AM ET:
    ```bash
    15 12 * * 4 curl -s "https://yoursite.com/newsletter/run_newsletter.php?key=yourSecretKey" > /dev/null 2>&1
    ```
5. ✅ Done! Watch the opens come in.

---

## 📫 Made by TreyB
Built for community connection. Fork it, remix it, and make it your own.

MIT Licensed.

---

## 🧩 Step-by-Step Installation Instructions

### 1. 📥 Download the Files

- Download the latest release ZIP from the GitHub repo.
- Extract the contents into a `/newsletter/` folder on your web server.

### 2. 🧱 Create the Database Tables

Run these SQL queries in phpMyAdmin or your MySQL terminal:

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

### 3. 🔑 Set Your Secret Key

In `run_newsletter.php`, edit this line near the top:

```php
$secret = 'yourSecretKeyHere';
```

This protects your script from unauthorized access.

### 4. 🖼️ Customize Your Email Template (Optional)

Edit the HTML output in `run_newsletter.php` if you want to change colors, layout, or branding.

### 5. 🧪 Test Locally Before Sending

Open this in your browser:

```
https://yourforum.com/newsletter/run_newsletter.php?key=yourSecretKey
```

- If `TEST_MODE` is enabled, it only sends to you.
- To print instead of send, comment out `mail(...)` and use `echo`.

### 6. 📅 Set Up a Cron Job in cPanel

In cPanel under **Cron Jobs**, use:

```bash
15 12 * * 4 curl -s "https://yourforum.com/newsletter/run_newsletter.php?key=yourSecretKey" > /dev/null 2>&1
```

This sends the digest every **Thursday at 8:15 AM Eastern** (adjust time if needed).

### 7. 📊 View Logs and Opens

Visit:

```
https://yourforum.com/newsletter/log_viewer.php
```

Here you'll see:
- Total emails sent
- Total opens
- Open rate
- Most recent logs

### 8. 🛑 Unsubscribe Handling (Optional)

Add emails to the `unsubscribed_emails` table to exclude users from future sends.

---

✅ That’s it! You now have a fully working, automated, and self-hosted Flarum newsletter system.


---

## 🔐 Configuring Database Access

In `run_newsletter.php`, you'll find the section that connects to your MySQL database. Be sure to replace the placeholders with your own credentials:

```php
$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPass = 'your_database_password';
```

You can usually find these details in your hosting control panel (like cPanel) under **MySQL Databases**.

> ✅ Tip: Make sure the user has SELECT access to `ct_users`, and INSERT access to `newsletter_logs`.

If your Flarum installation uses a table prefix (e.g., `flarum_users`), you’ll need to adjust this query too:

```php
$query = $pdo->query("SELECT username, email FROM ct_users WHERE email IS NOT NULL AND email != ''");
```

Change `ct_users` to match your Flarum user table name.



---

## 🧩 Database Setup in `open.php` and `log_viewer.php`

Just like `run_newsletter.php`, both `open.php` and `log_viewer.php` require a connection to your MySQL database.

Open each file and find the following section (or similar):

```php
$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPass = 'your_database_password';
```

Update these values to match your actual database configuration.

If you're using a different database/table prefix or have renamed your tables, make sure the SQL queries in those files are updated accordingly.

