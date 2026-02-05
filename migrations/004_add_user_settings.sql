-- Migration 004: Add user settings table for preferences and theme
-- Tracks user preferences for theme, privacy, notifications, and appearance

CREATE TABLE IF NOT EXISTS user_settings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    theme ENUM('light', 'dark') DEFAULT 'light',
    
    -- Chat & Privacy Settings
    read_receipts TINYINT(1) DEFAULT 1,
    typing_indicator TINYINT(1) DEFAULT 1,
    online_status_visible TINYINT(1) DEFAULT 1,
    
    -- Notification Settings
    notifications_enabled TINYINT(1) DEFAULT 1,
    notification_sound TINYINT(1) DEFAULT 1,
    notification_mute_until DATETIME DEFAULT NULL,
    
    -- Appearance Settings
    compact_layout TINYINT(1) DEFAULT 0,
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_theme (theme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
