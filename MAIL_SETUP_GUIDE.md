# PHP Mail Setup Guide (XAMPP)

This guide explains how to configure your local XAMPP environment to send emails using the Kingsman Hotel system.

---

## 1. Configure `php.ini`
Open your `php.ini` file (usually found in `C:\xampp\php\php.ini`) and search for the `[mail function]` section.

Update the following settings:
- **`SMTP`**: set to your SMTP server (e.g., `smtp.gmail.com`).
- **`smtp_port`**: set to `587` (for TLS) or `465` (for SSL).
- **`sendmail_path`**: `"\"C:\xampp\sendmail\sendmail.exe\" -t"`

---

## 2. Configure `sendmail.ini`
Open your `sendmail.ini` file (usually found in `C:\xampp\sendmail\sendmail.ini`).

Update the following settings:
- **`smtp_server`**: same as in `php.ini`.
- **`smtp_port`**: same as in `php.ini`.
- **`auth_username`**: your email address.
- **`auth_password`**: your email password or App Password.
- **`force_sender`**: your email address.

---

## 3. Project Dependencies
Since the `vendor/` directory is ignored in Git, you must install dependencies manually if they are missing:

```bash
composer install
```

---

## 4. System Integration
The project uses `includes/mail_helper.php` which executes the native `mail()` function. This function relies on the `php.ini` and `sendmail.ini` configurations mentioned above.

> [!TIP]
> If using Gmail, you MUST use an **App Password** if 2-Step Verification is enabled on your account.
