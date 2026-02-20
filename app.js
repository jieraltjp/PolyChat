// PolyChat - 实时聊天室前端 v2.0
class PolyChat {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
        this.color = this.user.color || localStorage.getItem('polychat_color') || '#6366f1';
        this.targetLang = localStorage.getItem('polychat_lang') || 'zh';
        this.messages = [];
        this.pollingInterval = null;
        this.lastMessageTime = 0;
        this.roomId = 1;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadRooms();
        this.loadMessages();
        this.startSSE();
    }
    
    bindEvents() {
        // 发送消息
        document.getElementById('sendBtn').addEventListener('click', () => this.sendMessage());
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // 颜色选择 - 读取之前选择的颜色
        const savedColor = this.user.color || localStorage.getItem('polychat_color') || '#6366f1';
        document.querySelectorAll('.color-option').forEach(el => {
            if (el.dataset.color === savedColor) {
                el.classList.add('selected');
                this.color = savedColor;
            }
            el.addEventListener('click', () => {
                document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                el.classList.add('selected');
                this.color = el.dataset.color;
                localStorage.setItem('polychat_color', this.color);
            });
        });
        
        // 语言选择
        document.getElementById('targetLang').addEventListener('change', (e) => {
            this.targetLang = e.target.value;
            localStorage.setItem('polychat_lang', this.targetLang);
        });
        
        // 房间选择
        document.getElementById('roomSelect').addEventListener('change', (e) => {
            this.roomId = e.target.value;
            this.messages = [];
            this.loadMessages();
        });
        
        // 表情选择
        document.querySelectorAll('.emoji-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.sendEmojiMessage(btn.dataset.emoji);
            });
        });
    }
    
    async loadRooms() {
        try {
            const response = await fetch('api.php?action=rooms');
            const result = await response.json();
            
            if (result.success) {
                const select = document.getElementById('roomSelect');
                select.innerHTML = '';
                
                result.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = room.name;
                    select.appendChild(option);
                });
                
                if (result.rooms.length > 0) {
                    document.getElementById('currentRoomName').textContent = result.rooms[0].name;
                }
            }
        } catch (error) {
            console.error('加载房间失败:', error);
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        
        if (!text) return;
        
        const username = this.user.username || '游客';
        
        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('username', username);
            formData.append('user_id', this.user.id || 0);
            formData.append('text', text);
            formData.append('color', this.color);
            formData.append('target_lang', this.targetLang);
            formData.append('room_id', this.roomId);
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
            }
        } catch (error) {
            console.error('发送失败:', error);
        } finally {
            btn.disabled = false;
        }
    }
    
    async sendEmojiMessage(emoji) {
        const username = this.user.username || '游客';
        
        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('username', username);
            formData.append('user_id', this.user.id || 0);
            formData.append('text', emoji + ' ' + emoji + ' ' + emoji);
            formData.append('color', this.color);
            formData.append('target_lang', this.targetLang);
            formData.append('room_id', this.roomId);
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
            console.error('发送失败:', error);
        }
    }
    
    async loadMessages() {
        try {
            const response = await fetch(`api.php?action=messages&room_id=${this.roomId}&limit=50`);
            const result = await response.json();
            
            if (result.success) {
                const newMessages = result.messages;
                const latestTime = newMessages.length > 0 ? newMessages[newMessages.length - 1].created_at : '';
                
                if (latestTime && latestTime !== this.lastMessageTime) {
                    this.lastMessageTime = latestTime;
                    this.messages = newMessages;
                    this.renderMessages();
                }
            }
        } catch (error) {
            console.error('加载消息失败:', error);
        }
    }
    
    startSSE() {
        if (typeof EventSource !== 'undefined') {
            const lastId = this.messages.length > 0 ? this.messages[this.messages.length - 1].id : 0;
            this.eventSource = new EventSource(`sse.php?last_id=${lastId}&room_id=${this.roomId}`);
            
            this.eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'new_messages') {
                    data.messages.forEach(msg => {
                        if (!this.messages.find(m => m.id === msg.id) && msg.room_id == this.roomId) {
                            this.messages.push(msg);
                        }
                    });
                    this.renderMessages();
                    this.scrollToBottom();
                }
            };
            
            this.eventSource.onerror = () => {
                this.pollingInterval = setInterval(() => this.loadMessages(), 5000);
            };
        } else {
            this.pollingInterval = setInterval(() => this.loadMessages(), 5000);
        }
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p>还没有消息</p>
                    <p>成为第一个说话的人吧！</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.messages.map(msg => this.createMessageHTML(msg)).join('');
        
        container.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.likeMessage(parseInt(btn.dataset.msgId));
            });
        });
    }
    
    addMessage(msg) {
        this.messages.push(msg);
        this.lastMessageTime = msg.created_at;
        this.renderMessages();
    }
    
    async likeMessage(msgId) {
        const username = this.user.username || '游客';
        
        try {
            const formData = new FormData();
            formData.append('action', 'like');
            formData.append('msg_id', msgId);
            formData.append('username', username);
            
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                const msg = this.messages.find(m => m.id === msgId);
                if (msg) msg.likes = result.likes;
                this.renderMessages();
            }
        } catch (error) {
            console.error('点赞失败:', error);
        }
    }
    
    createMessageHTML(msg) {
        const initial = msg.username.charAt(0).toUpperCase();
        const time = this.formatTime(msg.created_at);
        
        let translationHTML = '';
        if (msg.translated_text && msg.translated_text !== msg.original_text) {
            translationHTML = `<div class="message-translation">🌐 ${this.escapeHTML(msg.translated_text)}</div>`;
        }
        
        const likedBy = msg.liked_by ? JSON.parse(msg.liked_by) : [];
        const username = this.user.username || '游客';
        const isLiked = likedBy.includes(username);
        
        return `
            <div class="message">
                <div class="avatar" style="background: ${msg.color}">${initial}</div>
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
        const date = new Date(timestamp);
        const tokyoOffset = 9 * 60 * 60 * 1000;
        const tokyoTime = new Date(date.getTime() + tokyoOffset);
        
        const now = new Date();
        const nowUTC = now.getTime() - (now.getTimezoneOffset() * 60000);
        const nowTokyo = new Date(nowUTC + tokyoOffset);
        
        const diff = nowTokyo - tokyoTime;
        
        if (diff < 60000) return '刚刚';
        if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
        if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
        
        const months = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        return months[tokyoTime.getMonth()] + tokyoTime.getDate() + '日 ' + 
               tokyoTime.getHours().toString().padStart(2,'0') + ':' + 
               tokyoTime.getMinutes().toString().padStart(2,'0');
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
