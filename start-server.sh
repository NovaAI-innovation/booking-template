#!/bin/bash
# Gallery Paywall System - Server Initialization Script
# Handles Composer dependencies, database setup, and server startup

set -e  # Exit on error

echo "========================================"
echo " Gallery Paywall System - Starting Up"
echo "========================================"
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} PHP detected: $(php -v | head -n 1)"
echo ""

# Step 1: Check and install Composer dependencies
echo "Step 1: Checking Composer dependencies..."
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}⚠${NC} Vendor directory not found. Installing Composer dependencies..."

    if ! command -v composer &> /dev/null; then
        echo -e "${RED}Error: Composer is not installed${NC}"
        echo "Please install Composer or manually run: composer install"
        exit 1
    fi

    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✓${NC} Composer dependencies installed"
else
    echo -e "${GREEN}✓${NC} Composer dependencies already installed"
fi
echo ""

# Step 2: Check and setup database
echo "Step 2: Checking database setup..."
if [ ! -f "database.sqlite" ]; then
    echo -e "${YELLOW}⚠${NC} Database not found. Creating database..."

    # Create empty database file
    touch database.sqlite
    chmod 664 database.sqlite

    # Run schema setup
    echo "Running database schema setup..."
    php -r "
    require_once 'config.php';
    try {
        \$pdo = getDbConnection();
        \$schema = file_get_contents(__DIR__ . '/schema.sql');
        if (\$schema === false) {
            throw new Exception('Could not read schema.sql');
        }
        \$pdo->exec(\$schema);
        echo 'Database tables created successfully\n';
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Database initialized successfully"
    else
        echo -e "${RED}✗${NC} Database initialization failed"
        exit 1
    fi
else
    echo -e "${GREEN}✓${NC} Database already exists"
fi
echo ""

# Step 3: Verify Stripe configuration
echo "Step 3: Verifying Stripe configuration..."
STRIPE_CHECK=$(php -r "
require_once 'config.php';
if (STRIPE_SECRET_KEY === 'sk_test_YOUR_SECRET_KEY_HERE' ||
    STRIPE_PUBLISHABLE_KEY === 'pk_test_YOUR_PUBLISHABLE_KEY_HERE') {
    echo 'NOT_CONFIGURED';
} else {
    echo 'CONFIGURED';
}
")

if [ "$STRIPE_CHECK" == "NOT_CONFIGURED" ]; then
    echo -e "${YELLOW}⚠${NC} Stripe API keys are using default values"
    echo -e "${YELLOW}⚠${NC} Please update STRIPE_SECRET_KEY and STRIPE_PUBLISHABLE_KEY in config.php"
    echo -e "${YELLOW}⚠${NC} Payment processing will NOT work until configured"
else
    echo -e "${GREEN}✓${NC} Stripe API keys are configured"
fi
echo ""

# Step 4: Check file permissions
echo "Step 4: Checking file permissions..."
if [ -w "database.sqlite" ]; then
    echo -e "${GREEN}✓${NC} Database file is writable"
else
    echo -e "${YELLOW}⚠${NC} Database file may not be writable"
    chmod 664 database.sqlite 2>/dev/null || true
fi
echo ""

# Step 5: Start the server
echo "========================================"
echo " Server Ready - Starting PHP Server"
echo "========================================"
echo ""

# Determine base URLs
BASE_LOCAL="http://localhost:8080"
NETWORK_IP=$(ifconfig 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -n 1)
if [ -n "$NETWORK_IP" ]; then
    BASE_NETWORK="http://$NETWORK_IP:8080"
fi

echo -e "${GREEN}=== Main Pages ===${NC}"
echo "  ${BASE_LOCAL}"
echo "  ${BASE_LOCAL}/book.html"
echo "  ${BASE_LOCAL}/services.html"
echo "  ${BASE_LOCAL}/platforms.html"
echo "  ${BASE_LOCAL}/etiquette.html"
echo "  ${BASE_LOCAL}/gallery.html"
if [ -n "$BASE_NETWORK" ]; then
    echo ""
    echo -e "${GREEN}Network Access:${NC}"
    echo "  ${BASE_NETWORK} (and all pages above)"
fi

echo ""
echo -e "${GREEN}=== Admin Pages ===${NC}"
echo "  ${BASE_LOCAL}/admin-login.php"
echo "  ${BASE_LOCAL}/admin-dashboard.php"

echo ""
echo -e "${GREEN}=== API Endpoints ===${NC}"
echo "  ${BASE_LOCAL}/user-api.php"
echo "  ${BASE_LOCAL}/payment-api.php"
echo "  ${BASE_LOCAL}/admin-api.php"
echo "  ${BASE_LOCAL}/stripe-webhook.php"

echo ""
echo -e "${YELLOW}Features enabled:${NC}"
echo "  • User registration & authentication"
echo "  • Gallery with paywall (\$19.99)"
echo "  • Stripe payment processing"
echo "  • Tipping system"
echo "  • Admin dashboard (login required)"
echo ""
echo -e "${GREEN}Press Ctrl+C to stop the server${NC}"
echo ""

# Start PHP built-in server
php -S 0.0.0.0:8080
