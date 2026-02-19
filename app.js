// PolyChat - 实时聊天室前端
class PolyChat {
    constructor() {
        this.username = localStorage.getItem('polychat_username') || this.generateUsername();
        this.color = localStorage.getItem('polychat_color') || this.randomColor();
        this.targetLang = localStorage.getItem('polychat_lang') || 'zh';
        this.messages = [];
        this.pollingInterval = null;
        this.lastMessageTime = 0;
        
        localStorage.setItem('polychat_username', this.username);
        localStorage.setItem('polychat_color', this.color);
        
        this.init();
    }
    
    generateUsername() {
        const adjectives = ['酷', '萌', '帅', '稳', '飞', '浪', '星', '月', '云', '风', '龙', '虎'];
        const nouns = ['哥', '弟', '酱', '仔', '侠', '客', '人', '鸟', '鱼', '猫', '狗', '兔'];
        const num = Math.floor(Math.random() * 1000);
        return adjectives[Math.floor(Math.random() * adjectives.length)] + 
               nouns[Math.floor(Math.random() * nouns.length)] + 
               num;
    }
    
    randomColor() {
        const colors = ['#6366f1', '#ec4899', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#64748b'];
        return colors[Math.floor(Math.random() * colors.length)];
    }
    
    init() {
        this.loadSettings();
        this.bindEvents();
        this.loadMessages();
        this.startSSE();
    }
    
    startSSE() {
        // Use Server-Sent Events for real-time updates
        if (typeof EventSource !== 'undefined') {
            const lastId = this.messages.length > 0 ? this.messages[this.messages.length - 1].id : 0;
            this.eventSource = new EventSource('sse.php?last_id=' + lastId);
            
            this.eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'new_messages') {
                    // Add new messages to the list
                    data.messages.forEach(msg => {
                        if (!this.messages.find(m => m.id === msg.id)) {
                            this.messages.push(msg);
                        }
                    });
                    this.renderMessages();
                    this.scrollToBottom();
                }
            };
            
            this.eventSource.onerror = () => {
                // Fallback to polling if SSE fails
                this.startPolling();
            };
        } else {
            // Fallback for older browsers
            this.startPolling();
        }
    }
    
    loadSettings() {
        document.getElementById('username').value = this.username;
        document.getElementById('targetLang').value = this.targetLang;
        
        document.querySelectorAll('.color-option').forEach(el => {
            if (el.dataset.color === this.color) {
                el.classList.add('selected');
            }
        });
    }
    
    bindEvents() {
        document.getElementById('sendBtn').addEventListener('click', () => this.sendMessage());
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        document.getElementById('username').addEventListener('change', (e) => {
            this.username = e.target.value.trim();
            localStorage.setItem('polychat_username', this.username);
        });
        
        const savedColor = localStorage.getItem('polychat_color') || this.color;
        document.querySelectorAll('.color-option').forEach(el => {
            if (el.dataset.color === savedColor) {
                el.classList.add('selected');
            }
            el.addEventListener('click', () => {
                document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                el.classList.add('selected');
                this.color = el.dataset.color;
                localStorage.setItem('polychat_color', this.color);
            });
        });
        
        document.getElementById('targetLang').addEventListener('change', (e) => {
            this.targetLang = e.target.value;
            localStorage.setItem('polychat_lang', this.targetLang);
        });
        
        document.querySelectorAll('.emoji-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const emoji = btn.dataset.emoji;
                this.sendEmojiMessage(emoji);
            });
        });
    }
    
    async sendMessage() {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        
        if (!text) return;
        if (!this.username || this.username.length < 2) {
            alert('请先输入用户名！');
            document.getElementById('username').focus();
            return;
        }
        
        this.username = document.getElementById('username').value.trim() || this.username;
        localStorage.setItem('polychat_username', this.username);
        
        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';
        
        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('username', this.username);
            formData.append('text', text);
            formData.append('color', this.color);
            formData.append('target_lang', this.targetLang);
            formData.append('emoji', '');
            
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                input.value = '';
                this.addMessage(result.message);
                this.scrollToBottom();
            } else {
                alert('发送失败: ' + (result.error || '未知错误'));
            }
        } catch (error) {
            console.error('发送错误:', error);
            alert('网络错误，请重试');
        } finally {
            btn.disabled = false;
            const sendText = i18n ? i18n.t('send') : '发送';
            btn.innerHTML = '🚀 ' + sendText;
        }
    }
    
    async sendEmojiMessage(emoji) {
        if (!this.username || this.username.length < 2) {
            this.username = document.getElementById('username').value.trim() || this.username;
        }
        
        if (!this.username || this.username.length < 2) {
            alert('请先输入用户名！');
            document.getElementById('username').focus();
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('username', this.username);
            formData.append('text', emoji + ' ' + emoji + ' ' + emoji);
            formData.append('color', this.color);
            formData.append('target_lang', this.targetLang);
            formData.append('emoji', emoji);
            
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.addMessage(result.message);
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('发送错误:', error);
        }
    }
    
    async loadMessages() {
        try {
            const response = await fetch('api.php?action=messages&limit=50');
            const result = await response.json();
            
            if (result.success) {
                const newMessages = result.messages;
                const latestTime = newMessages.length > 0 ? newMessages[newMessages.length - 1].created_at : '';
                
                // Only update if there are new messages (by timestamp)
                if (latestTime && latestTime !== this.lastMessageTime) {
                    this.lastMessageTime = latestTime;
                    this.messages = newMessages;
                    this.renderMessages();
                }
            }
        } catch (error) {
            console.error('加载消息错误:', error);
        }
    }
    
    startPolling() {
        this.pollingInterval = setInterval(() => {
            this.loadMessages();
        }, 3000);
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        
        if (this.messages.length === 0) {
            const emptyText = i18n ? i18n.t('emptyState') : '还没有消息';
            const emptySub = i18n ? i18n.t('emptyStateSub') : '成为第一个说话的人吧！';
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p>${emptyText}</p>
                    <p>${emptySub}</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.messages.map(msg => this.createMessageHTML(msg)).join('');
        
        container.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const msgId = parseInt(btn.dataset.msgId);
                this.likeMessage(msgId);
            });
        });
        
        this.scrollToBottom();
    }
    
    addMessage(msg) {
        this.messages.push(msg);
        this.lastMessageTime = msg.created_at;
        this.renderMessages();
    }
    
    async likeMessage(msgId) {
        try {
            const formData = new FormData();
            formData.append('action', 'like');
            formData.append('msg_id', msgId);
            formData.append('username', this.username);
            
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                const msg = this.messages.find(m => m.id === msgId);
                if (msg) {
                    msg.likes = result.likes;
                }
                this.renderMessages();
            }
        } catch (error) {
            console.error('点赞错误:', error);
        }
    }
    
    createMessageHTML(msg) {
        const initial = msg.username.charAt(0).toUpperCase();
        const time = this.formatTime(msg.created_at);
        
        let translationHTML = '';
        // Only show translation if it's different from original
        if (msg.translated_text && msg.translated_text !== msg.original_text && msg.target_lang !== msg.original_lang) {
            translationHTML = `
                <div class="message-translation">
                    🌐 ${this.escapeHTML(msg.translated_text)}
                </div>
            `;
        }
        
        const likedBy = msg.liked_by ? JSON.parse(msg.liked_by) : [];
        const isLiked = likedBy.includes(this.username);
        
        return `
            <div class="message">
                <div class="avatar" style="background: ${msg.color}">
                    ${initial}
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="username" style="color: ${msg.color}">${this.escapeHTML(msg.username)}</span>
                        <span class="time">${time}</span>
                    </div>
                    <div class="message-text">${this.escapeHTML(msg.original_text)}</div>
                    ${translationHTML}
                    <div class="message-actions">
                        <button class="like-btn ${isLiked ? 'liked' : ''}" data-msg-id="${msg.id}">
                            ${isLiked ? '❤️' : '🤍'} <span class="like-count">${msg.likes || 0}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    formatTime(timestamp) {
        // Parse timestamp - treat as UTC then convert to Tokyo
        const date = new Date(timestamp);
        
        if (isNaN(date.getTime())) {
            // Fallback: try parsing manually
            const parts = timestamp.split(/[- :]/);
            if (parts.length >= 6) {
                date = new Date(Date.UTC(parts[0], parts[1]-1, parts[2], parts[3], parts[4], parts[5]));
            }
        }
        
        // Convert to Tokyo time (UTC+9)
        const tokyoOffset = 9 * 60 * 60 * 1000;
        const tokyoTime = new Date(date.getTime() + tokyoOffset);
        
        // Current time in Tokyo
        const now = new Date();
        const nowUTC = now.getTime() - (now.getTimezoneOffset() * 60000);
        const nowTokyo = new Date(nowUTC + tokyoOffset);
        
        const diff = nowTokyo - tokyoTime;
        
        if (diff < 60000) return '刚刚';
        if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
        if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
        
        // Format: 2月19日 17:31
        const months = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        const month = months[tokyoTime.getMonth()];
        const day = tokyoTime.getDate();
        const hour = tokyoTime.getHours().toString().padStart(2, '0');
        const min = tokyoTime.getMinutes().toString().padStart(2, '0');
        
        return month + day + '日 ' + hour + ':' + min;
    }
    
    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }
    
    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chat = new PolyChat();
});
