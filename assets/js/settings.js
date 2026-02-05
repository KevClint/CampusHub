/**
 * Settings Management
 * Handles loading, saving, and applying user settings
 * Includes theme switching with localStorage persistence
 */

class SettingsManager {
    constructor() {
        this.apiUrl = 'api/settings.php';
        this.settings = null;
        this.init();
    }

    async init() {
        // Apply saved theme immediately (prevent FOUC)
        this.applyThemeFromStorage();
        
        // Load all settings
        await this.loadSettings();
        
        // Set up event listeners
        this.setupEventListeners();
    }

    /**
     * Apply theme from localStorage before page fully loads
     * This prevents Flash of Unstyled Content (FOUC)
     */
    applyThemeFromStorage() {
        const savedTheme = localStorage.getItem('campushub-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    }

    /**
     * Load all settings from server
     */
    async loadSettings() {
        try {
            const response = await fetch(this.apiUrl + '?action=get_settings');
            const data = await response.json();

            if (!data.success) {
                console.error('Failed to load settings:', data.error);
                return;
            }

            this.settings = data.settings;
            this.applySettings();
        } catch (error) {
            console.error('Error loading settings:', error);
        }
    }

    /**
     * Apply loaded settings to UI elements
     */
    applySettings() {
        if (!this.settings) return;

        // Theme
        const themeRadio = document.querySelector(`input[name="theme"][value="${this.settings.theme}"]`);
        if (themeRadio) {
            themeRadio.checked = true;
        }
        this.applyTheme(this.settings.theme);

        // Chat & Privacy
        if (document.getElementById('read-receipts')) {
            document.getElementById('read-receipts').checked = this.settings.read_receipts == 1;
        }
        if (document.getElementById('typing-indicator')) {
            document.getElementById('typing-indicator').checked = this.settings.typing_indicator == 1;
        }
        if (document.getElementById('online-status')) {
            document.getElementById('online-status').checked = this.settings.online_status_visible == 1;
        }

        // Notifications
        if (document.getElementById('notifications-enabled')) {
            document.getElementById('notifications-enabled').checked = this.settings.notifications_enabled == 1;
        }
        if (document.getElementById('notification-sound')) {
            document.getElementById('notification-sound').checked = this.settings.notification_sound == 1;
        }

        // Appearance
        if (document.getElementById('layout-compact')) {
            document.getElementById('layout-compact').checked = this.settings.compact_layout == 1;
        }
        if (document.getElementById('font-size')) {
            document.getElementById('font-size').value = this.settings.font_size || 'medium';
        }

        // Apply appearance settings to body
        if (this.settings.compact_layout == 1) {
            document.body.classList.add('compact-layout');
        }
        if (this.settings.font_size) {
            document.body.setAttribute('data-font-size', this.settings.font_size);
        }
    }

    /**
     * Set up event listeners for all controls
     */
    setupEventListeners() {
        // Theme radio buttons
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.handleThemeChange(e.target.value));
        });

        // Toggle switches for chat & privacy
        const chatPrivacySettings = ['read-receipts', 'typing-indicator', 'online-status'];
        chatPrivacySettings.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', (e) => {
                    this.saveChatPrivacySetting(
                        e.target.getAttribute('data-setting'),
                        e.target.checked ? 1 : 0
                    );
                });
            }
        });

        // Toggle switches for notifications
        const notificationSettings = ['notifications-enabled', 'notification-sound'];
        notificationSettings.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', (e) => {
                    this.saveNotificationSetting(
                        e.target.getAttribute('data-setting'),
                        e.target.checked ? 1 : 0
                    );
                });
            }
        });

        // Notification mute select
        const muteSelect = document.getElementById('notification-mute');
        if (muteSelect) {
            muteSelect.addEventListener('change', (e) => {
                this.saveNotificationSetting('notification_mute_until', e.target.value);
            });
        }

        // Appearance settings
        const compactLayout = document.getElementById('layout-compact');
        if (compactLayout) {
            compactLayout.addEventListener('change', (e) => {
                this.saveAppearanceSetting('compact_layout', e.target.checked ? 1 : 0);
                if (e.target.checked) {
                    document.body.classList.add('compact-layout');
                } else {
                    document.body.classList.remove('compact-layout');
                }
            });
        }

        const fontSize = document.getElementById('font-size');
        if (fontSize) {
            fontSize.addEventListener('change', (e) => {
                this.saveAppearanceSetting('font_size', e.target.value);
                document.body.setAttribute('data-font-size', e.target.value);
            });
        }
    }

    /**
     * Handle theme change
     */
    async handleThemeChange(theme) {
        // Update UI immediately
        this.applyTheme(theme);

        // Save to localStorage
        localStorage.setItem('campushub-theme', theme);

        // Save to server
        const formData = new FormData();
        formData.append('action', 'save_theme');
        formData.append('theme', theme);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.showMessage('Theme saved successfully', 'success');
            } else {
                this.showMessage('Failed to save theme: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error saving theme:', error);
            this.showMessage('Error saving theme', 'error');
        }
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }

    /**
     * Save chat & privacy settings
     */
    async saveChatPrivacySetting(setting, value) {
        const formData = new FormData();
        formData.append('action', 'save_chat_privacy');
        formData.append('setting', setting);
        formData.append('value', value);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.showMessage('Setting saved', 'success');
            } else {
                this.showMessage('Failed to save setting: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error saving setting:', error);
            this.showMessage('Error saving setting', 'error');
        }
    }

    /**
     * Save notification settings
     */
    async saveNotificationSetting(setting, value) {
        const formData = new FormData();
        formData.append('action', 'save_notifications');
        formData.append('setting', setting);
        formData.append('value', value);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.showMessage('Notification setting saved', 'success');
            } else {
                this.showMessage('Failed to save setting: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error saving setting:', error);
            this.showMessage('Error saving setting', 'error');
        }
    }

    /**
     * Save appearance settings
     */
    async saveAppearanceSetting(setting, value) {
        const formData = new FormData();
        formData.append('action', 'save_appearance');
        formData.append('setting', setting);
        formData.append('value', value);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.showMessage('Appearance setting saved', 'success');
            } else {
                this.showMessage('Failed to save setting: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error saving setting:', error);
            this.showMessage('Error saving setting', 'error');
        }
    }

    /**
     * Show message to user
     */
    showMessage(message, type) {
        const messageBox = document.getElementById('settingsMessage');
        if (!messageBox) return;

        messageBox.textContent = message;
        messageBox.className = `settings-message ${type}`;

        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageBox.className = 'settings-message';
            }, 3000);
        }
    }
}

// Initialize settings manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});
