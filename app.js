// PolyChat - 实时聊天室前端 v2.0
class PolyChat {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
        this.color = this.user.color || localStorage.getItem('polychat_color') || '#6366f1';
        this.targetLang = localStorage.getItem('polychat_lang') || 'zh';
        this.messages = [];
        this.pollingInterval = null;
        this.lastMessageTime = 0;
        
        // 读取上次所在的房间
        this.roomId = parseInt(localStorage.getItem('polychat_last_room')) || 1;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadRooms();
        this.loadMessages();
        this.startSSE();
        
        // 加载房间类型
        this.roomTypes = {};
    }
    
    setRoomType(roomId, type) {
        this.roomTypes[roomId] = type;
        
        const messagesContainer = document.getElementById('messagesContainer');
        const tasksPanel = document.getElementById('tasksPanel');
        const chatInputArea = document.getElementById('chatInputArea');
        
        if (type === 'task') {
            messagesContainer.style.display = 'none';
            tasksPanel.style.display = 'block';
            chatInputArea.style.display = 'none';
            this.loadTasks();
        } else {
            messagesContainer.style.display = 'block';
            tasksPanel.style.display = 'none';
            chatInputArea.style.display = 'block';
        }
    }
    
    async loadTasks() {
        try {
            const response = await fetch(`api.php?action=tasks&room_id=${this.roomId}`);
            const result = await response.json();
            
            if (result.success) {
                this.renderTasks(result.tasks || []);
            }
        } catch (error) {
            console.error('加载任务失败:', error);
        }
    }
    
    renderTasks(tasks) {
        const container = document.getElementById('tasksList');
        
        if (tasks.length === 0) {
            container.innerHTML = '<div class="task-empty">暂无任务，点击上方添加</div>';
            return;
        }
        
        container.innerHTML = tasks.map(task => `
            <div class="task-item ${task.completed ? 'completed' : ''}">
                <input type="checkbox" class="task-checkbox" ${task.completed ? 'checked' : ''} 
                    onchange="window.chat.toggleTask(${task.id})">
                <span class="task-title">${this.escapeHTML(task.title)}</span>
                <span class="task-delete" onclick="window.chat.deleteTask(${task.id})">×</span>
            </div>
        `).join('');
    }
    
    async addTask() {
        const title = prompt('请输入任务名称:');
        if (!title) return;
        
        const user = this.user;
        
        const formData = new FormData();
        formData.append('room_id', this.roomId);
        formData.append('user_id', user.id || 0);
        formData.append('title', title);
        
        try {
            const response = await fetch('api.php?action=add_task', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                this.loadTasks();
            }
        } catch (error) {
            console.error('添加任务失败:', error);
        }
    }
    
    async toggleTask(taskId) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        
        try {
            await fetch('api.php?action=toggle_task', { method: 'POST', body: formData });
            this.loadTasks();
        } catch (error) {
            console.error('更新任务失败:', error);
        }
    }
    
    async deleteTask(taskId) {
        if (!confirm('确定删除此任务?')) return;
        
        const formData = new FormData();
        formData.append('task_id', taskId);
        
        try {
            await fetch('api.php?action=delete_task', { method: 'POST', body: formData });
            this.loadTasks();
        } catch (error) {
            console.error('删除任务失败:', error);
        }
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
            localStorage.setItem('polychat_last_room', this.roomId);
            this.messages = [];
            this.lastMessageTime = 0;
            document.getElementById('currentRoomName').textContent = e.target.options[e.target.selectedIndex].text;
            this.loadMessages();
            this.reconnectSSE();
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
                    option.dataset.type = room.type;
                    select.appendChild(option);
                });
                
                // 选择上次所在的房间
                select.value = this.roomId;
                
                if (result.rooms.length > 0) {
                    // 确保 roomId 有效
                    const room = result.rooms.find(r => r.id == this.roomId);
                    if (room) {
                        document.getElementById('currentRoomName').textContent = room.name;
                        this.setRoomType(room.id, room.type);
                    } else {
                        this.roomId = result.rooms[0].id;
                        document.getElementById('currentRoomName').textContent = result.rooms[0].name;
                        localStorage.setItem('polychat_last_room', this.roomId);
                    }
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
                // 只保留当前房间的消息
                this.messages = result.messages.filter(m => m.room_id == this.roomId);
                this.renderMessages();
            }
        } catch (error) {
            console.error('加载消息失败:', error);
        }
    }
    
    startSSE() {
        if (typeof EventSource !== 'undefined') {
            this.reconnectSSE();
        } else {
            this.pollingInterval = setInterval(() => this.loadMessages(), 5000);
        }
    }
    
    reconnectSSE() {
        // 关闭旧的 SSE 连接
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        if (typeof EventSource !== 'undefined') {
            this.eventSource = new EventSource(`sse.php?last_id=0&room_id=${this.roomId}`);
            
            this.eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'new_messages') {
                    data.messages.forEach(msg => {
                        // 只添加当前房间的消息
                        if (msg.room_id == this.roomId && !this.messages.find(m => m.id === msg.id)) {
                            this.messages.push(msg);
                        }
                    });
                    this.renderMessages();
                    this.scrollToBottom();
                }
            };
            
            this.eventSource.onerror = () => {
                // 降级到轮询
                this.pollingInterval = setInterval(() => this.loadMessages(), 5000);
            };
        } else {
            this.pollingInterval = setInterval(() => this.loadMessages(), 5000);
        }
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        
        // 过滤当前房间的消息
        const roomMessages = this.messages.filter(m => m.room_id == this.roomId);
        
        if (roomMessages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p>还没有消息</p>
                    <p>成为第一个说话的人吧！</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = roomMessages.map(msg => this.createMessageHTML(msg)).join('');
        
        container.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.likeMessage(parseInt(btn.dataset.msgId));
            });
        });
        
        // 消息操作按钮
        container.querySelectorAll('.msg-edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.editMessage(parseInt(btn.dataset.msgId));
            });
        });
        
        container.querySelectorAll('.msg-delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.deleteMessage(parseInt(btn.dataset.msgId));
            });
        });
    }
    
    addMessage(msg) {
        // 只添加当前房间的消息
        if (msg.room_id == this.roomId) {
            this.messages.push(msg);
            this.lastMessageTime = msg.created_at;
            this.renderMessages();
        }
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
        const isOwn = msg.username === username;
        
        return `
            <div class="message">
                <div class="avatar" style="background: ${msg.color}">${initial}</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="username" style="color: ${msg.color}">${this.escapeHTML(msg.username)}</span>
                        <span class="time">${time}</span>
                        ${isOwn ? `
                            <button class="msg-edit-btn" data-msg-id="${msg.id}">✏️</button>
                            <button class="msg-delete-btn" data-msg-id="${msg.id}">🗑️</button>
                        ` : ''}
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
    
    async editMessage(msgId) {
        const msg = this.messages.find(m => m.id === msgId);
        if (!msg) return;
        
        const newText = prompt('编辑消息:', msg.original_text);
        if (!newText || newText === msg.original_text) return;
        
        const formData = new FormData();
        formData.append('text', newText);
        
        try {
            const response = await fetch(`api.php?action=message&id=${msgId}`, {
                method: 'PUT',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                msg.original_text = newText;
                this.renderMessages();
            }
        } catch (error) {
            console.error('编辑失败:', error);
        }
    }
    
    async deleteMessage(msgId) {
        if (!confirm('确定删除这条消息?')) return;
        
        try {
            const response = await fetch(`api.php?action=message&id=${msgId}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (result.success) {
                this.messages = this.messages.filter(m => m.id !== msgId);
                this.renderMessages();
            }
        } catch (error) {
            console.error('删除失败:', error);
        }
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
