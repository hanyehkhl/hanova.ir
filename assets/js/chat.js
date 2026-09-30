(function () {

    let currentUser = null;
    let selectedUserId = null;
    let lastMessageId = 0;
    let fetchInterval = null;
    let usersInterval = null;
    let selectedFile = null;
    let isLoadingOlder = false;
    let oldestMessageId = Infinity;

    const sidebar = document.getElementById('user-list');
    const currentUserName = document.getElementById('current-user-name');
    const logoutBtn = document.getElementById('logout-btn');
    const chatHeader = document.getElementById('chat-header');
    const messagesContainer = document.getElementById('messages-container');
    const chatInputArea = document.getElementById('chat-input-area');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const fileNameSpan = document.getElementById('file-name');
    const removeFileBtn = document.getElementById('remove-file');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    let searchTimeout = null;

    async function checkAuth() {
        try {
            const res = await fetch('/api/auth/check.php', { credentials: 'include' });
            const data = await res.json();
            if (!data.success || !data.logged_in) {
                window.location.href = '/chat';
                return false;
            }
            currentUser = data.user;
            currentUserName.textContent = data.user.display_name;
            return true;
        } catch (e) {
            window.location.href = '/chat';
            return false;
        }
    }

    async function loadUsers() {
        try {
            const res = await fetch('/api/chat/users.php', { credentials: 'include' });
            const data = await res.json();
            if (!data.success) return;

            const users = data.users;
            sidebar.innerHTML = '';

            if (users.length === 0) {
                sidebar.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:0.9rem;">ع©ط§ط±ط¨ط± ط¯غŒع¯ط±غŒ ظˆط¬ظˆط¯ ظ†ط¯ط§ط±ط¯</div>';
                return;
            }

            users.forEach(function (user) {
                const item = document.createElement('div');
                item.className = 'user-item' + (selectedUserId === user.id ? ' active' : '');
                item.dataset.userId = user.id;
                item.dataset.displayName = user.display_name;

                const firstChar = user.display_name.charAt(0);
                let badgeHTML = '';
                if (user.unread_count > 0) {
                    badgeHTML = '<div class="unread-badge">' + user.unread_count + '</div>';
                }

                item.innerHTML =
                    '<div class="user-item-info">' +
                    '<div class="user-avatar">' + firstChar + '</div>' +
                    '<div class="user-item-name">' + escapeHtml(user.display_name) + '</div>' +
                    '</div>' + badgeHTML;

                item.addEventListener('click', function () {
                    selectUser(user.id, user.display_name);
                });
                sidebar.appendChild(item);
            });
        } catch (e) {}
    }

    function selectUser(userId, displayName) {
        if (selectedUserId === userId) return;
        selectedUserId = userId;
        // ط¨ط¹ط¯ ط§ط² طھظ†ط¸غŒظ… selectedUserId ظˆ ظ†ظ…ط§غŒط´ ع†طھ
        if(window.innerWidth <= 600){
            document.getElementById('sidebar').classList.remove('mobile-show');
             // ط§ط¶ط§ظپظ‡ ع©ظ†:
             document.querySelector('.chat-panel').style.display = 'flex';
        }

        lastMessageId = 0;
        oldestMessageId = Infinity;
        messagesContainer.innerHTML = '';
        
        document.querySelectorAll('.user-item').forEach(function (el) {
            el.classList.remove('active');
            if (parseInt(el.dataset.userId) === userId) {
                el.classList.add('active');
            }
        });

        const firstChar = displayName.charAt(0);
        chatHeader.innerHTML =
            '<div class="chat-header-active">' +
            '<div class="user-avatar">' + firstChar + '</div>' +
            '<span class="header-name">' + escapeHtml(displayName) + '</span>' +
            '</div>';

        chatInputArea.style.display = 'block';
        messageInput.focus();

        if (fetchInterval) clearInterval(fetchInterval);
        fetchMessages(true);
        fetchInterval = setInterval(fetchMessages, 2000);
    }

    async function fetchMessages(forceScrollToBottom) {
        try {
            const res = await fetch('/api/chat/fetch.php?partner_id=' + selectedUserId + '&last_id=' + lastMessageId, { credentials: 'include' });
            const data = await res.json();

            if (data.success && data.messages.length > 0) {
                const wasAtBottom = isScrolledToBottom();
                data.messages.forEach(function (msg) {
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                    oldestMessageId = Math.min(oldestMessageId, msg.id);
                });
                if (wasAtBottom || forceScrollToBottom) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        } catch (e) {}
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       appendMessage  â€“  ط³ط§ط®طھ ط­ط¨ط§ط¨ ظ¾غŒط§ظ… ط¨ط§ DOM API
       ط¨ط±ط§غŒ ظپط§غŒظ„â€Œظ‡ط§: ط¯ع©ظ…ظ‡ ط¯ط§ظ†ظ„ظˆط¯ + ظ†ظˆط§ط± ظ¾غŒط´ط±ظپطھ
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function appendMessage(msg) {
        var isSent = (msg.sender_id === currentUser.id);

        var row = document.createElement('div');
        row.className = 'message-row ' + (isSent ? 'sent' : 'received');
        row.dataset.messageId = msg.id;

        var bubble = document.createElement('div');
        bubble.className = 'message-bubble';

        /* ---------- ط§ع¯ط± ظ¾غŒط§ظ… ط­ط§ظˆغŒ ظپط§غŒظ„ ط¨ط§ط´ط¯ ---------- */
        if (msg.file_path) {
            var ext = msg.file_path.split('.').pop().toLowerCase();
            var fileUrl = '/api/chat/file.php?file=' + encodeURIComponent(msg.file_path);

            var fileName = msg.file_path.split('/').pop();
            var uid = 'dl-' + msg.id;

            var fileDiv = document.createElement('div');
            fileDiv.className = 'message-file';

            /* --- ط³ط§ط®طھ ظ†ظˆط§ط± ظ¾غŒط´ط±ظپطھ (ظ…ط´طھط±ع© ط¨غŒظ† طھطµظˆغŒط± ظˆ ط³ط§غŒط± ظپط§غŒظ„â€Œظ‡ط§) --- */
            function buildProgressBlock(id) {
                var wrap = document.createElement('div');
                wrap.id = id + '-container';
                wrap.style.cssText = 'display:none;margin-top:6px;width:100%;';

                var track = document.createElement('div');
                track.style.cssText = 'background:#e0e0e0;border-radius:4px;overflow:hidden;height:6px;width:100%;';

                var bar = document.createElement('div');
                bar.id = id + '-bar';
                bar.style.cssText = 'height:100%;width:0%;background:#4CAF50;border-radius:4px;transition:width 0.2s;';

                track.appendChild(bar);
                wrap.appendChild(track);

                var pct = document.createElement('span');
                pct.id = id + '-percent';
                pct.style.cssText = 'font-size:11px;color:#555;display:inline-block;margin-top:2px;';
                pct.textContent = '0%';
                wrap.appendChild(pct);

                return wrap;
            }

            if (['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1) {
                /* ---- طھطµظˆغŒط± ---- */
                var img = document.createElement('img');
                img.src = fileUrl;
                img.alt = 'طھطµظˆغŒط±';
                img.style.cssText = 'max-width:250px;border-radius:8px;cursor:pointer;display:block;';
                img.addEventListener('click', function () { window.open(fileUrl); });
                fileDiv.appendChild(img);

                var btnWrap = document.createElement('div');
                btnWrap.style.marginTop = '6px';

                var dlBtn = document.createElement('button');
                dlBtn.textContent = 'ط°ط®غŒط±ظ‡ طھطµظˆغŒط±';
                dlBtn.style.cssText = 'background:#2196F3;color:#fff;border:none;padding:5px 14px;border-radius:6px;cursor:pointer;font-size:13px;';
                dlBtn.addEventListener('click', function () {
                    downloadWithProgress(fileUrl, uid, fileName);
                });
                btnWrap.appendChild(dlBtn);
                btnWrap.appendChild(buildProgressBlock(uid));
                fileDiv.appendChild(btnWrap);

            } else {
                /* ---- ط³ط§غŒط± ظپط§غŒظ„â€Œظ‡ط§ (PDF ظˆ ...) ---- */
                var dlBtn2 = document.createElement('button');
                dlBtn2.textContent = 'ط¯ط§ظ†ظ„ظˆط¯ ظپط§غŒظ„';
                dlBtn2.style.cssText = 'background:#2196F3;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:13px;';
                dlBtn2.addEventListener('click', function () {
                    downloadWithProgress(fileUrl, uid, fileName);
                });
                fileDiv.appendChild(dlBtn2);
                fileDiv.appendChild(buildProgressBlock(uid));
            }

            bubble.appendChild(fileDiv);
        }

        /* ---------- ظ…طھظ† ظ¾غŒط§ظ… ---------- */
        if (msg.message && msg.message.trim() !== '') {
            var textDiv = document.createElement('div');
            textDiv.textContent = msg.message;
            bubble.appendChild(textDiv);
        }

        /* ---------- ط³ط§ط¹طھ ---------- */
        var timeSpan = document.createElement('span');
        timeSpan.className = 'message-time';
        timeSpan.textContent = formatTime(msg.created_at);
        bubble.appendChild(timeSpan);

        row.appendChild(bubble);
        messagesContainer.appendChild(row);
    }
        
        /* ---------- ط¯ع©ظ…ظ‡ ط­ط°ظپ ---------- */
        if (isSent) {
            var deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'ًں—‘ï¸ڈ';
            deleteBtn.style.cssText = 'background:none;border:none;cursor:pointer;font-size:14px;margin-right:8px;opacity:0.6;';
            deleteBtn.title = 'ط­ط°ظپ ظ¾غŒط§ظ…';
            deleteBtn.addEventListener('click', function() {
                deleteMessage(msg.id);
            });
            bubble.appendChild(deleteBtn);
        }
        
        row.appendChild(bubble);
        messagesContainer.appendChild(row);


    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       downloadWithProgress  â€“  ط¯ط§ظ†ظ„ظˆط¯ ط¨ط§ ظ†ظˆط§ط± ظ¾غŒط´ط±ظپطھ
       ط§ط² XHR ط§ط³طھظپط§ط¯ظ‡ ظ…غŒع©ظ†ط¯ طھط§ ط±ظˆغŒط¯ط§ط¯ progress ط®ظˆط§ظ†ط¯ظ‡ ط´ظˆط¯
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function downloadWithProgress(url, uid, fileName) {
        var container = document.getElementById(uid + '-container');
        var bar       = document.getElementById(uid + '-bar');
        var percent   = document.getElementById(uid + '-percent');
        if (!container || !bar || !percent) return;

        container.style.display = 'block';
        bar.style.width = '0%';
        bar.style.background = '#4CAF50';
        percent.textContent = '0%';

        var xhr = new XMLHttpRequest();
        xhr.responseType = 'blob';

        xhr.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                var p = Math.round((e.loaded / e.total) * 100);
                bar.style.width = p + '%';
                percent.textContent = p + '%';
            } else {
                percent.textContent = 'ط¯ط± ط­ط§ظ„ ط¯ط§ظ†ظ„ظˆط¯...';
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                console.log('Upload Response:', xhr.status, xhr.responseText);

                if (xhr.status >= 200 && xhr.status < 300) {
                    bar.style.width = '100%';
                    percent.textContent = '100% - ع©ط§ظ…ظ„ ط´ط¯';

                    var blob = xhr.response;
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);

                    setTimeout(function () {
                        container.style.display = 'none';
                    }, 2500);
                } else {
                    percent.textContent = 'ط®ط·ط§ ط¯ط± ط¯ط§ظ†ظ„ظˆط¯';
                    bar.style.background = '#f44336';
                }
            }
        };

        xhr.open('GET', url);
        xhr.withCredentials = true;
        xhr.send();
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ط§ط±ط³ط§ظ„ ظ¾غŒط§ظ… ظ…طھظ†غŒ
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    messageForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!selectedUserId) return;
        var text = messageInput.value.trim();
        if (!text && !selectedFile) return;
        if (selectedFile) { await sendFile(text); }
        else { await sendText(text); }
    });

    async function sendText(text) {
        var btn = messageForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            var res = await fetch('/api/chat/send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ receiver_id: selectedUserId, message: text })
            });
            var data = await res.json();
            if (data.success) { messageInput.value = ''; fetchMessages(true); }
        } catch (e) {}
        btn.disabled = false;
        messageInput.focus();
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       sendFile  â€“  ط¢ظ¾ظ„ظˆط¯ ظپط§غŒظ„ ط¨ط§ ظ†ظˆط§ط± ظ¾غŒط´ط±ظپطھ (ط¨ط¯ظˆظ† طھط؛غŒغŒط±)
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    async function sendFile(text) {
        var btn = messageForm.querySelector('button[type="submit"]');
        btn.disabled = true;

        try {
            var formData = new FormData();
            formData.append('receiver_id', selectedUserId);
            formData.append('file', selectedFile);
            if (text) formData.append('message', text);

            var progressContainer = document.getElementById('upload-progress-container');
            var progressBar       = document.getElementById('upload-progress-bar');
            var progressPercent   = document.getElementById('upload-progress-percent');
            var progressText      = document.getElementById('upload-progress-text');

            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressPercent.textContent = '0%';
            progressText.textContent = 'ط¯ط± ط­ط§ظ„ ط¢ظ¾ظ„ظˆط¯ ظپط§غŒظ„...';

            var uploadResult = await new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percent = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percent + '%';
                        progressPercent.textContent = percent + '%';
                    }
                });
            ;
                
                xhr.timeout = 300000; // 5 ط¯ظ‚غŒظ‚ظ‡
                

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        console.log('Upload Response:', xhr.status, xhr.responseText);
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try { resolve(JSON.parse(xhr.responseText)); }
                            catch (err) { reject(err); }
                        } else {
                            reject(new Error('Upload failed with status ' + xhr.status));
                        }
                    }
                };

                xhr.open('POST', '/api/chat/upload.php');
                xhr.withCredentials = true;
                xhr.onerror = function () {
                    btn.disabled = false;
                    console.error('XHR Error:', xhr.status, xhr.statusText);
                    progressText.textContent = 'ط®ط·ط§ ط¯ط± ط§طھطµط§ظ„ ط¨ظ‡ ط³ط±ظˆط±';
                    progressBar.style.background = '#f44336';
            };
            
                xhr.ontimeout = function () {
                    btn.disabled = false;
                    console.error('XHR Timeout');
                    progressText.textContent = 'ط²ظ…ط§ظ† ط¢ظ¾ظ„ظˆط¯ ط¨ظ‡ ظ¾ط§غŒط§ظ† ط±ط³غŒط¯';
                    progressBar.style.background = '#f44336';
                };
            
            xhr.timeout = 300000;

                xhr.send(formData);
            });

            if (uploadResult.success) {
                messageInput.value = '';
                selectedFile = null;
                fileInput.value = '';
                filePreview.style.display = 'none';
                progressText.textContent = 'ط¢ظ¾ظ„ظˆط¯ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ط§ظ†ط¬ط§ظ… ط´ط¯';
                fetchMessages(true);
            } else {
                progressText.textContent = uploadResult.message || 'ط®ط·ط§ ط¯ط± ط¢ظ¾ظ„ظˆط¯ ظپط§غŒظ„';
            }
        } catch (e) {
            var pt = document.getElementById('upload-progress-text');
            if (pt) pt.textContent = 'ط®ط·ط§ ط¯ط± ط¨ط±ظ‚ط±ط§ط±غŒ ط§ط±طھط¨ط§ط·';
        }

        setTimeout(function () {
            var pc = document.getElementById('upload-progress-container');
            if (pc) pc.style.display = 'none';
        }, 2000);

        btn.disabled = false;
        messageInput.focus();
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ظ…ط¯غŒط±غŒطھ ط§ظ†طھط®ط§ط¨ ظپط§غŒظ„
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0];
        if (!file) return;
        const maxSize = 100 * 1024 * 1024; // 100MB

        var allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
        if (allowed.indexOf(file.type) === -1) {
            alert('ظپظ‚ط· طھطµط§ظˆغŒط± ظˆ PDF ظ…ط¬ط§ط² ظ‡ط³طھظ†ط¯');
            fileInput.value = '';
            return;
        }
        if (file.size > maxSize) {
            alert('ط­ط¯ط§ع©ط«ط± ط­ط¬ظ… 50 ظ…ع¯ط§ط¨ط§غŒطھ ط§ط³طھ');
            fileInput.value = '';
            return;
        }
        selectedFile = file;
        fileNameSpan.textContent = file.name;
        filePreview.style.display = 'flex';
    });

    removeFileBtn.addEventListener('click', function () {
        selectedFile = null;
        fileInput.value = '';
        filePreview.style.display = 'none';
        fileNameSpan.textContent = '';
    });

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ط®ط±ظˆط¬
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    logoutBtn.addEventListener('click', async function () {
        try { await fetch('/api/auth/logout.php', { method:'POST', credentials:'include' }); }
        catch (e) {}
        window.location.href = '/chat';
    });

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ط¬ط³طھط¬ظˆغŒ ع©ط§ط±ط¨ط±
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            var query = searchInput.value.trim();
            if (searchTimeout) clearTimeout(searchTimeout);
            if (query.length === 0) {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(async function () {
                try {
                    var res = await fetch('/api/users/search.php?q=' + encodeURIComponent(query), { credentials:'include' });
                    var data = await res.json();
                    if (!data.success || !data.users || data.users.length === 0) {
                        searchResults.innerHTML = '<div style="padding:10px;text-align:center;color:#999;font-size:0.85rem;">ظ†طھغŒط¬ظ‡â€Œط§غŒ غŒط§ظپطھ ظ†ط´ط¯</div>';
                        searchResults.style.display = 'block';
                        return;
                    }
                    searchResults.innerHTML = '';
                    data.users.forEach(function (user) {
                        var item = document.createElement('div');
                        item.className = 'search-result-item';
                        var avatar = document.createElement('div');
                        avatar.className = 'user-avatar';
                        avatar.textContent = user.display_name.charAt(0);
                        var name = document.createElement('span');
                        name.textContent = user.display_name;
                        item.appendChild(avatar);
                        item.appendChild(name);
                        item.addEventListener('click', function () { addContact(user.id, user.display_name); });
                        searchResults.appendChild(item);
                    });
                    searchResults.style.display = 'block';
                } catch (e) {
                    searchResults.innerHTML = '<div style="padding:10px;text-align:center;color:#999;">ط®ط·ط§ ط¯ط± ط¬ط³طھط¬ظˆ</div>';
                    searchResults.style.display = 'block';
                }
            }, 300);
        });
    }

    async function addContact(userId, displayName) {
        try {
            var res = await fetch('/api/contacts/add.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                credentials:'include',
                body: JSON.stringify({ contact_id: userId })
            });
            var data = await res.json();
            if (data.success) {
                searchInput.value = '';
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                await loadUsers();
                selectUser(userId, displayName);
            }
        } catch (e) {}
    }
            /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ط­ط°ظپ ظ¾غŒط§ظ…
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    async function deleteMessage(messageId) {
        if (!confirm('ط§غŒظ† ظ¾غŒط§ظ… ط­ط°ظپ ط´ظˆط¯طں')) return;
        try {
            var res = await fetch('/api/messages/delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({ message_id: messageId })

            });
            var data = await res.json();
            if (data.success) {
                var row = document.querySelector('[data-message-id="' + messageId + '"]');
                if (row) row.remove();}
        } catch(e) {}
    }


    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       طھظˆط§ط¨ط¹ ع©ظ…ع©غŒ
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function escapeHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function formatTime(datetime) {
        if (!datetime) return '';
        var d = new Date(datetime);
        return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
    }

    function isScrolledToBottom() {
        return (messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight) < 80;
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
       ط§ط¬ط±ط§غŒ ط§ظˆظ„غŒظ‡
       â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    checkAuth().then(function (ok) {
        if (ok) {
            loadUsers();
            usersInterval = setInterval(loadUsers, 5000);
        }
    });

})();

