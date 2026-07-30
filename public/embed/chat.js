(function () {
    // Safely detect script element across all async/defer loaders
    var scriptTag = document.currentScript || (function() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].getAttribute('data-widget-key') || (scripts[i].src && scripts[i].src.indexOf('/embed/chat.js') !== -1)) {
                return scripts[i];
            }
        }
        return scripts[scripts.length - 1];
    })();
    var currentScript = scriptTag;

    var widgetKey = (scriptTag && scriptTag.getAttribute) ? (scriptTag.getAttribute('data-widget-key') || '') : '';
    var serverUrl = (scriptTag && scriptTag.src) ? scriptTag.src.replace(/\/embed\/chat\.js.*$/, '') : 'https://gotiau.com';

    if (!widgetKey) {
        console.warn('[AI CS Widget] Missing data-widget-key attribute.');
        return;
    }

    var sessionToken = localStorage.getItem('ai_cs_session_token_' + widgetKey) || '';

    // Create Chat Widget CSS
    var style = document.createElement('style');
    style.innerHTML = `
        .aics-launcher {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: #fff;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999999;
            transition: all 0.3s ease;
            font-size: 26px;
        }
        .aics-launcher:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.6);
        }
        .aics-window {
            position: fixed;
            bottom: 96px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 48px);
            height: 520px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            z-index: 999999;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            border: 1px border-gray-100;
        }
        .aics-header {
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: #fff;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .aics-header-title {
            font-weight: 700;
            font-size: 15px;
        }
        .aics-header-subtitle {
            font-size: 11px;
            opacity: 0.85;
        }
        .aics-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
        }
        .aics-close:hover { opacity: 1; }
        .aics-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #F9FAFB;
        }
        .aics-msg {
            max-width: 82%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .aics-msg a {
            color: #2563EB;
            font-weight: 700;
            text-decoration: underline;
            word-break: break-all;
        }
        .aics-msg a:hover {
            color: #1D4ED8;
        }
        .aics-msg strong {
            font-weight: 700;
            color: #111827;
        }
        .aics-msg-ai {
            align-self: flex-start;
            background: #ffffff;
            color: #1F2937;
            border: 1px solid #E5E7EB;
            border-bottom-left-radius: 2px;
        }
        .aics-msg-visitor {
            align-self: flex-end;
            background: #4F46E5;
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }
        .aics-footer {
            padding: 12px;
            background: #fff;
            border-top: 1px solid #E5E7EB;
            display: flex;
            gap: 8px;
        }
        .aics-input {
            flex: 1;
            border: 1px solid #D1D5DB;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 13px;
            outline: none;
        }
        .aics-input:focus { border-color: #4F46E5; }
        .aics-send {
            background: #4F46E5;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
    `;
    document.head.appendChild(style);

    // Create Launcher Button
    var launcher = document.createElement('div');
    launcher.className = 'aics-launcher';
    launcher.innerHTML = '💬';
    document.body.appendChild(launcher);

    var customTitle = currentScript ? currentScript.getAttribute('data-title') : null;
    var headerTitle = customTitle || 'Waperu Support';

    // Create Chat Window
    var windowBox = document.createElement('div');
    windowBox.className = 'aics-window';
    windowBox.innerHTML = `
        <div class="aics-header">
            <div>
                <div class="aics-header-title" id="aics-title">${headerTitle}</div>
                <div class="aics-header-subtitle">Online • Siap Menjawab Pertanyaan Anda</div>
            </div>
            <button class="aics-close" id="aics-close">✕</button>
        </div>
        <div class="aics-body" id="aics-body">
            <div class="aics-msg aics-msg-ai">Halo! 👋 Ada yang bisa saya bantu terkait **toko online mandiri**, **fitur QRIS & COD 0% komisi**, atau solusi jualan tanpa potongan admin? Silakan tanyakan di sini! 😊</div>
        </div>
        <div class="aics-footer">
            <input type="text" class="aics-input" id="aics-input" placeholder="Tulis pertanyaan Anda..." />
            <button class="aics-send" id="aics-send">➔</button>
        </div>
    `;
    document.body.appendChild(windowBox);

    var bodyBox = document.getElementById('aics-body');
    var inputField = document.getElementById('aics-input');
    var sendBtn = document.getElementById('aics-send');
    var closeBtn = document.getElementById('aics-close');

    // Toggle Chat Window
    launcher.onclick = function () {
        var isVisible = windowBox.style.display === 'flex';
        windowBox.style.display = isVisible ? 'none' : 'flex';
        if (!isVisible) inputField.focus();
    };

    closeBtn.onclick = function () {
        windowBox.style.display = 'none';
    };

    // Format Markdown links, raw URLs, bold text, and linebreaks for AI responses
    function formatChatMessage(text) {
        if (!text) return '';

        // 1. Escape HTML special characters to prevent XSS
        var div = document.createElement('div');
        div.innerText = text;
        var escaped = div.innerHTML;

        var links = [];

        // 2. Extract & Tokenize Markdown links: [label](url)
        escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, function(match, label, url) {
            var index = links.length;
            var cleanLabel = label.replace(/^https?:\/\//, '');
            links.push('<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="aics-link">' + cleanLabel + ' ↗</a>');
            return '___AICS_LINK_' + index + '___';
        });

        // 3. Extract & Tokenize Raw URLs: https://... or http://... (exclude markdown formatting symbols like *, _, ~)
        escaped = escaped.replace(/(https?:\/\/[^\s<>"*\_]+)/g, function(match, url) {
            var cleanUrl = url.replace(/[*_.,!?:;)\-]+$/, '');
            var trailingPunct = url.substring(cleanUrl.length);
            if (!cleanUrl) return match;
            var index = links.length;
            var displayUrl = cleanUrl.replace(/^https?:\/\//, '');
            links.push('<a href="' + cleanUrl + '" target="_blank" rel="noopener noreferrer" class="aics-link">' + displayUrl + ' ↗</a>');
            return '___AICS_LINK_' + index + '___' + trailingPunct;
        });

        // 4. Format Markdown bold: **text** or __text__
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/__(.*?)__/g, '<strong>$1</strong>');

        // 5. Restore Tokenized Links
        escaped = escaped.replace(/___AICS_LINK_(\d+)___/g, function(match, id) {
            return links[parseInt(id, 10)] || '';
        });

        // 6. Format Newlines -> <br>
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    // Send Message Logic
    function sendMessage() {
        var text = inputField.value.trim();
        if (!text) return;

        // Append Visitor Message
        var vMsg = document.createElement('div');
        vMsg.className = 'aics-msg aics-msg-visitor';
        vMsg.innerText = text;
        bodyBox.appendChild(vMsg);
        inputField.value = '';
        bodyBox.scrollTop = bodyBox.scrollHeight;

        // Show Typing Indicator
        var typing = document.createElement('div');
        typing.className = 'aics-msg aics-msg-ai';
        typing.innerText = 'AI sedang mengetik...';
        bodyBox.appendChild(typing);
        bodyBox.scrollTop = bodyBox.scrollHeight;

        // Call Backend API
        fetch(serverUrl + '/api/widget/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                widget_key: widgetKey,
                message: text,
                session_token: sessionToken
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            bodyBox.removeChild(typing);
            if (data.session_token) {
                sessionToken = data.session_token;
                localStorage.setItem('ai_cs_session_token_' + widgetKey, sessionToken);
            }
            if (data.brand_name) {
                document.getElementById('aics-title').innerText = 'CS AI ' + data.brand_name;
            }

            var aiMsg = document.createElement('div');
            aiMsg.className = 'aics-msg aics-msg-ai';
            aiMsg.innerHTML = formatChatMessage(data.reply || data.error || 'Mohon maaf, terjadi kendala saat merespon.');
            bodyBox.appendChild(aiMsg);
            bodyBox.scrollTop = bodyBox.scrollHeight;
        })
        .catch(function(err) {
            console.warn('[AI CS Widget Connection Notice]', err);
            bodyBox.removeChild(typing);
            var errMsg = document.createElement('div');
            errMsg.className = 'aics-msg aics-msg-ai';
            errMsg.innerHTML = formatChatMessage('Halo! 👋 Terima kasih telah menghubungi Customer Support **Waperu**.\n\nSilakan kunjungi website resmi kami di https://waperu.com atau tanyakan seputar pembuatan **toko online 0% komisi** & **QRIS/COD**! 😊');
            bodyBox.appendChild(errMsg);
            bodyBox.scrollTop = bodyBox.scrollHeight;
        });
    }

    sendBtn.onclick = sendMessage;
    inputField.onkeypress = function (e) {
        if (e.key === 'Enter') sendMessage();
    };
})();
