<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyChat - 多元语言聊天室</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌍</text></svg>">
</head>
<body>
    <!-- 登录/注册弹窗 -->
    <div id="authModal" class="modal">
        <div class="modal-content">
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">登录</button>
                <button class="auth-tab" data-tab="register">注册</button>
            </div>
            
            <!-- 登录表单 -->
            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="loginUsername" placeholder="输入用户名" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" id="loginPassword" placeholder="输入密码" required>
                </div>
                <button type="submit" class="btn-primary">登录</button>
                <p class="auth-tip">未注册？直接输入用户名进入游客模式</p>
            </form>
            
            <!-- 注册表单 -->
            <form id="registerForm" class="auth-form" style="display:none;">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="regUsername" placeholder="2-20字符" required minlength="2" maxlength="20">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" id="regPassword" placeholder="至少6位" required minlength="6">
                </div>
                <div class="form-group">
                    <label>邮箱 (可选)</label>
                    <input type="email" id="regEmail" placeholder="your@email.com">
                </div>
                <button type="submit" class="btn-primary">注册</button>
            </form>
            
            <button class="btn-guest" id="guestBtn">游客进入 ></button>
        </div>
    </div>

    <div class="container" id="mainContainer" style="display:none;">
        <!-- 头部 -->
        <header class="header">
            <h1 class="logo" data-i18n="title">PolyChat 🌍</h1>
            <p class="tagline" data-i18n="tagline">跨越语言障碍，连接世界各地的朋友</p>
            <div class="header-actions">
                <button class="btn-logout" id="logoutBtn">退出</button>
            </div>
            <button class="mobile-sidebar-btn" id="mobileSidebarBtn">☰</button>
        </header>
        
        <!-- 主内容 -->
        <div class="main-grid">
            <!-- 聊天区域 -->
            <div class="chat-card">
                <div class="chat-header">
                    <div class="chat-title">
                        <span class="online-dot"></span>
                        <span id="currentRoomName">公共聊天室</span>
                    </div>
                    <div class="chat-stats">
                        <select id="roomSelect" class="room-select">
                            <option value="1">公共聊天室</option>
                        </select>
                    </div>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
                
                <!-- 任务面板 (任务模式房间显示) -->
                <div class="tasks-panel" id="tasksPanel" style="display:none;">
                    <div class="tasks-header">
                        <span>📋 待办事项</span>
                        <button class="btn-add-task" id="addTaskBtn">+ 添加</button>
                    </div>
                    <div class="tasks-list" id="tasksList"></div>
                </div>
                
                <div class="input-area" id="chatInputArea">
                    <div class="emoji-bar">
                        <button class="emoji-btn" data-emoji="👍">👍</button>
                        <button class="emoji-btn" data-emoji="❤️">❤️</button>
                        <button class="emoji-btn" data-emoji="😂">😂</button>
                        <button class="emoji-btn" data-emoji="😮">😮</button>
                        <button class="emoji-btn" data-emoji="😢">😢</button>
                        <button class="emoji-btn" data-emoji="😡">😡</button>
                        <button class="emoji-btn" data-emoji="🎉">🎉</button>
                        <button class="emoji-btn" data-emoji="🔥">🔥</button>
                        <button class="emoji-btn" data-emoji="👋">👋</button>
                        <button class="emoji-btn" data-emoji="✨">✨</button>
                        <button class="emoji-btn" data-emoji="💪">💪</button>
                        <button class="emoji-btn" data-emoji="🌟">🌟</button>
                    </div>
                    
                    <div class="input-wrapper">
                        <textarea 
                            id="messageInput" 
                            class="input-box" 
                            placeholder="输入消息... (Enter 发送)"
                            rows="1"
                        ></textarea>
                        <button id="sendBtn" class="send-btn">🚀 发送</button>
                    </div>
                </div>
            </div>
            
            <!-- 侧边栏 -->
            <aside class="sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-title">🌐 UI 语言</div>
                    <select id="uiLang" class="lang-select">
                        <option value="zh">🇨🇳 中文</option>
                        <option value="en">🇺🇸 English</option>
                        <option value="ja">🇯🇵 日本語</option>
                        <option value="ko">🇰🇷 한국어</option>
                        <option value="es">🇪🇸 Español</option>
                        <option value="fr">🇫🇷 Français</option>
                        <option value="de">🇩🇪 Deutsch</option>
                        <option value="ru">🇷🇺 Русский</option>
                        <option value="ar">🇸🇦 العربية</option>
                        <option value="hi">🇮🇳 हिन्दी</option>
                        <option value="pt">🇧🇷 Português</option>
                        <option value="it">🇮🇹 Italiano</option>
                    </select>
                </div>
                
                <div class="sidebar-card">
                    <div class="sidebar-title">⚙️ 个人设置</div>
                    <div class="setup-form">
                        <div class="form-group">
                            <label>头像</label>
                            <div class="avatar-upload">
                                <img id="avatarPreview" src="" alt="头像" style="width:60px;height:60px;border-radius:50%;display:none;">
                                <input type="file" id="avatarInput" accept="image/*" style="display:none;">
                                <button type="button" class="btn-avatar" onclick="document.getElementById('avatarInput').click()">上传头像</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" id="username" readonly>
                        </div>
                        <div class="form-group">
                            <label>你的颜色</label>
                            <div class="color-picker">
                                <div class="color-option" data-color="#6366f1" style="background: #6366f1"></div>
                                <div class="color-option" data-color="#ec4899" style="background: #ec4899"></div>
                                <div class="color-option" data-color="#8b5cf6" style="background: #8b5cf6"></div>
                                <div class="color-option" data-color="#06b6d4" style="background: #06b6d4"></div>
                                <div class="color-option" data-color="#10b981" style="background: #10b981"></div>
                                <div class="color-option" data-color="#f59e0b" style="background: #f59e0b"></div>
                                <div class="color-option" data-color="#ef4444" style="background: #ef4444"></div>
                                <div class="color-option" data-color="#64748b" style="background: #64748b"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>翻译目标语言</label>
                            <select id="targetLang" class="lang-select">
                                <option value="zh">🇨🇳 中文</option>
                                <option value="en">🇺🇸 English</option>
                                <option value="ja">🇯🇵 日本語</option>
                                <option value="ko">🇰🇷 한국어</option>
                                <option value="es">🇪🇸 Español</option>
                                <option value="fr">🇫🇷 Français</option>
                                <option value="de">🇩🇪 Deutsch</option>
                                <option value="ru">🇷🇺 Русский</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-card">
                    <div class="sidebar-title">💡 功能说明</div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
                        <p>• 输入消息自动翻译</p>
                        <p>• 支持 12+ 种语言</p>
                        <p>• 点赞表情功能</p>
                        <p>• 实时推送消息</p>
                    </div>
                </div>
                
                <!-- 管理员面板 -->
                <div class="sidebar-card" id="adminPanel" style="display:none;">
                    <div class="sidebar-title">🔧 管理员后台</div>
                    <div class="setup-form">
                        <div class="form-group">
                            <label>翻译服务</label>
                            <select id="translatorSelect" class="lang-select">
                                <option value="google">Google 翻译</option>
                                <option value="local">本地翻译服务</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>创建聊天室</label>
                            <input type="text" id="newRoomName" class="form-input" placeholder="房间名称">
                        </div>
                        <button class="btn-primary" id="createRoomBtn" style="margin-top:8px;">创建房间</button>
                        
                        <hr style="border-color:var(--border);margin:15px 0;">
                        
                        <div class="form-group">
                            <label>📊 统计数据</label>
                            <div id="adminStats" style="font-size:0.85rem;color:var(--text-secondary);">
                                加载中...
                            </div>
                        </div>
                        
                        <button class="btn-primary" id="refreshStatsBtn" style="margin-top:8px;background:var(--bg-input);">刷新统计</button>
                    </div>
                </div>
            </aside>
        </div>
        
        <footer style="text-align: center; padding: 30px; color: var(--text-secondary); font-size: 0.85rem;">
            <p>PolyChat v2.0 | 由 jieraltjp 开发维护</p>
        </footer>
    </div>
    
    <script src="i18n.js"></script>
    <script src="app.js"></script>
    <script>
        // Auth Modal
        const authModal = document.getElementById('authModal');
        const mainContainer = document.getElementById('mainContainer');
        
        // 检查登录状态
        function checkAuth() {
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            if (user && user.username) {
                showMainApp(user);
            } else {
                showAuthModal();
            }
        }
        
        function showAuthModal() {
            authModal.style.display = 'flex';
            mainContainer.style.display = 'none';
        }
        
        function showMainApp(user) {
            authModal.style.display = 'none';
            mainContainer.style.display = 'block';
            
            document.getElementById('username').value = user.username;
            window.currentUser = user;
            
            // 加载配置（检查管理员权限）
            loadConfig();
        }
        
        // Tab 切换
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const isLogin = tab.dataset.tab === 'login';
                document.getElementById('loginForm').style.display = isLogin ? 'block' : 'none';
                document.getElementById('registerForm').style.display = isLogin ? 'none' : 'block';
            });
        });
        
        // 登录
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('loginUsername').value.trim();
            const password = document.getElementById('loginPassword').value;
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            
            try {
                const response = await fetch('api.php?action=login', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    localStorage.setItem('polychat_user', JSON.stringify(result.user));
                    showMainApp(result.user);
                } else {
                    alert(result.error || '登录失败');
                }
            } catch (err) {
                alert('网络错误');
            }
        });
        
        // 注册
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('regUsername').value.trim();
            const password = document.getElementById('regPassword').value;
            const email = document.getElementById('regEmail').value.trim();
            const color = '#' + Math.floor(Math.random()*16777215).toString(16);
            
            console.log('Registering:', username);
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('password', password);
            if (email) formData.append('email', email);
            formData.append('color', color);
            
            try {
                const response = await fetch('api.php?action=register', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                console.log('Register result:', result);
                
                if (result.success) {
                    localStorage.setItem('polychat_user', JSON.stringify(result.user));
                    showMainApp(result.user);
                } else {
                    alert(result.error || '注册失败');
                }
            } catch (err) {
                console.error('Register error:', err);
                alert('网络错误: ' + err.message);
            }
        });
        
        // 游客进入
        document.getElementById('guestBtn').addEventListener('click', () => {
            const username = '游客' + Math.floor(Math.random() * 10000);
            const user = { username, color: '#6366f1', role: 'guest' };
            localStorage.setItem('polychat_user', JSON.stringify(user));
            showMainApp(user);
        });
        
        // 退出
        document.getElementById('logoutBtn').addEventListener('click', () => {
            localStorage.removeItem('polychat_user');
            showAuthModal();
        });
        
        // 加载配置
        loadConfig();
        
        // 加载配置
        async function loadConfig() {
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            if (user.role === 'admin') {
                document.getElementById('adminPanel').style.display = 'block';
                loadAdminStats();
                
                // 加载翻译服务配置
                try {
                    const res = await fetch('api.php?action=config');
                    const data = await res.json();
                    if (data.success && data.config) {
                        if (data.config.translator) {
                            document.getElementById('translatorSelect').value = data.config.translator;
                        }
                    }
                } catch (e) {}
            }
            
            // 加载头像
            if (user.avatar) {
                const img = document.getElementById('avatarPreview');
                img.src = user.avatar;
                img.style.display = 'block';
            }
        }
        
        // 头像上传
        document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = async () => {
                const base64 = reader.result;
                
                const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
                const formData = new FormData();
                formData.append('user_id', user.id);
                formData.append('username', user.username);
                formData.append('avatar', base64);
                
                try {
                    const res = await fetch('api.php?action=update_profile', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        const img = document.getElementById('avatarPreview');
                        img.src = base64;
                        img.style.display = 'block';
                        
                        // 更新本地存储
                        user.avatar = base64;
                        localStorage.setItem('polychat_user', JSON.stringify(user));
                        alert('头像上传成功!');
                    }
                } catch (err) {
                    alert('上传失败');
                }
            };
            reader.readAsDataURL(file);
        });
        
        // 创建房间
        document.getElementById('createRoomBtn')?.addEventListener('click', async () => {
            const name = document.getElementById('newRoomName').value.trim();
            if (!name) {
                alert('请输入房间名称');
                return;
            }
            
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            
            const formData = new FormData();
            formData.append('name', name);
            formData.append('user_id', user.id || 0);
            
            try {
                const res = await fetch('api.php?action=create_room', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    alert('房间创建成功！');
                    document.getElementById('newRoomName').value = '';
                    window.chat.loadRooms();
                } else {
                    alert(data.error || '创建失败');
                }
            } catch (e) {
                alert('网络错误');
            }
        });
        
        // 保存翻译服务配置
        document.getElementById('translatorSelect')?.addEventListener('change', async (e) => {
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            if (user.role !== 'admin') return;
            
            const formData = new FormData();
            formData.append('key', 'translator');
            formData.append('value', e.target.value);
            formData.append('user_id', user.id);
            
            try {
                await fetch('api.php?action=config', { method: 'POST', body: formData });
            } catch (e) {}
        });
        
        // 加载统计数据
        async function loadAdminStats() {
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            if (user.role !== 'admin') return;
            
            try {
                const res = await fetch('api.php?action=admin_stats&admin_id=' + user.id);
                const data = await res.json();
                
                if (data.success && data.stats) {
                    const s = data.stats;
                    document.getElementById('adminStats').innerHTML = 
                        '👥 用户: ' + s.total_users + '<br>' +
                        '💬 消息: ' + s.total_messages + '<br>' +
                        '🏠 房间: ' + s.total_rooms + '<br>' +
                        '📅 今日: ' + s.today_messages + '<br>' +
                        '🟢 在线: ' + s.online_users;
                }
            } catch (e) {}
        }
        
        // 刷新统计
        document.getElementById('refreshStatsBtn')?.addEventListener('click', loadAdminStats);
        
        // UI 语言
        document.getElementById('uiLang').addEventListener('change', (e) => {
            i18n.setLang(e.target.value);
        });
        
        // 页面加载
        document.addEventListener('DOMContentLoaded', () => {
            // 先清除旧状态，强制显示登录
            // localStorage.removeItem('polychat_user'); // 注释掉这行，允许记住登录状态
            checkAuth();
            i18n.init();
            
            // 确保登录弹窗可见
            const user = JSON.parse(localStorage.getItem('polychat_user') || '{}');
            if (!user || !user.username) {
                showAuthModal();
            }
        });
    </script>
</body>
</html>
