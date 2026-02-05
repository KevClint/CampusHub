# Settings System Documentation

## Overview

CampusHub now includes a comprehensive user settings system with Dark/Light mode support, privacy controls, notification preferences, and appearance customization.

## Features

### 1. Theme Switching (Dark/Light Mode)
- **Instant Application**: Theme changes are applied immediately without page refresh
- **Persistent Storage**: Theme preference is saved in localStorage (before login) and database (after login)
- **FOUC Prevention**: Theme is applied before page loads to prevent flash of unstyled content
- **Smooth Transitions**: 0.3s CSS transition between themes
- **System Preference Detection**: Uses system preference if no saved theme exists

#### How It Works
```javascript
// Toggle theme (in header)
toggleTheme()

// Automatically saved to:
// 1. localStorage -> 'campushub-theme'
// 2. Database -> user_settings.theme
```

### 2. Chat & Privacy Settings
- **Read Receipts**: Users can toggle visibility of message read status
- **Typing Indicator**: Show/hide when typing messages
- **Online Status**: Control who sees your online/offline status

### 3. Notification Settings
- **Enable/Disable Notifications**: Turn message notifications on/off
- **Notification Sound**: Toggle sound alerts
- **Mute Duration**: 
  - Unmute (immediate)
  - Mute for 1 hour
  - Mute for 24 hours

### 4. Appearance Settings
- **Layout**: Toggle between comfortable and compact view
- **Font Size**: Choose from Small, Medium, or Large text

## Database Schema

### user_settings Table

```sql
CREATE TABLE user_settings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    
    -- Theme
    theme ENUM('light', 'dark') DEFAULT 'light',
    
    -- Chat & Privacy
    read_receipts TINYINT(1) DEFAULT 1,
    typing_indicator TINYINT(1) DEFAULT 1,
    online_status_visible TINYINT(1) DEFAULT 1,
    
    -- Notifications
    notifications_enabled TINYINT(1) DEFAULT 1,
    notification_sound TINYINT(1) DEFAULT 1,
    notification_mute_until DATETIME DEFAULT NULL,
    
    -- Appearance
    compact_layout TINYINT(1) DEFAULT 0,
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_theme (theme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Installation

### 1. Run Migrations

Open `http://localhost/CampusHub/migrate.php` in your browser to apply the database schema automatically.

Alternatively, manually run in MySQL:
```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Files Added/Modified

**New Files:**
- `/api/settings.php` - Backend API handler for settings operations
- `/settings.php` - Settings page UI
- `/assets/js/settings.js` - Settings JavaScript manager
- `/assets/js/fouc-prevention.js` - Flash of Unstyled Content prevention
- `/migrations/004_add_user_settings.sql` - Database migration

**Modified Files:**
- `/assets/css/style.css` - Added dark mode CSS variables and appearance styles
- `/includes/header.php` - Added theme toggle button and JavaScript
- `/index.php`, `/messages.php`, `/profile.php`, `/login.php`, `/register.php` - Added FOUC prevention script

## Usage

### Accessing Settings Page
Navigate to `http://localhost/CampusHub/settings.php` (requires login)

### Toggling Theme
Click the theme toggle button (🌙/☀️) in the header to switch between light and dark mode instantly.

### Modifying Settings
1. Visit Settings page
2. Adjust toggles and selections
3. Changes are saved automatically via AJAX
4. Success messages confirm each save

## CSS Variables (Dark Mode)

### Light Mode (Default)
```css
--primary: #3b82f6;
--bg-primary: #ffffff;
--bg-secondary: #f8fafc;
--text-primary: #0f172a;
--text-secondary: #64748b;
--border-color: #e2e8f0;
```

### Dark Mode
```css
--primary: #60a5fa;
--bg-primary: #1e293b;
--bg-secondary: #0f172a;
--text-primary: #f1f5f9;
--text-secondary: #cbd5e1;
--border-color: #334155;
```

## API Endpoints

### GET /api/settings.php?action=get_settings
Returns all user settings

**Response:**
```json
{
  "success": true,
  "settings": {
    "id": 1,
    "user_id": 1,
    "theme": "light",
    "read_receipts": 1,
    "typing_indicator": 1,
    "online_status_visible": 1,
    "notifications_enabled": 1,
    "notification_sound": 1,
    "notification_mute_until": null,
    "compact_layout": 0,
    "font_size": "medium",
    "created_at": "2026-02-05 10:30:00",
    "updated_at": "2026-02-05 10:30:00"
  }
}
```

