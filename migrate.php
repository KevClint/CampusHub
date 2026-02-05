<?php
/**
 * Migration Runner
 * Automatically applies pending database migrations
 * Run this once to set up the database schema
 */

require_once 'config.php';

try {
    echo "\n=== Running Database Migrations ===\n\n";
    
    // Migration 003: Message status, typing, online status
    echo "Migration 003: Adding message status, typing indicators, and online status...\n";
    
    // Add status and seen_at to messages table
    $conn->exec("ALTER TABLE messages ADD COLUMN status ENUM('sent', 'delivered', 'seen') DEFAULT 'sent' AFTER is_deleted");
    echo "✓ Added 'status' column to messages\n";
    
    $conn->exec("ALTER TABLE messages ADD COLUMN seen_at DATETIME DEFAULT NULL AFTER status");
    echo "✓ Added 'seen_at' column to messages\n";
    
    $conn->exec("ALTER TABLE messages ADD INDEX idx_status (status)");
    echo "✓ Added index on messages.status\n";
    
    // Create typing_indicators table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS typing_indicators (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            conversation_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            typing TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_conversation_user (conversation_id, user_id),
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_conversation_id (conversation_id),
            INDEX idx_user_id (user_id),
            INDEX idx_updated_at (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created 'typing_indicators' table\n";
    
    // Add last_activity to users table
    $conn->exec("ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP AFTER last_seen");
    echo "✓ Added 'last_activity' column to users\n";
    
    $conn->exec("ALTER TABLE users ADD INDEX idx_last_activity (last_activity)");
    echo "✓ Added index on users.last_activity\n";
    
    echo "\n";
    
    // Migration 004: User settings table
    echo "Migration 004: Adding user settings table for preferences...\n";
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            theme ENUM('light', 'dark') DEFAULT 'light',
            read_receipts TINYINT(1) DEFAULT 1,
            typing_indicator TINYINT(1) DEFAULT 1,
            online_status_visible TINYINT(1) DEFAULT 1,
            notifications_enabled TINYINT(1) DEFAULT 1,
            notification_sound TINYINT(1) DEFAULT 1,
            notification_mute_until DATETIME DEFAULT NULL,
            compact_layout TINYINT(1) DEFAULT 0,
            font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_theme (theme)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created 'user_settings' table\n";
    
    echo "\n✅ All migrations completed successfully!\n\n";
    
} catch (PDOException $e) {
    // Check if columns/tables already exist (expected error if already migrated)
    if (strpos($e->getMessage(), 'Duplicate') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠️  Migration already applied (columns/tables already exist)\n";
        echo "This is normal if you've run migrations before.\n\n";
    } else {
        echo "❌ Migration Error:\n";
        echo $e->getMessage() . "\n\n";
        exit(1);
    }
}
?>

