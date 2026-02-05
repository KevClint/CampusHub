<?php
/**
 * Online Status Handler
 * Tracks user activity and online/offline status
 * Uses last_activity timestamp for activity tracking
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
        case 'update_activity':
            /**
             * Update current user's last activity timestamp
             * Called on page activity (mousemove, keypress, etc.)
             * Helps determine if user is online
             */
            $update = $conn->prepare("
                UPDATE users 
                SET last_activity = NOW()
                WHERE id = ?
            ");
            $update->execute([$user_id]);
            
            echo json_encode(['success' => true]);
            break;
        
        case 'heartbeat':
            /**
             * Lightweight heartbeat to keep user online
             * Updates activity without other overhead
             * Call every 30 seconds while user is on page
             */
            $update = $conn->prepare("
                UPDATE users 
                SET last_activity = NOW(), status = 'online'
                WHERE id = ?
            ");
            $update->execute([$user_id]);
            
            echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
            break;
        
        case 'get_status':
            /**
             * Get online/offline status of one or more users
             * User is online if active within last 30 seconds
             * Returns status and last_activity timestamp
             */
            $user_ids = isset($_GET['user_ids']) ? json_decode($_GET['user_ids'], true) : [];
            
            if (empty($user_ids) || !is_array($user_ids)) {
                throw new Exception('user_ids array required');
            }
            
            // Sanitize user IDs
            $user_ids = array_filter(array_map('intval', $user_ids));
            if (empty($user_ids)) {
                throw new Exception('Invalid user IDs');
            }
            
            // Build placeholders
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            
            // Get user statuses
            $query = $conn->prepare("
                SELECT 
                    id,
                    display_name,
                    last_activity,
                    CASE 
                        WHEN last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND) THEN 'online'
                        ELSE 'offline'
                    END as status
                FROM users
                WHERE id IN ($placeholders)
            ");
            $query->execute($user_ids);
            $users = $query->fetchAll();
            
            // Create map of user_id -> status info
            $statusMap = [];
            foreach ($users as $user) {
                $lastActivity = strtotime($user['last_activity']);
                $secondsAgo = time() - $lastActivity;
                
                $statusMap[$user['id']] = [
                    'status' => $user['status'],
                    'last_activity' => $user['last_activity'],
                    'seconds_ago' => $secondsAgo,
                    'display_text' => $user['status'] === 'online' ? 'Online' : 
                                     ($secondsAgo < 60 ? 'Just now' : 
                                      ($secondsAgo < 3600 ? ceil($secondsAgo / 60) . 'm ago' :
                                       ceil($secondsAgo / 3600) . 'h ago'))
                ];
            }
            
            echo json_encode([
                'success' => true,
                'statuses' => $statusMap
            ]);
            break;
        
        case 'get_conversation_partner_status':
            /**
             * Get the online status of the user in a specific conversation
             * Used to show online indicator in chat header
             */
            $conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
            
            if (!$conversation_id) {
                throw new Exception('Conversation ID required');
            }
            
            // Verify user is participant
            $verify = $conn->prepare("SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
            $verify->execute([$conversation_id, $user_id]);
            if (!$verify->fetch()) {
                throw new Exception('Access denied', 403);
            }
            
            // Get the other participant(s) in this conversation
            $query = $conn->prepare("
                SELECT 
                    u.id,
                    u.display_name,
                    u.avatar_url,
                    u.last_activity,
                    CASE 
                        WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 30 SECOND) THEN 'online'
                        ELSE 'offline'
                    END as status
                FROM users u
                INNER JOIN conversation_participants cp ON u.id = cp.user_id
                WHERE cp.conversation_id = ? AND u.id != ?
                LIMIT 1
            ");
            $query->execute([$conversation_id, $user_id]);
            $partner = $query->fetch();
            
            if (!$partner) {
                throw new Exception('Partner not found');
            }
            
            $lastActivity = strtotime($partner['last_activity']);
            $secondsAgo = time() - $lastActivity;
            
            echo json_encode([
                'success' => true,
                'partner' => [
                    'id' => $partner['id'],
                    'display_name' => $partner['display_name'],
                    'avatar_url' => $partner['avatar_url'],
                    'status' => $partner['status'],
                    'last_activity' => $partner['last_activity'],
                    'display_text' => $partner['status'] === 'online' ? 'Online' : 
                                     ($secondsAgo < 60 ? 'Just now' : 
                                      ($secondsAgo < 3600 ? ceil($secondsAgo / 60) . 'm ago' :
                                       ceil($secondsAgo / 3600) . 'h ago'))
                ]
            ]);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Ensure HTTP code is a valid integer
    $code = (int)($e->getCode() ?: 400);
    // Validate it's a valid HTTP status code
    if ($code < 100 || $code >= 600) {
        $code = 400;
    }
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
