<header class="main-header">
    <div class="header-container">
        <div class="header-left">
            <a href="index.php" class="logo">CampusHub</a>
        </div>
        
        <div class="header-center">
            <div class="search-bar">
                <input type="text" placeholder="Search users, posts..." id="globalSearch">
                <button type="button" onclick="performSearch(document.getElementById('globalSearch').value.trim())"><i class="material-icons">search</i></button>
            </div>
        </div>
        
        <div class="header-right">
            <a href="index.php" class="header-icon" title="Feed">
                <i class="material-icons">home</i>
            </a>
            <div class="search-bar mobile-search">
                <button type="button" id="mobileSearchBtn" onclick="openSearchModal()"><i class="material-icons">search</i></button>
            </div>
            <a href="messages.php" class="header-icon" title="Messages">
                <i class="material-icons">chat_bubble</i>
                <span class="notification-badge" id="messagesBadge" style="display: none;"></span>
            </a>
            <button type="button" class="header-icon theme-toggle" id="themeToggle" title="Toggle theme" onclick="toggleTheme()">
                <i class="material-icons" id="themeIcon">dark_mode</i>
            </button>
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
                        <i class="material-icons">person</i>
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
            <button type="button" class="search-modal-close" onclick="closeSearchModal()"><i class="material-icons">close</i></button>
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
    document.getElementById('searchModalResults').innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">Start typing to search...</p>';
}

function performMobileSearch(query) {
    if (query.trim().length < 2) {
        document.getElementById('searchModalResults').innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">Start typing to search...</p>';
        return;
    }
    
    // Perform search and display results in modal
    performSearchInModal(query);
}

/**
 * Perform search and display results in mobile modal
 * Uses proper mobile-responsive layout
 */
async function performSearchInModal(query) {
    if (!query) return;
    
    try {
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displayMobileSearchResults(data.posts, data.users, query);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

/**
 * Display search results in mobile modal with responsive layout
 */
function displayMobileSearchResults(posts, users, query) {
    const resultsContainer = document.getElementById('searchModalResults');
    let resultsHtml = '';
    
    // Show users first
    if (users && users.length > 0) {
        resultsHtml += '<div class="search-results-section"><h4 class="search-section-title">Users</h4>';
        
        users.forEach(user => {
            const avatar = user.avatar_url
                ? `<img src="${escapeHtml(user.avatar_url)}" alt="Avatar" class="search-result-avatar">`
                : `<div class="avatar-placeholder search-result-avatar">${user.display_name.charAt(0).toUpperCase()}</div>`;
            
            resultsHtml += `
                <div class="search-result-item" onclick="window.location.href='messages.php?user=${user.id}'">
                    ${avatar}
                    <div class="search-result-info">
                        <h5>${escapeHtml(user.display_name)}</h5>
                        <p>@${escapeHtml(user.username)}</p>
                    </div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
    }
    
    // Show posts
    if (posts && posts.length > 0) {
        resultsHtml += '<div class="search-results-section"><h4 class="search-section-title">Posts</h4>';
        
        posts.forEach(post => {
            const avatar = post.avatar_url
                ? `<img src="${escapeHtml(post.avatar_url)}" alt="Avatar" class="search-result-avatar">`
                : `<div class="avatar-placeholder search-result-avatar">${post.display_name.charAt(0).toUpperCase()}</div>`;
            
            const preview = escapeHtml(post.content.substring(0, 60)) + (post.content.length > 60 ? '...' : '');
            
            resultsHtml += `
                <div class="search-result-item" onclick="scrollToPost(${post.id}); closeSearchModal();">
                    ${avatar}
                    <div class="search-result-info">
                        <h5>${escapeHtml(post.display_name)}</h5>
                        <p style="font-size: 13px; margin-top: 4px;">${preview}</p>
                    </div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
    }
    
    if (!resultsHtml) {
        resultsHtml = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">No results found</p>';
    }
    
    resultsContainer.innerHTML = resultsHtml;
}

// Close modal when clicking outside
document.getElementById('searchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSearchModal();
    }
});

/**
 * Toggle between light and dark themes
 */
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    // Update HTML attribute
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // Update localStorage
    localStorage.setItem('campushub-theme', newTheme);
    
    // Update icon
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        themeIcon.textContent = newTheme === 'dark' ? 'light_mode' : 'dark_mode';
    }
    
    // Save to server if user is logged in
    saveThemeToServer(newTheme);
}

/**
 * Save theme preference to server
 */
async function saveThemeToServer(theme) {
    const formData = new FormData();
    formData.append('action', 'save_theme');
    formData.append('theme', theme);
    
    try {
        await fetch('api/settings.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error saving theme:', error);
    }
}

/**
 * Initialize theme on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        themeIcon.textContent = currentTheme === 'dark' ? 'light_mode' : 'dark_mode';
    }
});

</script>
