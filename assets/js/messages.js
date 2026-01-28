// Messages functionality

let currentConversationId = null;
let messageCheckInterval = null;

// Load conversation
async function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    const chatArea = document.getElementById('chatArea');
    chatArea.innerHTML = `
        <div class="chat-header">
            <div class="user-avatar-sm">
                <div class="avatar-placeholder">👤</div>
            </div>
            <h3 id="chatHeaderName">Loading...</h3>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div style="text-align: center; color: var(--text-muted);">Loading messages...</div>
        </div>
        <div class="chat-input">
            <form onsubmit="sendMessage(event)">
                <input type="text" id="messageInput" placeholder="Type a message..." required>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    `;
    
    // Load messages and conversation info
    try {
        const response = await fetch(`api/messages.php?conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success) {
            displayMessages(data.messages);
            
            // Get conversation partner name from first message or use default
            let partnerName = 'Conversation';
            if (data.messages && data.messages.length > 0) {
                // Find a message from someone other than current user
                const otherUserMsg = data.messages.find(m => m.sender_id != getCurrentUserId());
                if (otherUserMsg) {
                    partnerName = otherUserMsg.display_name;
                }
            } else {
                // No messages yet, try to get from conversation-item
                const activeItem = document.querySelector(`[onclick="loadConversation(${conversationId})"]`);
                if (activeItem) {
                    const nameEl = activeItem.querySelector('.conversation-info h4');
                    if (nameEl) {
                        partnerName = nameEl.textContent;
                    }
                }
            }
            
            document.getElementById('chatHeaderName').textContent = partnerName;
            
            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[onclick="loadConversation(${conversationId})"]`)?.classList.add('active');
            
            // Start polling for new messages
            if (messageCheckInterval) {
                clearInterval(messageCheckInterval);
            }
            messageCheckInterval = setInterval(() => checkForNewMessages(conversationId), 3000);
        } else {
            document.getElementById('chatMessages').innerHTML = `<div style="text-align: center; color: var(--text-danger); padding: 20px;">Error: ${data.error || 'Failed to load messages'}</div>`;
            console.error('API Error:', data);
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('chatMessages').innerHTML = `<div style="text-align: center; color: var(--text-danger); padding: 20px;">Connection error. Please try again.</div>`;
    }
}

// Display messages
function displayMessages(messages) {
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: var(--text-muted); margin-top: 40px;">No messages yet. Start the conversation!</div>';
        return;
    }
    
    messages.forEach(message => {
        const messageEl = createMessageElement(message);
        container.appendChild(messageEl);
    });
    
    // Scroll to bottom
    setTimeout(() => {
        container.scrollTop = container.scrollHeight;
    }, 100);
}

// Create message element
function createMessageElement(message) {
    const div = document.createElement('div');
    div.className = 'message' + (message.sender_id == getCurrentUserId() ? ' own' : '');
    div.dataset.messageId = message.id;
    
    // Safe access to display_name with fallback
    const displayName = message.display_name || message.username || 'Unknown User';
    const firstLetter = displayName.charAt(0).toUpperCase();
    
    const avatar = message.avatar_url 
        ? `<img src="${escapeHtml(message.avatar_url)}" alt="Avatar">`
        : `<div class="avatar-placeholder">${firstLetter}</div>`;
    
    div.innerHTML = `
        <div class="user-avatar-sm">
            ${avatar}
        </div>
        <div class="message-bubble">
            <div class="message-info">
                <strong>${escapeHtml(displayName)}</strong>
            </div>
            <div class="message-text">${escapeHtml(message.content)}</div>
            <div class="message-time">${timeAgo(message.created_at)}</div>
        </div>
    `;
    
    return div;
}

// Send message
async function sendMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    const button = e.target.querySelector('button');
    
    if (!content) {
        alert('Please write a message');
        return;
    }
    
    if (!currentConversationId) {
        alert('Please select a conversation');
        return;
    }
    
    // Disable button while sending
    button.disabled = true;
    button.textContent = 'Sending...';
    
    console.log('Sending message:', { conversationId: currentConversationId, content: content });
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('content', content);
        
        console.log('FormData entries:', {
            conversation_id: currentConversationId,
            content: content
        });
        
        const response = await fetch('api/messages.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', {
            contentType: response.headers.get('content-type')
        });
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            alert('Server returned invalid response. Check console for details.');
            throw new Error('Invalid JSON response: ' + responseText);
        }
        
        console.log('Parsed response:', data);
        
        if (data.success) {
            input.value = '';
            
            // Add message to display
            const container = document.getElementById('chatMessages');
            
            // If this is the first message, clear the "No messages yet" text
            if (container.textContent.includes('No messages yet')) {
                container.innerHTML = '';
            }
            
            console.log('Message received:', data.message);
            const messageEl = createMessageElement(data.message);
            container.appendChild(messageEl);
            container.scrollTop = container.scrollHeight;
        } else {
            const errorMsg = data.error || 'Unknown error';
            alert('Failed to send message: ' + errorMsg);
            console.error('API Error Response:', data);
        }
    } catch (error) {
        console.error('Exception caught:', error);
        alert('Error sending message: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = 'Send';
    }
}

// Check for new messages
async function checkForNewMessages(conversationId) {
    if (conversationId !== currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages.php?conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('chatMessages');
            const existingIds = new Set(Array.from(container.querySelectorAll('.message')).map(m => parseInt(m.dataset.messageId)));
            
            let hasNewMessages = false;
            data.messages.forEach(message => {
                if (!existingIds.has(message.id)) {
                    const messageEl = createMessageElement(message);
                    container.appendChild(messageEl);
                    hasNewMessages = true;
                }
            });
            
            // Auto scroll if near bottom
            const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
            if (isNearBottom && hasNewMessages) {
                container.scrollTop = container.scrollHeight;
            }
        }
    } catch (error) {
        console.error('Error checking messages:', error);
    }
}

// Show new message modal
function showNewMessageModal() {
    const modal = document.getElementById('newMessageModal');
    modal.style.display = 'flex';
    document.getElementById('userSearch').focus();
}

// Close modal
function closeModal() {
    const modal = document.getElementById('newMessageModal');
    modal.style.display = 'none';
    document.getElementById('userSearch').value = '';
    document.getElementById('userResults').innerHTML = '';
}

// Search users
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
            
            data.users.forEach(user => {
                const avatar = user.avatar_url 
                    ? `<img src="${escapeHtml(user.avatar_url)}" alt="Avatar">`
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
                
                userEl.addEventListener('click', () => {
                    window.location.href = `messages.php?user=${user.id}`;
                });
                
                container.appendChild(userEl);
            });
            
            if (data.users.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">No users found</p>';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }, 300);
}

// Get current user ID (from session)
function getCurrentUserId() {
    // This should be set from PHP
    return window.currentUserId || null;
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

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
