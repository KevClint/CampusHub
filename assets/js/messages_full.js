// Messaging System v2 - Full Featured
let currentConversationId = null;
let currentPartnerInfo = null;
let messageRefreshInterval = null;
let pinnedMessagesVisible = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Messaging system v2 initialized');
});

// Load conversation
async function loadConversation(conversationId) {
    console.log('Loading conversation:', conversationId);
    currentConversationId = conversationId;
    pinnedMessagesVisible = false;
    
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
            <div class="chat-header-actions">
                <button class="btn btn-secondary btn-sm" onclick="showDetailsModal()" title="Conversation details">ℹ️ Details</button>
                <button class="btn btn-secondary btn-sm" onclick="togglePinnedMessages()" title="Pinned messages">📌 Pinned</button>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="pinned-messages-panel" id="pinnedPanel" style="display: none;">
            <div class="pinned-header">
                <h4>Pinned Messages</h4>
                <button onclick="togglePinnedMessages()" style="background: none; border: none; cursor: pointer; font-size: 18px;">✕</button>
            </div>
            <div class="pinned-list" id="pinnedList"></div>
        </div>
        <div class="chat-input">
            <form onsubmit="sendMessage(event)">
                <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
                <label for="fileInput" class="btn btn-secondary btn-sm" style="margin: 0; cursor: pointer;">📎</label>
                <input type="file" id="fileInput" style="display: none;">
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
            <div id="filePreview" style="display: none; padding: 8px; background: var(--bg-secondary); border-radius: 8px; margin-top: 8px; font-size: 13px;"></div>
        </div>
    `;
    
    // Set up file input listener
    const fileInput = document.getElementById('fileInput');
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = `📄 ${escapeHtml(file.name)} (${(file.size / 1024).toFixed(2)} KB)`;
            preview.style.display = 'block';
        }
    });
    
    // Load messages and pinned messages
    await loadPinnedMessages();
    loadMessages();
    
    // Start auto-refresh
    if (messageRefreshInterval) clearInterval(messageRefreshInterval);
    messageRefreshInterval = setInterval(refreshMessages, 2000);
}

// Load messages from API
async function loadMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages_v2.php?conversation_id=${currentConversationId}`);
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load messages:', data.error);
            return;
        }
        
        // Update header with partner name from first message or conversation item
        if (data.messages && data.messages.length > 0) {
            const firstMsg = data.messages[0];
            currentPartnerInfo = {
                display_name: firstMsg.display_name,
                username: firstMsg.username,
                avatar_url: firstMsg.avatar_url,
                sender_id: firstMsg.sender_id
            };
            const headerEl = document.querySelector('.chat-header h3');
            if (headerEl) {
                headerEl.textContent = escapeHtml(firstMsg.display_name);
            }
        } else {
            // Try to get partner name from conversation item
            const convItem = document.querySelector(`[data-conv-id="${currentConversationId}"]`);
            if (convItem) {
                const nameEl = convItem.querySelector('h4');
                if (nameEl) {
                    currentPartnerInfo = {
                        display_name: nameEl.textContent,
                        avatar_url: null
                    };
                    const headerEl = document.querySelector('.chat-header h3');
                    if (headerEl) {
                        headerEl.textContent = nameEl.textContent;
                    }
                }
            }
        }
        
        displayMessages(data.messages);
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Load pinned messages
async function loadPinnedMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages_v2.php?conversation_id=${currentConversationId}&action=pinned`);
        const data = await response.json();
        
        if (data.success) {
            window.pinnedMessages = data.pinned_messages || [];
        }
    } catch (error) {
        console.error('Error loading pinned messages:', error);
    }
}

// Toggle pinned messages panel
function togglePinnedMessages() {
    pinnedMessagesVisible = !pinnedMessagesVisible;
    const panel = document.getElementById('pinnedPanel');
    const list = document.getElementById('pinnedList');
    
    if (pinnedMessagesVisible && window.pinnedMessages && window.pinnedMessages.length > 0) {
        panel.style.display = 'block';
        list.innerHTML = '';
        window.pinnedMessages.forEach(msg => {
            const el = document.createElement('div');
            el.className = 'pinned-item';
            el.innerHTML = `
                <strong>${escapeHtml(msg.display_name)}</strong>
                <p>${escapeHtml(msg.content)}</p>
            `;
            el.style.cursor = 'pointer';
            el.onclick = () => scrollToMessage(msg.id);
            list.appendChild(el);
        });
    } else {
        panel.style.display = 'none';
    }
}

// Scroll to message
function scrollToMessage(messageId) {
    const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
    if (msgEl) {
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        msgEl.style.backgroundColor = 'yellow';
        setTimeout(() => msgEl.style.backgroundColor = '', 2000);
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
        if (!msg.unsent_for_all || msg.sender_id == window.currentUserId) {
            const msgEl = createMessageElement(msg);
            
            // Check if message is pinned
            if (window.pinnedMessages && window.pinnedMessages.some(pm => pm.id === msg.id)) {
                msgEl.classList.add('pinned');
            }
            
            container.appendChild(msgEl);
        }
    });
    
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
    
    let content = escapeHtml(message.content);
    if (message.is_unsent) {
        content = `<em style="color: var(--text-muted);">You unsent this message</em>`;
    } else if (message.file_url) {
        content += `<br><a href="${message.file_url}" download="${message.file_name}" style="color: var(--primary); text-decoration: none;">📎 ${escapeHtml(message.file_name)}</a>`;
    }
    
    div.innerHTML = `
        <div class="user-avatar-sm">
            ${avatar}
        </div>
        <div class="message-bubble">
            <strong>${escapeHtml(message.display_name)}</strong>
            <p>${content}</p>
            <span class="message-time">${timeStr}</span>
        </div>
        <div class="message-actions">
            ${isOwn ? `
                <button class="msg-btn" onclick="editMessage(${message.id})" title="Edit">✏️</button>
                <button class="msg-btn" onclick="unsendMessage(${message.id})" title="Unsend">↩️</button>
                <button class="msg-btn" onclick="deleteMessage(${message.id})" title="Delete">🗑️</button>
            ` : ''}
            <button class="msg-btn" onclick="pinMessage(${message.id})" title="Pin">📌</button>
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
    const fileInput = document.getElementById('fileInput');
    const content = input.value.trim();
    const file = fileInput.files[0];
    
    if (!content && !file) {
        alert('Please type a message or select a file');
        return;
    }
    
    const button = e.target.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Sending...';
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('content', content);
        formData.append('action', 'send');
        if (file) formData.append('file', file);
        
        const response = await fetch('api/messages_v2.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert('Error: ' + data.error);
            console.error('Send error:', data);
            return;
        }
        
        // Clear inputs
        input.value = '';
        fileInput.value = '';
        document.getElementById('filePreview').style.display = 'none';
        
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

