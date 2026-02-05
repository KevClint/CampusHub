-- Migration 003: Add message status, typing indicator, and online status tracking
-- Adds delivery & seen status for messages
-- Adds typing indicator system
-- Adds online/offline status tracking

-- Add status field to messages table
ALTER TABLE messages 
ADD COLUMN status ENUM('sent', 'delivered', 'seen') DEFAULT 'sent' AFTER is_deleted,
ADD COLUMN seen_at DATETIME DEFAULT NULL AFTER status,
ADD INDEX idx_status (status);

-- Create typing_indicator table for tracking who is typing
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update users table to track last activity for online status
ALTER TABLE users 
ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP AFTER last_seen,
ADD INDEX idx_last_activity (last_activity);
