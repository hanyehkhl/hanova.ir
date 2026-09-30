document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const err = document.getElementById('errorMsg');
    btn.disabled = true;
    btn.textContent = 'ط¯ط± ط­ط§ظ„ ظˆط±ظˆط¯...';
    err.style.display = 'none';
    try {
        const r = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'include',
            body: JSON.stringify({
                username: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value
            })
        });
        const d = await r.json();
        if (d.success) {
            window.location.href = '/workspace';
        } else {
            err.textContent = d.message || 'ظ†ط§ظ… ع©ط§ط±ط¨ط±غŒ غŒط§ ط±ظ…ط² ط¹ط¨ظˆط± ط§ط´طھط¨ط§ظ‡ ط§ط³طھ';
            err.style.display = 'block';
        }
    } catch (x) {
        err.textContent = 'ط®ط·ط§ ط¯ط± ط§طھطµط§ظ„ ط¨ظ‡ ط³ط±ظˆط±';
        err.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'ظˆط±ظˆط¯';
});