// Edit message
async function editMessage(messageId) {
    const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
    const currentContent = msgEl.querySelector('p').textContent.replace(' (edited)', '').trim();
    
    // Show edit modal
    showEditModal(messageId, currentContent);
}

// Show edit modal
function showEditModal(messageId, currentContent) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Edit Message</h3>
                <button class="btn-close" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <textarea id="editContent" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 14px; min-height: 80px;" placeholder="Edit your message...">${escapeHtml(currentContent)}</textarea>
            </div>
            <div class="modal-header" style="justify-content: flex-end; gap: 8px;">
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                <button class="btn btn-primary" onclick="submitEdit(${messageId}, document.getElementById('editContent').value, this)">Save</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Submit edit
async function submitEdit(messageId, newContent, button) {
    if (!newContent.trim()) {
        alert('Message cannot be empty');
        return;
    }
    
    button.disabled = true;
    button.textContent = 'Saving...';
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message_id', messageId);
        formData.append('new_content', newContent);
        formData.append('action', 'edit');
        
        const response = await fetch('api/messages_v2.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
            const contentEl = msgEl.querySelector('p');
            contentEl.innerHTML = escapeHtml(newContent) + ' <em style="font-size: 11px; color: var(--text-muted);">(edited)</em>';
            document.querySelector('.modal').remove();
        } else {
            alert('Error: ' + data.error);
            button.disabled = false;
            button.textContent = 'Save';
        }
    } catch (error) {
        alert('Failed to edit message');
        button.disabled = false;
        button.textContent = 'Save';
    }
}

// Unsend message
async function unsendMessage(messageId) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Unsend Message</h3>
                <button class="btn-close" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px; cursor: pointer;">
                    <input type="radio" name="unsend_option" value="for_you" checked>
                    <span>Unsend for you only</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="radio" name="unsend_option" value="for_all">
                    <span>Unsend for everyone</span>
                </label>
            </div>
            <div class="modal-header" style="justify-content: flex-end; gap: 8px;">
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmUnsend(${messageId}, this)">Unsend</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Confirm unsend
async function confirmUnsend(messageId, button) {
    const modal = button.closest('.modal');
    const unsendForAll = modal.querySelector('input[name="unsend_option"]:checked').value === 'for_all';
    
    button.disabled = true;
    button.textContent = 'Unsending...';
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message_id', messageId);
        formData.append('action', 'unsend');
        formData.append('unsend_for_all', unsendForAll ? 'true' : 'false');
        
        const response = await fetch('api/messages_v2.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (unsendForAll) {
                const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
                if (msgEl) msgEl.remove();
            } else {
                const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
                if (msgEl) {
                    msgEl.querySelector('p').textContent = 'You unsent a message';
                    msgEl.style.opacity = '0.6';
                }
            }
            modal.remove();
        } else {
            alert('Error: ' + data.error);
            button.disabled = false;
            button.textContent = 'Unsend';
        }
    } catch (error) {
        alert('Failed to unsend message');
        button.disabled = false;
        button.textContent = 'Unsend';
    }
}

