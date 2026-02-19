// PolyChat - 实时聊天室前端
class PolyChat {
    constructor() {
        // 如果没有用户名，自动生成一个
        this.username = localStorage.getItem('polychat_username') || this.generateUsername();
        this.color = localStorage.getItem('polychat_color') || this.randomColor();
        this.targetLang = localStorage.getItem('polychat_lang') || 'zh';
        this.messages = [];
        this.pollingInterval = null;
        this.lastMessageId = 0;
        this.lastUpdateTime = 0;
        
        // 保存自动生成的用户名
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
        this.startPolling();
    }
    
    loadSettings() {
        document.getElementById('username').value = this.username;
        document.getElementById('targetLang').value = this.targetLang;
        
        // 选中颜色
        document.querySelectorAll('.color-option').forEach(el => {
            if (el.dataset.color === this.color) {
                el.classList.add('selected');
            }
        });
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
        
        // 用户设置
        document.getElementById('username').addEventListener('change', (e) => {
            this.username = e.target.value.trim();
            localStorage.setItem('polychat_username', this.username);
        });
        
        // 颜色选择
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
        
        // 语言选择
        document.getElementById('targetLang').addEventListener('change', (e) => {
            this.targetLang = e.target.value;
            localStorage.setItem('polychat_lang', this.targetLang);
        });
        
        // 表情选择
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
        
        // 更新用户名
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
            btn.innerHTML = i18n.t('send');
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
                // 检查是否有新消息，避免不必要的重渲染
                const newMessages = result.messages;
                const hasNew = newMessages.length > this.messages.length || 
                    (newMessages.length > 0 && newMessages[newMessages.length - 1].id > this.lastMessageId);
                
                if (hasNew) {
                    this.messages = newMessages;
                    this.lastMessageId = newMessages.length > 0 ? newMessages[newMessages.length - 1].id : 0;
                    this.renderMessages();
                }
            }
        } catch (error) {
            console.error('加载消息错误:', error);
        }
    }
    
    startPolling() {
        // 减少轮询频率，从3秒改为5秒，减少闪烁
        this.pollingInterval = setInterval(() => {
            this.loadMessages();
        }, 5000);
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p data-i18n="emptyState">${i18n.t('emptyState')}</p>
                    <p data-i18n="emptyStateSub">${i18n.t('emptyStateSub')}</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.messages.map(msg => this.createMessageHTML(msg)).join('');
        
        // 绑定点赞事件
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
                // 更新本地消息的点赞数
                const msg = this.messages.find(m => m.id === msgId);
                if (msg) {
                    msg.likes = result.likes;
                    msg.liked_by = msg.liked_by ? JSON.parse(msg.liked_by) : [];
                    if (result.unliked) {
                        msg.liked_by = msg.liked_by.filter(u => u !== this.username);
                    } else {
                        msg.liked_by.push(this.username);
                    }
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
        if (msg.translated_text && msg.translated_text !== msg.original_text) {
            translationHTML = `
                <div class="message-translation">
                    🌐 ${this.escapeHTML(msg.translated_text)}
                </div>
            `;
        }
        
        // 点赞状态
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
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return '刚刚';
        if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
        if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
        
        return date.toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' }) + 
               ' ' + date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
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

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    window.chat = new PolyChat();
});
