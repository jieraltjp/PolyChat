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
    <div class="container">
        <!-- 头部 -->
        <header class="header">
            <h1 class="logo" data-i18n="title">PolyChat 🌍</h1>
            <p class="tagline" data-i18n="tagline">跨越语言障碍，连接世界各地的朋友</p>
        </header>
        
        <!-- 主内容 -->
        <div class="main-grid">
            <!-- 聊天区域 -->
            <div class="chat-card">
                <div class="chat-header">
                    <div class="chat-title">
                        <span class="online-dot"></span>
                        <span data-i18n="chatTitle">公共聊天室</span>
                    </div>
                    <div class="chat-stats">
                        <span id="onlineCount" data-i18n="online">🌐 在线</span>
                    </div>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
                
                <div class="input-area">
                    <!-- 表情选择器 -->
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
                            data-i18n-placeholder="placeholder"
                            placeholder="输入消息... (Enter 发送, Shift+Enter 换行)"
                            rows="1"
                        ></textarea>
                        <button id="sendBtn" class="send-btn" data-i18n="send">
                            🚀 发送
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 侧边栏 -->
            <aside class="sidebar">
                <!-- UI语言设置 -->
                <div class="sidebar-card">
                    <div class="sidebar-title">🌐 UI 语言</div>
                    <select id="uiLang" class="lang-select" onchange="changeUILang(this.value)">
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
                
                <!-- 用户设置 -->
                <div class="sidebar-card">
                    <div class="sidebar-title" data-i18n="settings">⚙️ 个人设置</div>
                    <div class="setup-form">
                        <div class="form-group">
                            <label class="form-label" data-i18n="username">用户名</label>
                            <input type="text" id="username" class="form-input" data-i18n-placeholder="usernamePlaceholder" placeholder="给自己起个名字" maxlength="20">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" data-i18n="yourColor">你的颜色</label>
                            <div class="color-picker">
                                <div class="color-option selected" data-color="#6366f1" style="background: #6366f1"></div>
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
                            <label class="form-label" data-i18n="translateTo">翻译目标语言</label>
                            <select id="targetLang" class="lang-select">
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
                    </div>
                </div>
                
                <!-- 功能说明 -->
                <div class="sidebar-card">
                    <div class="sidebar-title" data-i18n="features">💡 功能说明</div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;" id="featureList">
                        <p>• 输入消息自动翻译成您选择的语言</p>
                        <p>• 支持 12+ 种语言实时翻译</p>
                        <p>• 消息将显示原文和翻译</p>
                        <p>• 选择喜欢的颜色代表自己</p>
                        <p style="margin-top: 12px; padding: 10px; background: var(--glass); border-radius: 8px;" data-i18n="featureHighlight">
                            🌐 无论说什么语言，我们都能懂你！
                        </p>
                    </div>
                </div>
                
                <!-- 在线用户 -->
                <div class="sidebar-card">
                    <div class="sidebar-title" data-i18n="recentActive">👥 最近活跃</div>
                    <div class="online-list" id="onlineList">
                        <span style="color: var(--text-secondary); font-size: 0.85rem;">加载中...</span>
                    </div>
                </div>
            </aside>
        </div>
        
        <!-- 底部 -->
        <footer style="text-align: center; padding: 30px; color: var(--text-secondary); font-size: 0.85rem;">
            <p data-i18n="footer">PolyChat v1.0 | 由 jieraltjp 开发维护 🤖</p>
            <p style="margin-top: 8px;" data-i18n="footer2">让语言不再是障碍，让世界更加紧密</p>
        </footer>
    </div>
    
    <script src="i18n.js"></script>
    <script src="app.js"></script>
    <script>
        function changeUILang(lang) {
            i18n.setLang(lang);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            i18n.init();
        });
    </script>
</body>
</html>
