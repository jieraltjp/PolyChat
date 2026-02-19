const http = require('http');
const https = require('https');

const translate = (text, from = 'auto', to = 'zh') => {
    return new Promise((resolve, reject) => {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=${from}&tl=${to}&dt=t&q=${encodeURIComponent(text)}`;
        
        https.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    const result = JSON.parse(data);
                    if (result && result[0]) {
                        let translated = '';
                        result[0].forEach(item => {
                            if (item[0]) translated += item[0];
                        });
                        resolve(translated || text);
                    } else {
                        resolve(text);
                    }
                } catch (e) {
                    reject(e);
                }
            });
        }).on('error', reject);
    });
};

const server = http.createServer(async (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }
    
    const urlObj = new URL(req.url, `http://localhost:${PORT}`);
    const action = urlObj.searchParams.get('action');
    
    if (action === 'translate') {
        const text = urlObj.searchParams.get('text') || '';
        const from = urlObj.searchParams.get('from') || 'auto';
        const to = urlObj.searchParams.get('to') || 'zh';
        
        try {
            const translated = await translate(text, from, to);
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: true, translated }));
        } catch (e) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: e.message }));
        }
    } else {
        res.writeHead(404);
        res.end('Not Found');
    }
});

const PORT = 16689;
server.listen(PORT, () => {
    console.log(`Translation service running on port ${PORT}`);
});
