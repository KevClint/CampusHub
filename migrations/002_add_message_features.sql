-- Add fields to support new messaging features
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_unsent TINYINT(1) DEFAULT 0;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS unsent_for_all TINYINT(1) DEFAULT 0;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS file_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS file_size INT DEFAULT NULL;

-- Create pinned_messages table
CREATE TABLE IF NOT EXISTS pinned_messages (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    message_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    pinned_by INT UNSIGNED NOT NULL,
    pinned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (pinned_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_message_per_conversation (message_id, conversation_id),
    INDEX idx_conversation_id (conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create message_edits table to track message edits
CREATE TABLE IF NOT EXISTS message_edits (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    message_id INT UNSIGNED NOT NULL,
    old_content TEXT NOT NULL,
    edited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
