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
            <div class="search-bar mobile-search">
                <button type="button" id="mobileSearchBtn" onclick="openSearchModal()">🔍</button>
            </div>
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

<!-- Search Modal for Mobile -->
<div class="search-modal" id="searchModal">
    <div class="search-modal-content">
        <div class="search-modal-header">
            <input type="text" placeholder="Search users, posts..." id="mobileSearchInput" onkeyup="performMobileSearch(this.value)">
            <button type="button" class="search-modal-close" onclick="closeSearchModal()">✕</button>
        </div>
        <div class="search-modal-results" id="searchModalResults">
            <p style="color: var(--text-muted); text-align: center; padding: 20px;">Start typing to search...</p>
        </div>
    </div>
</div>

<script>
function openSearchModal() {
    document.getElementById('searchModal').classList.add('active');
    document.getElementById('mobileSearchInput').focus();
}

function closeSearchModal() {
    document.getElementById('searchModal').classList.remove('active');
    document.getElementById('mobileSearchInput').value = '';
}

function performMobileSearch(query) {
    if (query.trim().length === 0) {
        document.getElementById('searchModalResults').innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">Start typing to search...</p>';
        return;
    }
    
    performSearch(query);
}

// Close modal when clicking outside
document.getElementById('searchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSearchModal();
    }
});
</script>
