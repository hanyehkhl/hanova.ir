(function () {
  var OPENING = 'این صفحه رو یه مدل زبانی نوشته… یا شاید نه.';
  var panel = document.getElementById('chatPanel');
  var messagesEl = document.getElementById('chatMessages');
  var input = document.getElementById('chatInput');
  var sendBtn = document.getElementById('chatSend');
  var mobileToggle = document.getElementById('chatMobileToggle');
  var started = false;
  var busy = false;

  function addMessage(text, role) {
    var el = document.createElement('div');
    el.className = 'msg ' + role;
    el.textContent = text;
    messagesEl.appendChild(el);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return el;
  }

  function setTyping(on) {
    var existing = messagesEl.querySelector('.msg.typing');
    if (on && !existing) {
      addMessage('در حال فکر کردن…', 'bot typing');
    } else if (!on && existing) {
      existing.remove();
    }
  }

  function ensureOpening() {
    if (started) return;
    started = true;
    addMessage(OPENING, 'bot');
  }

  async function sendMessage() {
    var text = input.value.trim();
    if (!text || busy) return;

    ensureOpening();
    addMessage(text, 'user');
    input.value = '';
    busy = true;
    sendBtn.disabled = true;
    setTyping(true);

    try {
      var res = await fetch('/api/resume-bot/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      var raw = await res.text();
      var data = null;
      try {
        data = JSON.parse(raw);
      } catch (parseErr) {
        setTyping(false);
        addMessage('الان سرویس جواب نداد. یک‌بار دیگر امتحان کن.', 'bot');
        return;
      }
      setTyping(false);
      if (data.success) {
        addMessage(data.response, 'bot');
      } else {
        addMessage(data.message || 'الان نمی‌توانم جواب بدهم. شاید بعداً.', 'bot');
      }
    } catch (err) {
      setTyping(false);
      addMessage('اتصال قطع شد. شاید مدل خوابش برد.', 'bot');
    } finally {
      busy = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  if (mobileToggle && panel) {
    mobileToggle.addEventListener('click', function () {
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) {
        ensureOpening();
        input.focus();
      }
    });
  }

  ensureOpening();
})();
