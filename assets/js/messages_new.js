// Messaging System - Full Rewrite
let currentConversationId = null;
let messageRefreshInterval = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Messaging system initialized');
});

// Load conversation
function loadConversation(conversationId) {
    console.log('Loading conversation:', conversationId);
    currentConversationId = conversationId;
    
    // Update UI
    const chatArea = document.getElementById('chatArea');
    
    // Mark as active
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.classList.remove('active');
    });
    document.querySelector(`[data-conv-id="${conversationId}"]`)?.classList.add('active');
    
    // Show loading state
    chatArea.innerHTML = `
        <div class="chat-header">
            <h3>Loading conversation...</h3>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-input">
            <form onsubmit="sendMessage(event)">
                <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    `;
    
    // Load messages
    loadMessages();
    
    // Start auto-refresh
    if (messageRefreshInterval) clearInterval(messageRefreshInterval);
    messageRefreshInterval = setInterval(refreshMessages, 2000);
}

// Load messages from API
async function loadMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages_new.php?conversation_id=${currentConversationId}`);
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load messages:', data.error);
            return;
        }
        
        displayMessages(data.messages);
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Display messages
function displayMessages(messages) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">No messages yet. Start the conversation!</div>';
        return;
    }
    
    messages.forEach(msg => {
        const msgEl = createMessageElement(msg);
        container.appendChild(msgEl);
    });
    
    // Auto-scroll to bottom
    container.scrollTop = container.scrollHeight;
}

// Create message element
function createMessageElement(message) {
    const div = document.createElement('div');
    const isOwn = message.sender_id == window.currentUserId;
    div.className = 'message' + (isOwn ? ' own' : '');
    div.setAttribute('data-msg-id', message.id);
    
    const avatar = message.avatar_url 
        ? `<img src="${escapeHtml(message.avatar_url)}" alt="${message.display_name}">`
        : `<div class="avatar-placeholder">${message.display_name.charAt(0).toUpperCase()}</div>`;
    
    const timeStr = new Date(message.created_at).toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    div.innerHTML = `
        <div class="user-avatar-sm">
            ${avatar}
        </div>
        <div class="message-bubble">
            <strong>${escapeHtml(message.display_name)}</strong>
            <p>${escapeHtml(message.content)}</p>
            <span class="message-time">${timeStr}</span>
        </div>
    `;
    
    return div;
}

// Send message
async function sendMessage(e) {
    e.preventDefault();
    
    if (!currentConversationId) {
        alert('Please select a conversation');
        return;
    }
    
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content) {
        alert('Please type a message');
        return;
    }
    
    const button = e.target.querySelector('button');
    button.disabled = true;
    button.textContent = 'Sending...';
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('content', content);
        
        const response = await fetch('api/messages_new.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert('Error: ' + data.error);
            console.error('Send error:', data);
            return;
        }
        
        // Clear input
        input.value = '';
        
        // Add message to display
        const container = document.getElementById('chatMessages');
        if (container.textContent.includes('No messages yet')) {
            container.innerHTML = '';
        }
        
        const msgEl = createMessageElement(data.message);
        container.appendChild(msgEl);
        container.scrollTop = container.scrollHeight;
        
    } catch (error) {
        alert('Failed to send message. Check your connection.');
        console.error('Send error:', error);
    } finally {
        button.disabled = false;
        button.textContent = 'Send';
    }
}

// Refresh messages (check for new ones)
async function refreshMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages_new.php?conversation_id=${currentConversationId}`);
        const data = await response.json();
        
        if (!data.success) return;
        
        const container = document.getElementById('chatMessages');
        if (!container) return;
        
        // Get existing message IDs
        const existingIds = Array.from(container.querySelectorAll('[data-msg-id]')).map(el => parseInt(el.getAttribute('data-msg-id')));
        
        // Add new messages
        let added = false;
        data.messages.forEach(msg => {
            if (!existingIds.includes(msg.id)) {
                const msgEl = createMessageElement(msg);
                container.appendChild(msgEl);
                added = true;
            }
        });
        
        // Auto-scroll if there are new messages and user is near bottom
        if (added) {
            const nearBottom = container.scrollHeight - container.scrollTop < 200;
            if (nearBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }
    } catch (error) {
        console.error('Refresh error:', error);
    }
}

// Show new message modal
function showNewMessageModal() {
    const modal = document.getElementById('newMessageModal');
    if (modal) {
        modal.style.display = 'flex';
        const input = document.getElementById('userSearch');
        if (input) input.focus();
    }
}

// Close modal
function closeModal() {
    const modal = document.getElementById('newMessageModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('userSearch').value = '';
        document.getElementById('userResults').innerHTML = '';
    }
}

// Search users for new message
let searchTimeout;
async function searchUsers(query) {
    clearTimeout(searchTimeout);
    
    if (!query.trim()) {
        document.getElementById('userResults').innerHTML = '';
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`api/search_users.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            const container = document.getElementById('userResults');
            container.innerHTML = '';
            
            if (!data.users || data.users.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">No users found</p>';
                return;
            }
            
            data.users.forEach(user => {
                const avatar = user.avatar_url 
                    ? `<img src="${escapeHtml(user.avatar_url)}" alt="${user.display_name}">`
                    : `<div class="avatar-placeholder">${user.display_name.charAt(0).toUpperCase()}</div>`;
                
                const userEl = document.createElement('div');
                userEl.className = 'user-item';
                userEl.style.cursor = 'pointer';
                userEl.innerHTML = `
                    <div class="user-avatar-sm">
                        ${avatar}
                    </div>
                    <div class="user-info">
                        <h5>${escapeHtml(user.display_name)}</h5>
                        <p class="text-muted">@${escapeHtml(user.username)}</p>
                    </div>
                `;
                
                userEl.onclick = () => {
                    window.location.href = `messages.php?user=${user.id}`;
                };
                
                container.appendChild(userEl);
            });
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make conversation items clickable
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.conversation-item').forEach(item => {
        if (!item.dataset.convId) {
            const match = item.getAttribute('onclick')?.match(/loadConversation\((\d+)\)/);
            if (match) {
                item.setAttribute('data-conv-id', match[1]);
            }
        }
    });
});
