-- Database schema for user authentication, gallery paywall, and tipping system
-- SQLite database

-- Users table (with date_of_birth field for tippers display)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    is_active BOOLEAN DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_email ON users(email);

-- Gallery access purchases
CREATE TABLE IF NOT EXISTS gallery_purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_payment_intent_id VARCHAR(255) UNIQUE NOT NULL,
    stripe_checkout_session_id VARCHAR(255),
    amount_paid INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'usd',
    purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'completed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_gallery_user_id ON gallery_purchases(user_id);
CREATE INDEX IF NOT EXISTS idx_gallery_payment_intent ON gallery_purchases(stripe_payment_intent_id);

-- Tips/donations table
CREATE TABLE IF NOT EXISTS tips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_payment_intent_id VARCHAR(255) UNIQUE NOT NULL,
    stripe_checkout_session_id VARCHAR(255),
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'usd',
    message TEXT,
    is_anonymous BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'completed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_tips_user_id ON tips(user_id);
CREATE INDEX IF NOT EXISTS idx_tips_created_at ON tips(created_at);
CREATE INDEX IF NOT EXISTS idx_tips_payment_intent ON tips(stripe_payment_intent_id);

-- Stripe webhook events log (for idempotency)
CREATE TABLE IF NOT EXISTS stripe_webhook_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stripe_event_id VARCHAR(255) UNIQUE NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    payload TEXT
);

CREATE INDEX IF NOT EXISTS idx_stripe_event_id ON stripe_webhook_events(stripe_event_id);
