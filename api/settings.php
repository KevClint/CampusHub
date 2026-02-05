<?php
/**
 * User Settings Handler
 * Manages all user preferences: theme, privacy, notifications, appearance
 * Supports get/save operations via AJAX
 */

require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$user_id = getCurrentUserId();
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : null);

if (!$action) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Action required']));
}

try {
    switch ($action) {
        case 'get_settings':
            /**
             * Get all user settings
             * Returns default settings if user_settings record doesn't exist
             */
            $query = $conn->prepare("
                SELECT * FROM user_settings WHERE user_id = ?
            ");
            $query->execute([$user_id]);
            $settings = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // Create default settings if they don't exist
                $insert = $conn->prepare("
                    INSERT INTO user_settings (user_id) VALUES (?)
                ");
                $insert->execute([$user_id]);
                
                // Fetch the newly created settings
                $query->execute([$user_id]);
                $settings = $query->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'settings' => $settings]);
            break;
        
        case 'save_theme':
            /**
             * Save theme preference (light/dark)
             */
            $theme = isset($_POST['theme']) ? $_POST['theme'] : null;
            
            if (!in_array($theme, ['light', 'dark'])) {
                throw new Exception('Invalid theme');
            }
            
            // Ensure settings record exists
            $check = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $check->execute([$user_id]);
            if (!$check->fetch()) {
                $insert = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $insert->execute([$user_id]);
            }
            
            // Update theme
            $update = $conn->prepare("
                UPDATE user_settings SET theme = ?, updated_at = NOW() WHERE user_id = ?
            ");
            $update->execute([$theme, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Theme saved']);
            break;
        
        case 'save_chat_privacy':
            /**
             * Save chat & privacy settings
             */
            $setting = isset($_POST['setting']) ? $_POST['setting'] : null;
            $value = isset($_POST['value']) ? (int)$_POST['value'] : null;
            
            $allowed_settings = ['read_receipts', 'typing_indicator', 'online_status_visible'];
            
            if (!in_array($setting, $allowed_settings) || !is_numeric($value)) {
                throw new Exception('Invalid setting');
            }
            
            // Ensure settings record exists
            $check = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $check->execute([$user_id]);
            if (!$check->fetch()) {
                $insert = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $insert->execute([$user_id]);
            }
            
            // Sanitize column name (use prepared statement binding not possible for column names)
            $query = sprintf("UPDATE user_settings SET %s = ?, updated_at = NOW() WHERE user_id = ?", 
                            preg_replace('/[^a-z_]/', '', $setting));
            
            $update = $conn->prepare($query);
            $update->execute([$value, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Setting saved']);
            break;
        
        case 'save_notifications':
            /**
             * Save notification settings
             */
            $setting = isset($_POST['setting']) ? $_POST['setting'] : null;
            $value = isset($_POST['value']) ? $_POST['value'] : null;
            
            $allowed_settings = ['notifications_enabled', 'notification_sound', 'notification_mute_until'];
            
            if (!in_array($setting, $allowed_settings)) {
                throw new Exception('Invalid setting');
            }
            
            // Ensure settings record exists
            $check = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $check->execute([$user_id]);
            if (!$check->fetch()) {
                $insert = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $insert->execute([$user_id]);
            }
            
            if ($setting === 'notification_mute_until') {
                // Handle mute duration (1h, 24h, or null to unmute)
                $mute_until = null;
                if ($value === '1h') {
                    $mute_until = date('Y-m-d H:i:s', strtotime('+1 hour'));
                } elseif ($value === '24h') {
                    $mute_until = date('Y-m-d H:i:s', strtotime('+24 hours'));
                }
                // else: null means unmute
                
                $update = $conn->prepare("
                    UPDATE user_settings SET notification_mute_until = ?, updated_at = NOW() WHERE user_id = ?
                ");
                $update->execute([$mute_until, $user_id]);
            } else {
                // Boolean settings
                $query = sprintf("UPDATE user_settings SET %s = ?, updated_at = NOW() WHERE user_id = ?", 
                                preg_replace('/[^a-z_]/', '', $setting));
                $update = $conn->prepare($query);
                $update->execute([(int)$value, $user_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Notification setting saved']);
            break;
        
        case 'save_appearance':
            /**
             * Save appearance settings
             */
            $setting = isset($_POST['setting']) ? $_POST['setting'] : null;
            $value = isset($_POST['value']) ? $_POST['value'] : null;
            
            if ($setting === 'font_size') {
                if (!in_array($value, ['small', 'medium', 'large'])) {
                    throw new Exception('Invalid font size');
                }
            } elseif ($setting === 'compact_layout') {
                $value = (int)$value;
            } else {
                throw new Exception('Invalid setting');
            }
            
            // Ensure settings record exists
            $check = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $check->execute([$user_id]);
            if (!$check->fetch()) {
                $insert = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $insert->execute([$user_id]);
            }
            
            $query = sprintf("UPDATE user_settings SET %s = ?, updated_at = NOW() WHERE user_id = ?", 
                            preg_replace('/[^a-z_]/', '', $setting));
            $update = $conn->prepare($query);
            $update->execute([$value, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Appearance setting saved']);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Ensure HTTP code is a valid integer
    $code = (int)($e->getCode() ?: 400);
    if ($code < 100 || $code >= 600) {
        $code = 400;
    }
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
