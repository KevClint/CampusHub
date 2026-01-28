<?php
require_once 'config.php';
requireLogin();

$user_id = getCurrentUserId();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's posts
$posts_stmt = $conn->prepare("
    SELECT 
        p.*,
        COUNT(DISTINCT r.id) as reaction_count,
        COUNT(DISTINCT c.id) as comment_count
    FROM posts p
    LEFT JOIN reactions r ON p.id = r.post_id
    LEFT JOIN comments c ON p.id = c.post_id AND c.is_deleted = 0
    WHERE p.user_id = ? AND p.is_deleted = 0
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 20
");
$posts_stmt->execute([$user_id]);
$posts = $posts_stmt->fetchAll();

// Get stats
$stats = [
    'posts' => $conn->query("SELECT COUNT(*) FROM posts WHERE user_id = $user_id AND is_deleted = 0")->fetchColumn(),
    'reactions' => $conn->query("SELECT COUNT(*) FROM reactions r INNER JOIN posts p ON r.post_id = p.id WHERE p.user_id = $user_id")->fetchColumn(),
];

$current_user = $user;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - School Social</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="card" style="margin-bottom: 30px;">
            <div style="text-align: center; padding: 20px 0;">
                <div class="user-avatar" style="margin: 0 auto 20px;">
                    <?php if ($user['avatar_url']): ?>
                        <img src="<?php echo escape($user['avatar_url']); ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo strtoupper(substr($user['display_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <h1><?php echo escape($user['display_name']); ?></h1>
                <p class="text-muted">@<?php echo escape($user['username']); ?></p>
                <?php if ($user['year_level']): ?>
                    <span class="badge"><?php echo ucfirst($user['year_level']); ?></span>
                <?php endif; ?>
                <?php if ($user['major']): ?>
                    <span class="badge"><?php echo escape($user['major']); ?></span>
                <?php endif; ?>
                <?php if ($user['bio']): ?>
                    <p style="margin-top: 16px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        <?php echo nl2br(escape($user['bio'])); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 40px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700;"><?php echo $stats['posts']; ?></div>
                    <div class="text-muted">Posts</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700;"><?php echo $stats['reactions']; ?></div>
                    <div class="text-muted">Reactions</div>
                </div>
            </div>
        </div>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <h2 style="margin-bottom: 20px;">Your Posts</h2>
            
            <?php if (empty($posts)): ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>No posts yet</h3>
                        <p>Share something with your classmates!</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 16px;">Go to Feed</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card post-card" data-post-id="<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <div class="post-author">
                                <div class="user-avatar-sm">
                                    <?php if ($user['avatar_url']): ?>
                                        <img src="<?php echo escape($user['avatar_url']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?php echo strtoupper(substr($user['display_name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4><?php echo escape($user['display_name']); ?></h4>
                                    <p class="text-muted"><?php echo timeAgo($post['created_at']); ?></p>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn-icon" onclick="toggleDropdown(this)">⋮</button>
                                <div class="dropdown-menu">
                                    <a href="#" onclick="deletePost(<?php echo $post['id']; ?>)">Delete</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <p><?php echo nl2br(escape($post['content'])); ?></p>
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo escape($post['image_url']); ?>" alt="Post image" class="post-image">
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-actions">
                            <div class="action-btn">
                                <span class="icon">❤️</span>
                                <span class="count"><?php echo $post['reaction_count']; ?></span>
                            </div>
                            <div class="action-btn">
                                <span class="icon">💬</span>
                                <span class="count"><?php echo $post['comment_count']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
