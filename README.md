# 📬 CobbTalk Newsletter System

A self-hosted, token-secured PHP system that automatically sends weekly HTML digests to Flarum forum users, with personalized tracking and one-click unsubscribe functionality.

Built specifically for [CobbTalk.com](https://cobbtalk.com), but easily adaptable for any [Flarum](https://flarum.org) community.

---

## 🔧 Key Features

- 🧠 Automatically pulls top + newest discussions from Atom feeds
- 📨 Sends HTML email newsletters to all registered users
- 🧍 Personalized: each email contains the recipient’s username, a tracking token, and secure unsubscribe link
- 🔒 Fully opt-out compliant with one-time unsubscribe token system
- 📈 Tracks email opens via pixel
- 🗃 Logs all sends and opens in MySQL
- 🧪 `TEST_MODE` for safe local previews
- 🖼️ Dynamically pulls logo path from Flarum settings
- 🛡️ Centralized `config.php` for all credentials and access control

---

## 📁 File Overview

| File                | Purpose                                                 |
|---------------------|---------------------------------------------------------|
| `config.php`        | Stores DB credentials and access token                  |
| `run_newsletter.php`| Builds + sends digest emails and logs sends            |
| `unsubscribe.php`   | Handles unsubscribe securely via per-user token         |
| `open.php`          | Tracks when emails are opened via pixel                |
| `log_viewer.php`    | Displays latest sends and open rate (token protected)   |
| `tokens/`           | Stores one-time unsubscribe tokens                      |
| `newsletters/`      | Saves HTML copies of each newsletter                    |

---

## ✅ Initial Setup

1. Place all files inside `/newsletter/` directory
2. Manually create:
   - `/tokens/` — writable by PHP
   - `/newsletters/` — writable by PHP
3. Create `config.php` like below:

```php
<?php
return [
  'db_host'   => 'localhost',
  'db_name'   => 'your_database',
  'db_user'   => 'your_user',
  'db_pass'   => 'your_password',
  'log_token' => 'yourSecretKey123'
];
```

4. Create required database tables:

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

---

## 🧪 Testing

1. In `run_newsletter.php`:
```php
define('TEST_MODE', true);
define('TEST_EMAIL', 'your@email.com');
```

2. Visit in your browser:

```
https://YourSite.com/newsletter/run_newsletter.php?key=yourSecretKey123 (Configred in Config.php)
```

- Only `TEST_EMAIL` will receive the email
- A `.txt` token file will be created in `/tokens/`
- Output saved to `/newsletters/`

---

## 🛠 Cron Job

To automate weekly sends (e.g., Thursdays at 8:15am ET):

```
15 12 * * 4 curl -s "https://YourSite.com/newsletter/run_newsletter.php?key=yourSecretKey123" > /dev/null 2>&1
```

---

## 📊 View Logs

```
https://YourSite.com/newsletter/log_viewer.php?token=yourSecretKey123
```

Stats:
- Total sent
- Total opens
- Open rate
- 50 most recent logs with status

---

## 🛑 Unsubscribe System

Each email includes:

```
https://YourSite.com/newsletter/unsubscribe.php?email=user@example.com&token=xxxxxx
```

Unsubscribing:
- Checks one-time token from `/tokens/`
- Adds user to `unsubscribed_emails`
- Deletes token file
- Sends confirmation email

---

## 🖼️ Logo Integration

Email header pulls your forum logo dynamically from:

```
ct_settings → logo_path
```

No need to hardcode image URLs.

---

## 🔐 Security Tips

- Use a strong `log_token` in `config.php`
- Lock down `/tokens/` with `.htaccess`:
```
Deny from all
```
- Use HTTPS and disable directory indexing

---

## 👨‍💻 Built By

**Trey Bowman** for [CobbTalk.com](https://cobbtalk.com)  
MIT Licensed — fork, remix, and deploy freely.