### POST /api/settings.php
#### action: save_theme
```
POST /api/settings.php
theme: "light" | "dark"
```

#### action: save_chat_privacy
```
POST /api/settings.php
setting: "read_receipts" | "typing_indicator" | "online_status_visible"
value: 0 | 1
```

#### action: save_notifications
```
POST /api/settings.php
setting: "notifications_enabled" | "notification_sound" | "notification_mute_until"
value: 0 | 1 | "1h" | "24h" | ""
```

#### action: save_appearance
```
POST /api/settings.php
setting: "compact_layout" | "font_size"
value: 0 | 1 | "small" | "medium" | "large"
```

## How Dark Mode Works

### 1. Theme Storage
```javascript
// Saved in localStorage for pre-login
localStorage.setItem('campushub-theme', 'dark')

// Saved in database for persistent storage
UPDATE user_settings SET theme = 'dark' WHERE user_id = 1
```

### 2. Theme Application
```javascript
// Applied via HTML attribute
document.documentElement.setAttribute('data-theme', 'dark')

// CSS variables automatically adjust
[data-theme="dark"] {
    --bg-primary: #1e293b;
    --text-primary: #f1f5f9;
    /* ... and more */
}
```

### 3. FOUC Prevention
```javascript
// fouc-prevention.js runs before page loads
(function() {
    let theme = localStorage.getItem('campushub-theme');
    if (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        theme = 'dark';
    }
    document.documentElement.setAttribute('data-theme', theme || 'light');
})();
```

## JavaScript Classes & Functions

### SettingsManager Class
Located in `/assets/js/settings.js`

```javascript
class SettingsManager {
    // Initialize settings and load from server
    init()
    
    // Apply theme from localStorage (FOUC prevention)
    applyThemeFromStorage()
    
    // Fetch settings from API
    async loadSettings()
    
    // Apply loaded settings to UI
    applySettings()
    
    // Set up all event listeners
    setupEventListeners()
    
    // Handle theme change
    async handleThemeChange(theme)
    
    // Apply theme to document
    applyTheme(theme)
    
    // Save individual settings
    async saveChatPrivacySetting(setting, value)
    async saveNotificationSetting(setting, value)
    async saveAppearanceSetting(setting, value)
    
    // Display success/error messages
    showMessage(message, type)
}
```

### Theme Toggle Function (Header)
```javascript
function toggleTheme()  // Toggle between light/dark
function saveThemeToServer(theme)  // Save to database
```

## Appearance Classes

### Compact Layout
```css
body.compact-layout {
    font-size: 14px;
}
/* Reduces padding and spacing */
```

### Font Size Variations
```css
body[data-font-size="small"]   /* 13px base */
body[data-font-size="medium"]  /* 16px base (default) */
body[data-font-size="large"]   /* 18px base */
```

## Troubleshooting

### Theme Not Persisting
1. Check if `localStorage` is enabled in browser
2. Verify `api/settings.php` is accessible and has no errors
3. Check database migration was applied (user_settings table exists)

### Settings Not Saving
1. Verify user is logged in (session active)
2. Check browser console for JavaScript errors
3. Verify API endpoint returns success (200 status)
4. Check MySQL error logs for database issues

### FOUC (Flash of Unstyled Content)
1. Ensure `fouc-prevention.js` is included in `<head>` tag
2. Verify script tag appears before CSS link
3. Check browser caching (clear cache if needed)

## Security Notes

- All settings are user-specific (user_id UNIQUE constraint)
- Settings updates require active session (requireLogin())
- Column names are sanitized with regex: `preg_replace('/[^a-z_]/', '', $setting)`
- Invalid HTTP codes are validated before use
- Database uses prepared statements (PDO prepared statements)

## Performance Considerations

- Settings loaded once on page load (not polling)
- Theme preference checked from localStorage before API call
- System preference detection uses efficient `matchMedia` API
- Theme transitions use CSS (0.3s) for smooth UX
- All API calls use AJAX (no page refresh)

## Future Enhancements

Potential additions:
- Custom color themes
- Font family selection
- Message sound customization
- Email notification preferences
- Two-factor authentication settings
- Block list management
- Privacy level presets (Public/Friends Only/Private)

## Support

For issues or bugs, please check:
1. Database migration was applied (`/migrate.php`)
2. All required files exist
3. Browser console for JavaScript errors
4. MySQL error logs for database issues

---

**Last Updated:** February 5, 2026
**Version:** 1.0
**Compatibility:** PHP 7.2+, MySQL 5.7+
