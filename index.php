<?php
require_once 'config.php';
requireLogin();

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([getCurrentUserId()]);
$current_user = $stmt->fetch();

// Get posts from feed
$posts_stmt = $conn->prepare("
    SELECT 
        p.*,
        u.username,
        u.display_name,
        u.avatar_url,
        COUNT(DISTINCT r.id) as reaction_count,
        COUNT(DISTINCT c.id) as comment_count,
        MAX(CASE WHEN r.user_id = ? THEN 1 ELSE 0 END) as user_reacted
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    LEFT JOIN reactions r ON p.id = r.post_id
    LEFT JOIN comments c ON p.id = c.post_id AND c.is_deleted = 0
    WHERE p.is_deleted = 0
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 50
");
$posts_stmt->execute([getCurrentUserId()]);
$posts = $posts_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - School Social</title>
    <script src="assets/js/fouc-prevention.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="feed-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="user-profile-card">
                    <div class="user-avatar">
                        <?php if ($current_user['avatar_url']): ?>
                            <img src="<?php echo escape($current_user['avatar_url']); ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo strtoupper(substr($current_user['display_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo escape($current_user['display_name']); ?></h3>
                    <p class="text-muted">@<?php echo escape($current_user['username']); ?></p>
                    <?php if ($current_user['year_level']): ?>
                        <span class="badge"><?php echo ucfirst($current_user['year_level']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="sidebar-menu">
                    <a href="index.php" class="menu-item active">
                        <i class="material-icons">home</i> Feed
                    </a>
                    <a href="messages.php" class="menu-item">
                        <i class="material-icons">chat_bubble</i> Messages
                    </a>
                    <a href="profile.php" class="menu-item">
                        <i class="material-icons">person</i> Profile
                    </a>
                </div>
            </aside>
            
            <!-- Main Feed -->
            <main class="feed-main">
                <!-- Create Post -->
                <div class="card create-post-card">
                    <form method="POST" action="api/create_post.php" enctype="multipart/form-data" id="createPostForm">
                        <div class="create-post-header">
                            <div class="user-avatar-sm">
                                <?php if ($current_user['avatar_url']): ?>
                                    <img src="<?php echo escape($current_user['avatar_url']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo strtoupper(substr($current_user['display_name'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <textarea name="content" placeholder="What's on your mind, <?php echo escape(explode(' ', $current_user['display_name'])[0]); ?>?" 
                                      rows="3"></textarea>
                        </div>
                        
                        <div class="create-post-footer">
                            <div style="display: flex; gap: 10px;">
                                <label for="post-image" class="btn btn-secondary btn-sm">
                                    <i class="material-icons">photo_camera</i> Photo
                                    <input type="file" id="post-image" name="image" accept="image/*" style="display: none;">
                                </label>
                                <label for="post-video" class="btn btn-secondary btn-sm">
                                    <i class="material-icons">videocam</i> Video
                                    <input type="file" id="post-video" name="video" accept="video/*" style="display: none;">
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Post</button>
                        </div>
                        <div id="image-preview" style="display: none; margin-top: 10px;">
                            <img id="preview-img" style="max-width: 100%; border-radius: 8px;">
                        </div>
                        <div id="video-preview" style="display: none; margin-top: 10px;">
                            <video id="preview-video" style="max-width: 100%; border-radius: 8px; max-height: 300px;" controls></video>
                        </div>
                    </form>
                </div>
                
                <!-- Posts Feed -->
                <div id="posts-container">
                    <?php foreach ($posts as $post): ?>
                        <div class="card post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="user-avatar-sm">
                                        <?php if ($post['avatar_url']): ?>
                                            <img src="<?php echo escape($post['avatar_url']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <div class="avatar-placeholder"><?php echo strtoupper(substr($post['display_name'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4><?php echo escape($post['display_name']); ?></h4>
                                        <p class="text-muted">@<?php echo escape($post['username']); ?> · <?php echo timeAgo($post['created_at']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($post['user_id'] == getCurrentUserId()): ?>
                                    <div class="dropdown">
                                        <button class="btn-icon" onclick="toggleDropdown(this)"><i class="material-icons">more_vert</i></button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="deletePost(<?php echo $post['id']; ?>)">Delete</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="dropdown">
                                        <button class="btn-icon" onclick="toggleDropdown(this)"><i class="material-icons">more_vert</i></button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="reportPost(<?php echo $post['id']; ?>)">Report</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-content">
                                <p><?php echo nl2br(escape($post['content'])); ?></p>
                                <?php if (isset($post['image_url']) && $post['image_url']): ?>
                                    <img src="<?php echo escape($post['image_url']); ?>" alt="Post image" class="post-image">
                                <?php endif; ?>
                                <?php if (isset($post['video_url']) && $post['video_url']): ?>
                                    <video src="<?php echo escape($post['video_url']); ?>" controls class="post-video" style="max-width: 100%; border-radius: 8px; margin-top: 12px;"></video>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-actions">
                                <button type="button" class="action-btn <?php echo $post['user_reacted'] ? 'active' : ''; ?>" 
                                        onclick="toggleReaction(<?php echo $post['id']; ?>, this)">
                                    <i class="material-icons">favorite</i>
                                    <span class="count"><?php echo $post['reaction_count']; ?></span>
                                </button>
                                <button type="button" class="action-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    <i class="material-icons">comment</i>
                                    <span class="count"><?php echo $post['comment_count']; ?></span>
                                </button>
                            </div>
                            
                            <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                <div class="comments-list"></div>
                                <form class="comment-form" onsubmit="submitComment(event, <?php echo $post['id']; ?>)">
                                    <input type="text" name="comment" placeholder="Write a comment..." required>
                                    <button type="submit" class="btn btn-primary btn-sm">Post</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
            
            <!-- Right Sidebar -->
            <aside class="sidebar-right">
                <div class="card">
                    <h3>Suggested Friends</h3>
                    <div class="suggested-users">
                        <?php
                        $suggested = $conn->prepare("SELECT id, username, display_name, avatar_url FROM users WHERE id != ? AND is_active = 1 ORDER BY RAND() LIMIT 5");
                        $suggested->execute([getCurrentUserId()]);
                        while ($user = $suggested->fetch()):
                        ?>
                            <div class="user-item">
                                <div class="user-avatar-sm">
                                    <?php if ($user['avatar_url']): ?>
                                        <img src="<?php echo escape($user['avatar_url']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?php echo strtoupper(substr($user['display_name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-info">
                                    <h5><?php echo escape($user['display_name']); ?></h5>
                                    <p class="text-muted">@<?php echo escape($user['username']); ?></p>
                                </div>
                                <a href="messages.php?user=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Message</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Set current user ID for messaging and comments
        window.currentUserId = <?php echo getCurrentUserId(); ?>;
    </script>
</body>
</html>