// Delete message with confirmation
async function deleteMessage(messageId) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Delete Message</h3>
                <button class="btn-close" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this message? This action cannot be undone.</p>
            </div>
            <div class="modal-header" style="justify-content: flex-end; gap: 8px;">
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete(${messageId}, this)">Delete</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Confirm delete
async function confirmDelete(messageId, button) {
    button.disabled = true;
    button.textContent = 'Deleting...';
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message_id', messageId);
        formData.append('action', 'delete');
        
        const response = await fetch('api/messages_v2.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
            if (msgEl) msgEl.remove();
            document.querySelector('.modal').remove();
        } else {
            alert('Error: ' + data.error);
            button.disabled = false;
            button.textContent = 'Delete';
        }
    } catch (error) {
        alert('Failed to delete message');
        button.disabled = false;
        button.textContent = 'Delete';
    }
}

// Pin message
async function pinMessage(messageId) {
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message_id', messageId);
        formData.append('action', 'pin');
        
        const response = await fetch('api/messages_v2.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const msgEl = document.querySelector(`[data-msg-id="${messageId}"]`);
            if (data.pinned) {
                msgEl.classList.add('pinned');
            } else {
                msgEl.classList.remove('pinned');
            }
            loadPinnedMessages();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Failed to pin message');
        console.error('Error:', error);
    }
}

// Refresh messages
async function refreshMessages() {
    if (!currentConversationId) return;
    
    try {
        const response = await fetch(`api/messages_v2.php?conversation_id=${currentConversationId}`);
        const data = await response.json();
        
        if (!data.success) return;
        
        const container = document.getElementById('chatMessages');
        if (!container) return;
        
        const existingIds = Array.from(container.querySelectorAll('[data-msg-id]')).map(el => parseInt(el.getAttribute('data-msg-id')));
        
        let added = false;
        data.messages.forEach(msg => {
            if (!existingIds.includes(msg.id)) {
                const msgEl = createMessageElement(msg);
                
                // Check if message is pinned
                if (window.pinnedMessages && window.pinnedMessages.some(pm => pm.id === msg.id)) {
                    msgEl.classList.add('pinned');
                }
                
                container.appendChild(msgEl);
                added = true;
            }
        });
        
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

// Show details modal
function showDetailsModal() {
    if (!currentPartnerInfo) {
        alert('User information not available');
        return;
    }
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    
    const avatar = currentPartnerInfo.avatar_url
        ? `<img src="${escapeHtml(currentPartnerInfo.avatar_url)}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`
        : `<div class="avatar-placeholder" style="font-size: 48px;">${currentPartnerInfo.display_name.charAt(0).toUpperCase()}</div>`;
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Conversation Details</h3>
                <button class="btn-close" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <div class="user-avatar-sm" style="width: 120px; height: 120px; margin: 0 auto 20px;">
                    ${avatar}
                </div>
                <h3 style="margin: 20px 0 8px;">${escapeHtml(currentPartnerInfo.display_name)}</h3>
                ${currentPartnerInfo.username ? `<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">@${escapeHtml(currentPartnerInfo.username)}</p>` : ''}
                <div style="background: var(--bg-secondary); padding: 16px; border-radius: 8px; text-align: left;">
                    <p style="margin: 0; color: var(--text-muted); font-size: 12px; margin-bottom: 8px;">USER ID</p>
                    <p style="margin: 0 0 16px; font-weight: 600;">${currentPartnerInfo.sender_id || 'N/A'}</p>
                    <p style="margin: 0; color: var(--text-muted); font-size: 12px; margin-bottom: 8px;">CONVERSATION ID</p>
                    <p style="margin: 0; font-weight: 600;">${currentConversationId}</p>
                </div>
            </div>
            <div class="modal-header" style="justify-content: flex-end;">
                <button class="btn btn-primary" onclick="this.closest('.modal').remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
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
