// Main JavaScript for School Social Network

/**
 * Initialize notification updates on page load
 * Updates the message badge periodically to show unread message count
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification badge on page load
    updateNotificationBadgeFromMain();
    
    // Refresh notification badge every 10 seconds
    setInterval(updateNotificationBadgeFromMain, 10000);
});

/**
 * Update notification badge (wrapper for main.js context)
 */
async function updateNotificationBadgeFromMain() {
    try {
        const response = await fetch('api/mark_message_read.php?action=get_unread_count');
        const data = await response.json();
        
        if (data && data.success) {
            const badge = document.getElementById('messagesBadge');
            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.debug('Error updating notification badge:', error);
    }
}

// Global search functionality
const globalSearchInput = document.getElementById('globalSearch');
if (globalSearchInput) {
    let searchTimeout;
    
    globalSearchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            closeSearchResults();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    globalSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(e.target.value.trim());
        }
    });
}

// Perform search
async function performSearch(query) {
    if (!query) return;
    
    try {
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.posts, data.users, query);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

// Display search results
function displaySearchResults(posts, users, query) {
    let resultsHtml = '';
    
    // Show users first
    if (users && users.length > 0) {
        resultsHtml += '<div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);"><h4 style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin: 0 0 8px;">Users</h4>';
        
        users.forEach(user => {
            const avatar = user.avatar_url
                ? `<img src="${escapeHtml(user.avatar_url)}" alt="Avatar">`
                : `<div class="avatar-placeholder">${user.display_name.charAt(0).toUpperCase()}</div>`;
            
            resultsHtml += `
                <div style="display: flex; gap: 12px; padding: 8px; border-radius: 8px; cursor: pointer; transition: background 0.2s;" 
                     onmouseover="this.style.background='var(--bg-secondary)'" 
                     onmouseout="this.style.background='transparent'"
                     onclick="window.location.href='messages.php?user=${user.id}'">
                    <div class="user-avatar-sm" style="width: 40px; height: 40px;">
                        ${avatar}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 14px;">${escapeHtml(user.display_name)}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">@${escapeHtml(user.username)}</div>
                    </div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
    }
    
    // Show posts
    if (posts && posts.length > 0) {
        resultsHtml += '<div style="padding: 12px 16px;"><h4 style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin: 0 0 8px;">Posts</h4>';
        
        posts.forEach(post => {
            const avatar = post.avatar_url
                ? `<img src="${escapeHtml(post.avatar_url)}" alt="Avatar">`
                : `<div class="avatar-placeholder">${post.display_name.charAt(0).toUpperCase()}</div>`;
            
            const preview = post.content.substring(0, 60) + (post.content.length > 60 ? '...' : '');
            
            resultsHtml += `
                <div style="padding: 8px; border-radius: 8px; cursor: pointer; transition: background 0.2s;" 
                     onmouseover="this.style.background='var(--bg-secondary)'" 
                     onmouseout="this.style.background='transparent'"
                     onclick="scrollToPost(${post.id})">
                    <div style="display: flex; gap: 12px; margin-bottom: 6px;">
                        <div class="user-avatar-sm" style="width: 32px; height: 32px; flex-shrink: 0;">
                            ${avatar}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 13px;">${escapeHtml(post.display_name)}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">@${escapeHtml(post.username)}</div>
                        </div>
                    </div>
                    <div style="font-size: 13px; color: var(--text-primary); padding: 0 40px;">${escapeHtml(preview)}</div>
                </div>
            `;
        });
        
        resultsHtml += '</div>';
    }
    
    if (!resultsHtml) {
        resultsHtml = '<div style="padding: 20px; text-align: center; color: var(--text-muted);">No results found</div>';
    }
    
    // Create or update results container
    let resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.id = 'searchResults';
        resultsContainer.style.cssText = `
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 600px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: var(--shadow-lg);
            max-height: 400px;
            overflow-y: auto;
            z-index: 999;
        `;
        document.body.appendChild(resultsContainer);
    }
    
    resultsContainer.innerHTML = resultsHtml;
    resultsContainer.style.display = 'block';
}

// Close search results
function closeSearchResults() {
    const resultsContainer = document.getElementById('searchResults');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

// Scroll to post
function scrollToPost(postId) {
    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
    if (postCard) {
        postCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        postCard.style.backgroundColor = '#fffacd';
        setTimeout(() => postCard.style.backgroundColor = '', 2000);
        closeSearchResults();
        document.getElementById('globalSearch').value = '';
    } else {
        alert('Post not found or has been deleted');
    }
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-bar') && !e.target.closest('#searchResults')) {
        closeSearchResults();
    }
});

// Toggle dropdown menus
function toggleDropdown(button) {
    const dropdown = button.closest('.dropdown');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu.show').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    
    menu.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(m => {
            m.classList.remove('show');
        });
    }
});

// Image preview for create post
const postImageInput = document.getElementById('post-image');
if (postImageInput) {
    postImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('image-preview');
                const img = document.getElementById('preview-img');
                img.src = e.target.result;
                preview.style.display = 'block';
                document.getElementById('video-preview').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Video preview for create post
const postVideoInput = document.getElementById('post-video');
if (postVideoInput) {
    postVideoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('video-preview');
                const video = document.getElementById('preview-video');
                video.src = e.target.result;
                preview.style.display = 'block';
                document.getElementById('image-preview').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Create post form submission
const createPostForm = document.getElementById('createPostForm');
if (createPostForm) {
    createPostForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(createPostForm);
        const content = formData.get('content')?.trim() || '';
        const hasImage = document.getElementById('post-image')?.files?.length > 0;
        const hasVideo = document.getElementById('post-video')?.files?.length > 0;
        
        // Validate that at least one of content, image, or video is provided
        if (!content && !hasImage && !hasVideo) {
            alert('Please add some content, an image, or a video before posting');
            return;
        }
        
        try {
            const response = await fetch('api/create_post.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload page to show new post
                window.location.reload();
            } else {
                alert('Failed to create post: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred');
        }
    });
}

// Toggle reaction
async function toggleReaction(postId, button) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('api/toggle_reaction.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const countSpan = button.querySelector('.count');
            countSpan.textContent = data.count;
            
            if (data.action === 'added') {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Toggle comments section
async function toggleComments(postId) {
    const commentsSection = document.getElementById('comments-' + postId);
    const commentsList = commentsSection.querySelector('.comments-list');
    
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        
        // Load comments
        try {
            const response = await fetch('api/comments.php?post_id=' + postId);
            const data = await response.json();
            
            if (data.success) {
                displayComments(commentsList, data.comments);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    } else {
        commentsSection.style.display = 'none';
    }
}

// Display comments
function displayComments(container, comments) {
    container.innerHTML = '';
    
    comments.forEach(comment => {
        const commentEl = document.createElement('div');
        commentEl.className = 'comment-item';
        
        const avatar = comment.avatar_url 
            ? `<img src="${escapeHtml(comment.avatar_url)}" alt="Avatar">`
            : `<div class="avatar-placeholder">${comment.display_name.charAt(0).toUpperCase()}</div>`;
        
        commentEl.innerHTML = `
            <div class="user-avatar-sm">
                ${avatar}
            </div>
            <div class="comment-content">
                <h5>${escapeHtml(comment.display_name)}</h5>
                <p>${escapeHtml(comment.content)}</p>
                <span class="text-muted" style="font-size: 12px;">${timeAgo(comment.created_at)}</span>
            </div>
        `;
        
        container.appendChild(commentEl);
    });
}

// Submit comment
async function submitComment(e, postId) {
    e.preventDefault();
    
    const form = e.target;
    const input = form.querySelector('input');
    const content = input.value.trim();
    
    if (!content) {
        alert('Please write a comment');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', content);
        
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            
            // Reload comments
            const commentsList = form.closest('.comments-section').querySelector('.comments-list');
            const commentsResponse = await fetch('api/comments.php?post_id=' + postId);
            const commentsData = await commentsResponse.json();
            displayComments(commentsList, commentsData.comments);
            
            // Update comment count
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            const commentBtn = postCard.querySelector('.action-btn:nth-child(2) .count');
            commentBtn.textContent = parseInt(commentBtn.textContent) + 1;
        } else {
            alert('Failed to post comment: ' + (data.error || 'Unknown error'));
            console.error('Comment error:', data);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while posting the comment');
    }
}

// Delete post
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) return;
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('api/delete_post.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            postCard.remove();
        } else {
            alert('Failed to delete post');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}

// Report post
async function reportPost(postId) {
    const reason = prompt('Why are you reporting this post?\n\n1. Spam\n2. Harassment\n3. Inappropriate\n4. Misinformation\n5. Other\n\nEnter number (1-5):');
    
    if (!reason) return;
    
    const reasons = ['spam', 'harassment', 'inappropriate', 'misinformation', 'other'];
    const reasonIndex = parseInt(reason) - 1;
    
    if (reasonIndex < 0 || reasonIndex >= reasons.length) {
        alert('Invalid option');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('reason', reasons[reasonIndex]);
        
        const response = await fetch('api/report_post.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Report submitted. Thank you for keeping our community safe!');
        } else {
            alert('Failed to submit report');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper: Time ago
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Check for new messages periodically
if (window.location.pathname.includes('messages.php')) {
    setInterval(checkNewMessages, 30000); // Check every 30 seconds
}

async function checkNewMessages() {
    // This would be implemented to check for new messages
    // For now, it's a placeholder
}
