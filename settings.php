<?php
require_once 'config.php';
requireLogin();

$user_id = getCurrentUserId();

// Get user info
$query = $conn->prepare("
    SELECT id, username, display_name, avatar_url, email FROM users WHERE id = ?
");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CampusHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .settings-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .settings-user-info h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        .settings-user-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .settings-section {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .settings-section h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-section h3::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .settings-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .settings-item-label {
            flex: 1;
        }

        .settings-item-label h4 {
            margin: 0 0 4px 0;
            font-size: 15px;
            color: var(--text-primary);
        }

        .settings-item-label p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .settings-item-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 28px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--primary-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        /* Select */
        .settings-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .settings-select:hover {
            border-color: var(--primary-color);
        }

        .settings-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        /* Theme Preview */
        .theme-preview {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .theme-option {
            position: relative;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .theme-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .theme-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(0, 123, 255, 0.1);
        }

        .theme-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
        }

        .theme-preview .light-preview {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .theme-preview .dark-preview {
            background: #1e1e1e;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 15px;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #fff;
        }

        .preview-line {
            height: 8px;
            border-radius: 4px;
            background: rgba(0, 123, 255, 0.3);
        }

        .preview-line.secondary {
            background: rgba(0, 123, 255, 0.1);
            height: 6px;
        }

        /* Success/Error Messages */
        .settings-message {
            display: none;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }

        .settings-message.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            display: block;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .settings-message.error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            display: block;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .settings-container {
                padding: 15px;
            }

            .settings-header {
                flex-direction: column;
                text-align: center;
            }

            .theme-preview {
                grid-template-columns: 1fr;
            }

            .settings-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .settings-item-control {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="settings-container">
        <!-- Header -->
        <div class="settings-header">
            <div style="position: relative;">
                <img src="<?php echo htmlspecialchars($user['avatar_url'] ?? 'assets/images/default-avatar.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($user['display_name']); ?>" 
                     class="settings-avatar"
                     id="settingsAvatar">
                <label for="settingsAvatarUpload" style="position: absolute; bottom: 0; right: 0; cursor: pointer;">
                    <input type="file" id="settingsAvatarUpload" accept="image/*" style="display: none;">
                    <div style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-primary);">
                        <i class="material-icons" style="font-size: 20px;">photo_camera</i>
                    </div>
                </label>
            </div>
            <div class="settings-user-info">
                <h2><?php echo htmlspecialchars($user['display_name']); ?></h2>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <!-- Message Display -->
        <div id="settingsMessage" class="settings-message"></div>

        <!-- Theme Section -->
        <div class="settings-section">
            <h3>Appearance & Theme</h3>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Color Theme</h4>
                    <p>Choose between light and dark mode</p>
                </div>
            </div>

            <div class="theme-preview">
                <div class="theme-option">
                    <input type="radio" id="theme-light" name="theme" value="light">
                    <label for="theme-light">
                        <i class="material-icons">light_mode</i>
                        <span>Light Theme</span>
                    </label>
                    <div class="light-preview">
                        <div class="preview-line"></div>
                        <div class="preview-line secondary"></div>
                        <div class="preview-line secondary"></div>
                    </div>
                </div>

                <div class="theme-option">
                    <input type="radio" id="theme-dark" name="theme" value="dark">
                    <label for="theme-dark">
                        <i class="material-icons">dark_mode</i>
                        <span>Dark Theme</span>
                    </label>
                    <div class="dark-preview">
                        <div class="preview-line"></div>
                        <div class="preview-line secondary"></div>
                        <div class="preview-line secondary"></div>
                    </div>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Layout</h4>
                    <p>Choose between comfortable and compact view</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="layout-compact" data-setting="compact_layout">
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size: 13px;">Compact Layout</span>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Font Size</h4>
                    <p>Adjust text size across the site</p>
                </div>
                <div class="settings-item-control">
                    <select id="font-size" class="settings-select" data-setting="font_size">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Chat & Privacy Section -->
        <div class="settings-section">
            <h3>Chat & Privacy</h3>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Read Receipts</h4>
                    <p>Let people know when you've read their messages</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="read-receipts" data-setting="read_receipts">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Typing Indicator</h4>
                    <p>Show when you're typing a message</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="typing-indicator" data-setting="typing_indicator">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Online Status</h4>
                    <p>Let others see when you're online</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="online-status" data-setting="online_status_visible">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Notifications Section -->
        <div class="settings-section">
            <h3>Notifications</h3>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Message Notifications</h4>
                    <p>Receive notifications for new messages</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="notifications-enabled" data-setting="notifications_enabled">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Notification Sound</h4>
                    <p>Play sound for incoming messages</p>
                </div>
                <div class="settings-item-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="notification-sound" data-setting="notification_sound">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-item">
                <div class="settings-item-label">
                    <h4>Mute Notifications</h4>
                    <p>Temporarily disable notifications</p>
                </div>
                <div class="settings-item-control">
                    <select id="notification-mute" class="settings-select" data-setting="notification_mute_until">
                        <option value="">Unmute</option>
                        <option value="1h">Mute for 1 hour</option>
                        <option value="24h">Mute for 24 hours</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/settings.js"></script>
    <script>
        /**
         * Handle profile picture upload in settings
         */
        document.getElementById('settingsAvatarUpload').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Show loading state
            const settingsAvatar = document.getElementById('settingsAvatar');
            const originalSrc = settingsAvatar.src;
            settingsAvatar.style.opacity = '0.5';
            
            try {
                const formData = new FormData();
                formData.append('avatar', file);
                
                const response = await fetch('api/upload_avatar.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update avatar display
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        settingsAvatar.src = e.target.result;
                        settingsAvatar.style.opacity = '1';
                    };
                    reader.readAsDataURL(file);
                    
                    // Show success message
                    const messageBox = document.getElementById('settingsMessage');
                    messageBox.textContent = 'Profile picture updated successfully!';
                    messageBox.className = 'settings-message success';
                    
                    setTimeout(() => {
                        messageBox.className = 'settings-message';
                    }, 3000);
                } else {
                    settingsAvatar.style.opacity = '1';
                    const messageBox = document.getElementById('settingsMessage');
                    messageBox.textContent = 'Error: ' + data.error;
                    messageBox.className = 'settings-message error';
                }
            } catch (error) {
                settingsAvatar.style.opacity = '1';
                console.error('Upload error:', error);
                const messageBox = document.getElementById('settingsMessage');
                messageBox.textContent = 'Upload failed. Please try again.';
                messageBox.className = 'settings-message error';
            }
            
            // Reset file input
            this.value = '';
        });
    </script>
</body>
</html>
