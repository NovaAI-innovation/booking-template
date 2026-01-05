# Setup and Deployment Guide

## User Authentication, Gallery Paywall & Tipping System

This guide will help you configure and deploy the new authentication, paywall, and tipping features.

---

## 📋 What Was Implemented

### ✅ Complete Features
1. **User Authentication System**
   - User registration with username, email, password, and date of birth
   - Secure login with session management
   - Password hashing with bcrypt
   - CSRF protection
   - 18+ age verification on registration

2. **Gallery Paywall**
   - One-time payment ($19.99) for lifetime gallery access
   - Stripe Checkout integration
   - Automatic access granting via webhooks
   - Gallery content blur effect when locked

3. **Tipping System**
   - Custom tip amounts ($1-$1,000)
   - Optional tip messages
   - Recent tippers display showing:
     - Username
     - Tip amount
     - User's date of birth
     - Date of donation
     - Time of donation
   - Top 10 most recent tippers

4. **Database**
   - SQLite database with 4 tables
   - Secure data storage
   - Foreign key relationships

---

## 🔧 Configuration Steps

### Step 1: Stripe Account Setup

1. **Create Stripe Account**
   - Go to [stripe.com](https://stripe.com) and sign up
   - Complete account verification

2. **Get API Keys (Test Mode)**
   - Navigate to: Dashboard → Developers → API Keys
   - Copy your **Publishable key** (starts with `pk_test_`)
   - Copy your **Secret key** (starts with `sk_test_`)
   - Keep these secure!

3. **Update config.php**
   ```bash
   nano config.php
   ```

   Update these lines:
   ```php
   define('STRIPE_SECRET_KEY', 'sk_test_YOUR_KEY_HERE');
   define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_KEY_HERE');
   ```

4. **Set Up Webhook**
   - In Stripe Dashboard: Developers → Webhooks
   - Click "Add endpoint"
   - Endpoint URL: `https://yourdomain.com/stripe-webhook.php`
     - For local testing with ngrok: `https://YOUR_NGROK_URL/stripe-webhook.php`
   - Events to send: Select `checkout.session.completed`
   - Click "Add endpoint"
   - Copy the **Signing secret** (starts with `whsec_`)

   Update config.php:
   ```php
   define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_SECRET_HERE');
   ```

---

### Step 2: Test the System Locally

1. **Start the PHP Server**
   ```bash
   ./start-server.sh
   ```

   Or manually:
   ```bash
   php -S 0.0.0.0:8000
   ```

2. **Access the Site**
   - Open browser to: `http://localhost:8000`

3. **Test User Registration**
   - Go to Gallery page
   - Click "Register"
   - Fill in all fields (must be 18+)
   - Submit and verify auto-login

4. **Test Gallery Paywall**
   - After logging in, you should see the paywall modal
   - Click "Purchase Gallery Access"
   - Use Stripe test card: `4242 4242 4242 4242`
   - Any future expiry date, any 3-digit CVC
   - Complete payment

5. **Test Webhook (Local Testing with ngrok)**
   ```bash
   # Install ngrok if not installed
   pkg install ngrok

   # Start ngrok tunnel
   ngrok http 8000

   # Use the https URL in Stripe webhook settings
   ```

6. **Test Tipping**
   - Go to home page, scroll to tipping section
   - Enter amount and optional message
   - Complete payment with test card
   - Check recent tippers list updates

---

### Step 3: Database Verification

Check database contents:

```bash
# View users
sqlite3 database.sqlite "SELECT id, username, email, date_of_birth FROM users;"

# View gallery purchases
sqlite3 database.sqlite "SELECT u.username, gp.amount_paid/100 as amount, gp.purchased_at FROM gallery_purchases gp JOIN users u ON gp.user_id = u.id;"

# View tips
sqlite3 database.sqlite "SELECT u.username, u.date_of_birth, t.amount/100 as amount, t.created_at FROM tips t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10;"
```

---

## 🚀 Production Deployment

### Step 1: Switch to Live Stripe Keys

1. In Stripe Dashboard, toggle from Test mode to Live mode
2. Get live API keys
3. Update `config.php` with live keys:
   ```php
   define('STRIPE_SECRET_KEY', 'sk_live_YOUR_KEY');
   define('STRIPE_PUBLISHABLE_KEY', 'pk_live_YOUR_KEY');
   ```

4. Create new webhook for production URL
5. Update webhook secret in config.php

### Step 2: Security Hardening

1. **Enable HTTPS**
   - Get SSL certificate (Let's Encrypt recommended)
   - Update in `config.php`:
     ```php
     ini_set('session.cookie_secure', 1); // Change to 1
     ```

2. **Set Proper File Permissions**
   ```bash
   chmod 600 database.sqlite
   chmod 600 config.php
   chmod 644 *.php
   chmod 755 vendor
   ```

3. **Disable Error Display**
   In `config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', '0'); // Change to 0
   ini_set('log_errors', '1');
   ini_set('error_log', '/path/to/error.log');
   ```

4. **Backup Database**
   ```bash
   # Create backup script
   sqlite3 database.sqlite ".backup backup-$(date +%Y%m%d).sqlite"
   ```

### Step 3: Configure Production Server

If using Apache (.htaccess):
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<FilesMatch "(config\.php|database\.sqlite|composer\.(json|lock))">
    Order allow,deny
    Deny from all
</FilesMatch>
```

If using Nginx:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name yourdomain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /path/to/booking-template;
    index index.html;

    location ~ \.(sqlite|json|lock)$ {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## 🧪 Testing Checklist

### User Authentication
- [ ] Register new user with all required fields
- [ ] Login with username
- [ ] Login with email
- [ ] Logout
- [ ] Invalid credentials show error
- [ ] Duplicate username/email prevented
- [ ] Session persists across page reloads
- [ ] 18+ age requirement enforced

### Gallery Paywall
- [ ] Unauthenticated user sees login modal
- [ ] Authenticated user without access sees paywall
- [ ] Gallery content is blurred when locked
- [ ] Purchase button creates Stripe Checkout
- [ ] Successful payment redirects correctly
- [ ] Webhook processes payment
- [ ] Gallery unlocks after payment
- [ ] Access persists on page reload

### Tipping System
- [ ] Unauthenticated user prompted to login
- [ ] Tip amount validation works
- [ ] Stripe Checkout created successfully
- [ ] Tip recorded in database
- [ ] Recent tippers list displays correctly
- [ ] Tippers list shows username, amount, DOB, date, time
- [ ] List updates after new tip

### Security
- [ ] Passwords are hashed
- [ ] SQL injection attempts blocked
- [ ] CSRF tokens validated
- [ ] Session timeout works
- [ ] Stripe webhook signature verified

---

## 📊 Database Maintenance

### Regular Backups
```bash
# Daily backup (add to cron)
0 2 * * * sqlite3 /path/to/database.sqlite ".backup /backups/db-$(date +\%Y\%m\%d).sqlite"

# Keep last 7 days
find /backups -name "db-*.sqlite" -mtime +7 -delete
```

### View Statistics
```bash
# Total users
sqlite3 database.sqlite "SELECT COUNT(*) FROM users;"

# Total revenue
sqlite3 database.sqlite "SELECT SUM(amount_paid)/100 FROM gallery_purchases WHERE status='completed';"

# Total tips
sqlite3 database.sqlite "SELECT SUM(amount)/100 FROM tips WHERE status='completed';"

# Recent activity
sqlite3 database.sqlite "SELECT 'Registration' as type, username, created_at FROM users UNION ALL SELECT 'Purchase', u.username, gp.purchased_at FROM gallery_purchases gp JOIN users u ON gp.user_id=u.id UNION ALL SELECT 'Tip', u.username, t.created_at FROM tips t JOIN users u ON t.user_id=u.id ORDER BY created_at DESC LIMIT 20;"
```

---

## 🔍 Troubleshooting

### Webhook Not Working
1. Check webhook URL is correct and accessible
2. Verify webhook secret in config.php
3. Check PHP error logs: `tail -f /path/to/error.log`
4. Test webhook with Stripe CLI:
   ```bash
   stripe listen --forward-to localhost:8000/stripe-webhook.php
   stripe trigger checkout.session.completed
   ```

### User Can't Access Gallery After Payment
1. Check if webhook was received:
   ```bash
   sqlite3 database.sqlite "SELECT * FROM stripe_webhook_events ORDER BY processed_at DESC LIMIT 5;"
   ```
2. Check if purchase was recorded:
   ```bash
   sqlite3 database.sqlite "SELECT * FROM gallery_purchases WHERE user_id=X;"
   ```
3. Check PHP error logs

### Database Errors
1. Check file permissions:
   ```bash
   ls -la database.sqlite
   ```
2. Should be writable by web server user
3. Check foreign keys are enabled:
   ```bash
   sqlite3 database.sqlite "PRAGMA foreign_keys;"
   ```

---

## 📁 File Structure

```
booking-template/
├── config.php              # Central configuration
├── database.sqlite         # SQLite database
├── schema.sql             # Database schema
├── db-setup.php          # Database initialization
├── user-api.php          # Authentication endpoints
├── payment-api.php       # Payment & tipping endpoints
├── stripe-webhook.php    # Webhook handler
├── auth.js              # Frontend auth manager
├── gallery-paywall.js   # Gallery access control
├── tipping.js           # Tipping interface
├── gallery.html         # Gallery page (with paywall)
├── index.html           # Home page (with tipping)
├── styles.css           # All styling
└── vendor/              # Stripe PHP SDK
```

---

## 💳 Stripe Test Cards

- **Success**: `4242 4242 4242 4242`
- **Decline**: `4000 0000 0000 0002`
- **Requires Authentication**: `4000 0025 0000 3155`
- **Insufficient Funds**: `4000 0000 0000 9995`

Use any future expiry date and any 3-digit CVC.

---

## 🎯 Key Features Summary

**User Registration Fields:**
- Username (3-50 chars, alphanumeric)
- Email (validated)
- Password (min 8 chars, bcrypt hashed)
- Date of Birth (must be 18+)

**Gallery Access:**
- Price: $19.99 (one-time)
- Payment: Stripe Checkout
- Access: Permanent after purchase

**Tipping:**
- Range: $1.00 - $1,000.00
- Message: Optional, max 500 chars
- Display: Top 10 recent tippers with full details

**Recent Tippers Display Shows:**
1. Username
2. Tip amount
3. User's date of birth
4. Date of donation (YYYY-MM-DD)
5. Time of donation (HH:MM:SS)

---

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review Stripe documentation
3. Check PHP error logs
4. Verify database contents
5. Test with Stripe test mode

---

## 🔐 Security Best Practices

1. ✅ Use HTTPS in production
2. ✅ Keep Stripe keys secure (never commit to git)
3. ✅ Regular database backups
4. ✅ Monitor Stripe webhook logs
5. ✅ Set proper file permissions
6. ✅ Disable error display in production
7. ✅ Use strong passwords for admin
8. ✅ Regularly update dependencies

---

## ✨ Implementation Complete!

All features are now fully implemented and ready for testing. Follow the steps above to configure Stripe and deploy to production.

**Next Steps:**
1. Configure Stripe API keys
2. Test all user flows
3. Set up webhooks
4. Deploy to production
5. Monitor and maintain

Good luck! 🚀
