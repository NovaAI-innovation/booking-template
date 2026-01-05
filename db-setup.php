<?php
/**
 * Database Setup Script
 * Run once to create database tables
 *
 * Usage: php db-setup.php
 */

require_once 'config.php';

echo "========================================\n";
echo " Database Setup for Booking Template\n";
echo "========================================\n\n";

try {
    // Check if database file already exists
    if (file_exists(DB_PATH)) {
        echo "⚠️  Database file already exists at: " . DB_PATH . "\n";
        echo "Do you want to proceed? This will create tables if they don't exist. (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'y' && trim($line) !== 'Y') {
            echo "Setup cancelled.\n";
            exit(0);
        }
        fclose($handle);
    }

    echo "📦 Connecting to database...\n";
    $pdo = getDbConnection();
    echo "✓ Database connection established\n\n";

    echo "📝 Reading schema from schema.sql...\n";
    $schema = file_get_contents(__DIR__ . '/schema.sql');

    if ($schema === false) {
        throw new Exception("Could not read schema.sql file");
    }
    echo "✓ Schema file loaded\n\n";

    echo "🔨 Creating tables...\n";
    $pdo->exec($schema);
    echo "✓ Tables created successfully\n\n";

    // Verify tables were created
    echo "🔍 Verifying table creation...\n";
    $tables = ['users', 'gallery_purchases', 'tips', 'stripe_webhook_events'];
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "  ✓ Table '$table' exists\n";
        } else {
            echo "  ✗ Table '$table' NOT FOUND\n";
        }
    }

    echo "\n========================================\n";
    echo "✅ Database setup completed successfully!\n";
    echo "========================================\n\n";

    echo "Database location: " . DB_PATH . "\n";
    echo "Tables created: " . count($tables) . "\n\n";

    echo "Next steps:\n";
    echo "1. Update Stripe API keys in config.php\n";
    echo "2. Implement user-api.php for authentication\n";
    echo "3. Implement payment-api.php for Stripe integration\n";
    echo "4. Test the system\n\n";

} catch (PDOException $e) {
    echo "\n❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
