(function () {
  var root = document.documentElement;
  var toggle = document.getElementById('themeToggle');
  var stored = localStorage.getItem('home-theme');

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem('home-theme', theme);
    if (toggle) {
      toggle.textContent = theme === 'day' ? '🌙 شب' : '☀️ روز';
    }
  }

  applyTheme(stored === 'day' ? 'day' : 'night');

  if (toggle) {
    toggle.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'day' ? 'night' : 'day';
      applyTheme(next);
    });
  }

  var resumeForm = document.getElementById('resumeForm');
  if (resumeForm) {
    resumeForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      var form = e.currentTarget;
      var status = document.getElementById('resumeStatus');
      var submit = document.getElementById('resumeSubmit');
      status.className = 'status';
      status.textContent = 'در حال ارسال...';
      submit.disabled = true;

      try {
        var res = await fetch('/api/resume/upload.php', {
          method: 'POST',
          body: new FormData(form)
        });
        var data = await res.json();
        if (data.success) {
          status.className = 'status success';
          status.textContent = 'رزومه با موفقیت ثبت شد.';
          form.reset();
        } else {
          status.className = 'status error';
          status.textContent = data.message || 'ارسال رزومه انجام نشد.';
        }
      } catch (err) {
        status.className = 'status error';
        status.textContent = 'خطا در ارتباط با سرور.';
      } finally {
        submit.disabled = false;
      }
    });
  }

  var employerForm = document.getElementById('employerForm');
  if (employerForm) {
    employerForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      var form = e.currentTarget;
      var status = document.getElementById('employerStatus');
      var submit = document.getElementById('employerSubmit');
      status.className = 'status';
      status.textContent = 'در حال ارسال پیام...';
      submit.disabled = true;

      try {
        var payload = {
          employer_name: document.getElementById('employer_name').value.trim(),
          company_name: document.getElementById('company_name').value.trim(),
          employer_email: document.getElementById('employer_email').value.trim(),
          employer_message: document.getElementById('employer_message').value.trim()
        };
        var res = await fetch('/api/employer/message.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        var data = await res.json();
        if (data.success) {
          status.className = 'status success';
          status.textContent = 'پیام کارفرما ثبت شد.';
          form.reset();
        } else {
          status.className = 'status error';
          status.textContent = data.message || 'ارسال پیام انجام نشد.';
        }
      } catch (err) {
        status.className = 'status error';
        status.textContent = 'خطا در ارتباط با سرور.';
      } finally {
        submit.disabled = false;
      }
    });
  }
})();
