<header class="main-header">
    <div class="header-container">
        <div class="header-left">
            <a href="index.php" class="logo">🎓 CampusHub</a>
        </div>
        
        <div class="header-center">
            <div class="search-bar">
                <input type="text" placeholder="Search users, posts..." id="globalSearch">
                <button type="button" onclick="performSearch(document.getElementById('globalSearch').value.trim())">🔍</button>
            </div>
        </div>
        
        <div class="header-right">
            <a href="index.php" class="header-icon" title="Feed">
                <span>🏠</span>
            </a>
            <a href="messages.php" class="header-icon" title="Messages">
                <span>💬</span>
                <span class="notification-badge" id="messagesBadge" style="display: none;"></span>
            </a>
            <div class="dropdown">
                <button class="header-icon user-menu" onclick="toggleDropdown(this)">
                    <?php 
                    // Ensure $current_user is defined
                    if (!isset($current_user)) {
                        $current_user = getCurrentUser();
                    }
                    
                    if ($current_user && $current_user['avatar_url']): 
                    ?>
                        <img src="<?php echo escape($current_user['avatar_url']); ?>" alt="Avatar" class="header-avatar">
                    <?php elseif ($current_user): ?>
                        <div class="avatar-placeholder"><?php echo strtoupper(substr($current_user['display_name'], 0, 1)); ?></div>
                    <?php else: ?>
                        <div class="avatar-placeholder">👤</div>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="profile.php">Profile</a>
                    <a href="settings.php">Settings</a>
                    <hr>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>
