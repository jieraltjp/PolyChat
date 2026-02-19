// PolyChat i18n - Multi-language support
const i18n = {
    currentLang: 'zh',
    
    translations: {
        zh: {
            title: 'PolyChat',
            tagline: '跨越语言障碍，连接世界各地的朋友',
            chatTitle: '公共聊天室',
            online: '在线',
            placeholder: '输入消息... (Enter 发送, Shift+Enter 换行)',
            send: '发送',
            settings: '个人设置',
            username: '用户名',
            usernamePlaceholder: '给自己起个名字',
            yourColor: '你的颜色',
            translateTo: '翻译目标语言',
            features: '功能说明',
            featureHighlight: '无论说什么语言，我们都能懂你！',
            recentActive: '最近活跃',
            emptyState: '还没有消息',
            emptyStateSub: '成为第一个说话的人吧！',
            loading: '加载中...',
            footer: 'PolyChat v1.0 | 由 jieraltjp 开发维护',
            footer2: '让语言不再是障碍，让世界更加紧密'
        },
        en: {
            title: 'PolyChat',
            tagline: 'Break language barriers, connect friends worldwide',
            chatTitle: 'Public Chat',
            online: 'Online',
            placeholder: 'Type a message... (Enter to send)',
            send: 'Send',
            settings: 'Settings',
            username: 'Username',
            usernamePlaceholder: 'Give yourself a name',
            yourColor: 'Your Color',
            translateTo: 'Translate To',
            features: 'Features',
            featureHighlight: 'No matter what language, we understand you!',
            recentActive: 'Recently Active',
            emptyState: 'No messages yet',
            emptyStateSub: 'Be the first to say something!',
            loading: 'Loading...',
            footer: 'PolyChat v1.0 | Developed by jieraltjp',
            footer2: 'Making language no barrier, connecting the world'
        },
        ja: {
            title: 'PolyChat',
            tagline: '言語の壁を超え、世界中の友達とつながる',
            chatTitle: 'パブリックチャット',
            online: 'オンライン',
            placeholder: 'メッセージを入力...',
            send: '送信',
            settings: '設定',
            username: 'ユーザー名',
            usernamePlaceholder: '名前をつけて',
            yourColor: 'あなたの色',
            translateTo: '翻訳先言語',
            features: '機能',
            featureHighlight: 'どんな言語でも理解します！',
            recentActive: '最近アクティブ',
            emptyState: 'メッセージはまだありません',
            emptyStateSub: '最初のメッセージを送ろう！',
            loading: '読み込み中...',
            footer: 'PolyChat v1.0 | jieraltjpで開発',
            footer2: '言語不再是障壁、世界中のつながりを密に'
        },
        ko: {
            title: 'PolyChat',
            tagline: '언어 장벽을 넘어 전 세계 친구와 연결',
            chatTitle: '공개 채팅',
            online: '온라인',
            placeholder: '메시지 입력...',
            send: '전송',
            settings: '설정',
            username: '사용자 이름',
            usernamePlaceholder: '이름을 정하세요',
            yourColor: '당신의 색상',
            translateTo: '번역 대상 언어',
            features: '기능',
            featureHighlight: '어떤 언어든 이해합니다!',
            recentActive: '최근 활동',
            emptyState: '메시지가 없습니다',
            emptyStateSub: '첫 번째 메시지를 보내보세요!',
            loading: '로딩 중...',
            footer: 'PolyChat v1.0 | jieraltjp가 개발',
            footer2: '언어가 장벽이 되지 않게, 세계를 더 가까이'
        }
    },
    
    setLang(lang) {
        if (this.translations[lang]) {
            this.currentLang = lang;
            localStorage.setItem('polychat_ui_lang', lang);
            this.updateUI();
        }
    },
    
    t(key) {
        const trans = this.translations[this.currentLang];
        return trans ? trans[key] : this.translations['zh'][key] || key;
    },
    
    updateUI() {
        const lang = this.currentLang;
        
        // Update all elements with data-i18n attribute
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            el.textContent = this.t(key);
        });
        
        // Update placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.placeholder = this.t(key);
        });
        
        // Update language selector
        const langSelect = document.getElementById('uiLang');
        if (langSelect) {
            langSelect.value = lang;
        }
        
        // Update document lang
        document.documentElement.lang = lang;
    },
    
    init() {
        const savedLang = localStorage.getItem('polychat_ui_lang');
        if (savedLang && this.translations[savedLang]) {
            this.currentLang = savedLang;
        }
        this.updateUI();
    }
};

window.i18n = i18n;
