<?php
require_once 'config.php';
requireLogin();

$current_user_id = getCurrentUserId();

// Get all conversations for current user
$conversations_stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        c.is_group,
        c.updated_at,
        (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.conversation_id = c.id 
         AND m.created_at > COALESCE(cp.last_read_at, '2000-01-01')
         AND m.sender_id != ?) as unread_count
    FROM conversations c
    INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
    WHERE cp.user_id = ?
    ORDER BY c.updated_at DESC
");
$conversations_stmt->execute([$current_user_id, $current_user_id]);
$conversations = $conversations_stmt->fetchAll();

// If user clicked on a specific user to message
$selected_conversation_id = null;
if (isset($_GET['user'])) {
    $other_user_id = (int)$_GET['user'];
    
    // Check if conversation already exists
    $check = $conn->prepare("
        SELECT c.id 
        FROM conversations c
        INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
        INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = ?
        WHERE c.is_group = 0
    ");
    $check->execute([$current_user_id, $other_user_id]);
    $existing = $check->fetch();
    
    if ($existing) {
        $selected_conversation_id = $existing['id'];
    } else {
        // Create new conversation
        $conn->beginTransaction();
        $create = $conn->prepare("INSERT INTO conversations (created_by, is_group) VALUES (?, 0)");
        $create->execute([$current_user_id]);
        $new_conv_id = $conn->lastInsertId();
        
        $add_participant1 = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
        $add_participant1->execute([$new_conv_id, $current_user_id]);
        $add_participant1->execute([$new_conv_id, $other_user_id]);
        
        $conn->commit();
        $selected_conversation_id = $new_conv_id;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - School Social</title>
    <script src="assets/js/fouc-prevention.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="messages-layout">
            <!-- Conversations List -->
            <aside class="conversations-sidebar">
                <div class="conversations-header">
                    <h2>Messages</h2>
                    <button class="btn btn-primary btn-sm" onclick="showNewMessageModal()">+ New</button>
                </div>
                
                <div class="conversations-list">
                    <?php foreach ($conversations as $conv): ?>
                        <?php
                        // Get other participant info for 1-on-1 chats
                        if (!$conv['is_group']) {
                            $other_user_stmt = $conn->prepare("
                                SELECT u.* 
                                FROM users u
                                INNER JOIN conversation_participants cp ON u.id = cp.user_id
                                WHERE cp.conversation_id = ? AND u.id != ?
                            ");
                            $other_user_stmt->execute([$conv['id'], $current_user_id]);
                            $other_user = $other_user_stmt->fetch();
                        }
                        ?>
                        <div class="conversation-item <?php echo $selected_conversation_id == $conv['id'] ? 'active' : ''; ?>" 
                             data-conv-id="<?php echo $conv['id']; ?>"
                             onclick="loadConversation(<?php echo $conv['id']; ?>)">
                            <div class="user-avatar-sm">
                                <?php if (!$conv['is_group'] && $other_user): ?>
                                    <?php if ($other_user['avatar_url']): ?>
                                        <img src="<?php echo escape($other_user['avatar_url']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?php echo strtoupper(substr($other_user['display_name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="avatar-placeholder">👥</div>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-info">
                                <h4><?php echo escape($conv['is_group'] ? $conv['name'] : $other_user['display_name']); ?></h4>
                                <p class="text-muted"><?php echo escape(substr($conv['last_message'] ?? 'No messages yet', 0, 40)); ?></p>
                            </div>
                            <div class="conversation-meta">
                                <span class="time"><?php echo $conv['last_message_time'] ? timeAgo($conv['last_message_time']) : ''; ?></span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="badge badge-primary"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
            
            <!-- Chat Area -->
            <main class="chat-area" id="chatArea">
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <h3>Select a conversation</h3>
                    <p>Choose a conversation from the list or start a new one</p>
                </div>
            </main>
        </div>
    </div>
    
    <!-- New Message Modal -->
    <div id="newMessageModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; position: relative;">
                <div>
                    <h3 style="margin: 0; color: white; font-size: 18px;">✉️ New Message</h3>
                    <p style="margin: 4px 0 0; font-size: 12px; color: rgba(255, 255, 255, 0.8);">Select a user to message</p>
                </div>
                <button class="btn-close" style="color: white; font-size: 28px; opacity: 0.9; border: none; background: none; cursor: pointer;" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div style="padding: 16px; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-primary); z-index: 10;">
                    <input type="text" id="userSearch" placeholder="Search by name or username..." 
                           onkeyup="searchUsers(this.value)"
                           style="width: 100%; padding: 12px 16px; border: 1.5px solid var(--border-color); border-radius: 20px; outline: none; font-family: inherit; font-size: 14px; transition: all 0.2s ease; box-sizing: border-box;">
                </div>
                <div id="userResults" style="max-height: 400px; overflow-y: auto; padding: 8px 0;"></div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/messages_full.js"></script>
    <script>
        // Set current user ID for messaging
        window.currentUserId = <?php echo getCurrentUserId(); ?>;
        
        // Auto-load conversation if coming from a user link
        <?php if ($selected_conversation_id): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => loadConversation(<?php echo $selected_conversation_id; ?>), 100);
        });
        <?php endif; ?>
    </script>
</body>
</html>
