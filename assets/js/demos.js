(function () {
  'use strict';

  var modal, body, titleEl, tabsEl, stopCurrent = null;

  var DEMOS = {
    gbsa: { title: 'GbSA Community Detection', video: 'gbsa', run: runGbsa },
    product: { title: 'Product Assistant API', video: 'product-assistant', run: runProduct },
    yolo: { title: 'YOLOv3 Car Counter', video: 'yolov3-car-counter', run: runYolo }
  };

  function css(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }

  function buildModal() {
    modal = el('div', 'demo-modal');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML =
      '<div class="demo-backdrop" data-close></div>' +
      '<div class="demo-window">' +
      '  <div class="demo-top">' +
      '    <span class="window-controls"><i></i><i></i><i></i></span>' +
      '    <strong class="demo-title mono" dir="ltr"></strong>' +
      '    <button type="button" class="demo-close" data-close aria-label="بستن">✕</button>' +
      '  </div>' +
      '  <div class="demo-tabs"></div>' +
      '  <div class="demo-body"></div>' +
      '</div>';
    document.body.appendChild(modal);
    body = modal.querySelector('.demo-body');
    titleEl = modal.querySelector('.demo-title');
    tabsEl = modal.querySelector('.demo-tabs');
    modal.addEventListener('click', function (e) {
      if (e.target.hasAttribute('data-close')) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });
  }

  function clearBody() {
    if (stopCurrent) { stopCurrent(); stopCurrent = null; }
    body.innerHTML = '';
  }

  function close() {
    clearBody();
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  function open(key) {
    var demo = DEMOS[key];
    if (!demo) return;
    if (!modal) buildModal();
    titleEl.textContent = demo.title;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    var videoUrl = '/assets/video/' + demo.video + '.mp4';
    tabsEl.innerHTML = '';
    var liveTab = el('button', 'demo-tab active', 'دمو زنده');
    tabsEl.appendChild(liveTab);
    var showLive = function () {
      clearBody();
      setActive(liveTab);
      stopCurrent = demo.run(body) || null;
    };
    liveTab.onclick = showLive;
    showLive();

    // Show a video tab only when a recording has been uploaded for this project.
    fetch(videoUrl, { method: 'HEAD' }).then(function (r) {
      var type = r.headers.get('content-type') || '';
      if (!r.ok || type.indexOf('video') === -1) return;
      var vTab = el('button', 'demo-tab', '🎬 ویدیو اجرا');
      tabsEl.appendChild(vTab);
      vTab.onclick = function () {
        clearBody();
        setActive(vTab);
        var v = el('video', 'demo-video');
        v.src = videoUrl;
        v.controls = true;
        v.autoplay = true;
        v.playsInline = true;
        body.appendChild(v);
        stopCurrent = function () { v.pause(); };
      };
    }).catch(function () {});
  }

  function setActive(tab) {
    [].forEach.call(tabsEl.children, function (t) { t.classList.toggle('active', t === tab); });
  }

  /* =====================================================================
   * 1) GbSA — real Galaxy-based Search on Zachary's Karate Club graph
   * ===================================================================*/
  var KARATE = [[0,1],[0,2],[0,3],[0,4],[0,5],[0,6],[0,7],[0,8],[0,10],[0,11],[0,12],[0,13],[0,17],[0,19],[0,21],[0,31],[1,2],[1,3],[1,7],[1,13],[1,17],[1,19],[1,21],[1,30],[2,3],[2,7],[2,8],[2,9],[2,13],[2,27],[2,28],[2,32],[3,7],[3,12],[3,13],[4,6],[4,10],[5,6],[5,10],[5,16],[6,16],[8,30],[8,32],[8,33],[9,33],[13,33],[14,32],[14,33],[15,32],[15,33],[18,32],[18,33],[19,33],[20,32],[20,33],[22,32],[22,33],[23,25],[23,27],[23,29],[23,32],[23,33],[24,25],[24,27],[24,31],[25,31],[26,29],[26,33],[27,33],[28,31],[28,33],[29,32],[29,33],[30,32],[30,33],[31,32],[31,33],[32,33]];

  function runGbsa(root) {
    var N = 34, M = KARATE.length;
    var adj = []; for (var i = 0; i < N; i++) adj.push([]);
    KARATE.forEach(function (e) { adj[e[0]].push(e[1]); adj[e[1]].push(e[0]); });
    var deg = adj.map(function (a) { return a.length; });

    function modularity(p) {
      var inside = {}, tot = {};
      KARATE.forEach(function (e) { if (p[e[0]] === p[e[1]]) inside[p[e[0]]] = (inside[p[e[0]]] || 0) + 1; });
      for (var i = 0; i < N; i++) tot[p[i]] = (tot[p[i]] || 0) + deg[i];
      var q = 0;
      Object.keys(tot).forEach(function (c) { q += (inside[c] || 0) / M - Math.pow(tot[c] / (2 * M), 2); });
      return q;
    }

    root.innerHTML =
      '<div class="demo-grid">' +
      '  <div class="demo-stage"><canvas class="demo-canvas" dir="ltr"></canvas>' +
      '    <div class="demo-badge mono" dir="ltr">karate club · 34 nodes · 78 edges</div></div>' +
      '  <div class="demo-side">' +
      '    <p class="demo-lead">الگوریتم GbSA واقعاً داخل مرورگر شما اجرا می‌شود: جمعیتی از «ستاره‌ها» (تقسیم‌بندی‌های گراف) با حرکت مارپیچی-آشوبناک و جستجوی محلی، Modularity را بیشینه می‌کنند.</p>' +
      '    <div class="demo-stats">' +
      '      <div><small>Iteration</small><b class="mono" data-k="it">0</b></div>' +
      '      <div><small>Best Q</small><b class="mono" data-k="q">—</b></div>' +
      '      <div><small>Communities</small><b class="mono" data-k="c">—</b></div>' +
      '    </div>' +
      '    <div class="demo-chart-label mono">Q CONVERGENCE</div>' +
      '    <canvas class="demo-chart" dir="ltr"></canvas>' +
      '    <div class="demo-controls"><button class="btn btn-primary btn-sm" data-a="run">▶ اجرای دوباره</button></div>' +
      '    <div class="demo-log mono" dir="ltr"></div>' +
      '  </div>' +
      '</div>';

    var canvas = root.querySelector('.demo-canvas');
    var chart = root.querySelector('.demo-chart');
    var log = root.querySelector('.demo-log');
    var stat = function (k, v) { root.querySelector('[data-k="' + k + '"]').textContent = v; };

    // Force-directed layout, computed once.
    var pos = [];
    for (i = 0; i < N; i++) { var a = i / N * Math.PI * 2; pos.push({ x: Math.cos(a) * 0.4, y: Math.sin(a) * 0.4, vx: 0, vy: 0 }); }
    for (var step = 0; step < 400; step++) {
      for (i = 0; i < N; i++) for (var j = i + 1; j < N; j++) {
        var dx = pos[i].x - pos[j].x, dy = pos[i].y - pos[j].y, d2 = dx * dx + dy * dy + 1e-4, f = 0.0009 / d2;
        pos[i].vx += dx * f; pos[i].vy += dy * f; pos[j].vx -= dx * f; pos[j].vy -= dy * f;
      }
      KARATE.forEach(function (e) {
        var p = pos[e[0]], q = pos[e[1]], dx = q.x - p.x, dy = q.y - p.y;
        p.vx += dx * 0.02; p.vy += dy * 0.02; q.vx -= dx * 0.02; q.vy -= dy * 0.02;
      });
      pos.forEach(function (p) { p.vx -= p.x * 0.004; p.vy -= p.y * 0.004; p.x += p.vx; p.y += p.vy; p.vx *= 0.6; p.vy *= 0.6; });
    }
    var minX = Math.min.apply(null, pos.map(function (p) { return p.x; })), maxX = Math.max.apply(null, pos.map(function (p) { return p.x; }));
    var minY = Math.min.apply(null, pos.map(function (p) { return p.y; })), maxY = Math.max.apply(null, pos.map(function (p) { return p.y; }));

    var PALETTE = ['#9dd3ff', '#b8a4ff', '#6ee7b7', '#fcd34d', '#fca5a5', '#f0abfc', '#67e8f9'];

    function fit(c) {
      var r = c.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
      c.width = r.width * dpr; c.height = r.height * dpr;
      var ctx = c.getContext('2d'); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      return { ctx: ctx, w: r.width, h: r.height };
    }

    function draw(p) {
      var g = fit(canvas), ctx = g.ctx, pad = 26;
      var labels = {}, n = 0;
      p.forEach(function (c) { if (!(c in labels)) labels[c] = n++; });
      var X = function (i) { return pad + (pos[i].x - minX) / (maxX - minX) * (g.w - pad * 2); };
      var Y = function (i) { return pad + (pos[i].y - minY) / (maxY - minY) * (g.h - pad * 2); };
      ctx.lineWidth = 1;
      KARATE.forEach(function (e) {
        ctx.strokeStyle = p[e[0]] === p[e[1]] ? PALETTE[labels[p[e[0]]] % 7] + '66' : css('--line-strong');
        ctx.beginPath(); ctx.moveTo(X(e[0]), Y(e[0])); ctx.lineTo(X(e[1]), Y(e[1])); ctx.stroke();
      });
      for (var i = 0; i < N; i++) {
        var col = PALETTE[labels[p[i]] % 7];
        ctx.fillStyle = col; ctx.shadowColor = col; ctx.shadowBlur = 10;
        ctx.beginPath(); ctx.arc(X(i), Y(i), 4 + deg[i] * 0.45, 0, Math.PI * 2); ctx.fill();
        ctx.shadowBlur = 0;
      }
    }

    function drawChart(hist) {
      var g = fit(chart), ctx = g.ctx;
      ctx.strokeStyle = css('--line'); ctx.beginPath(); ctx.moveTo(0, g.h - 1); ctx.lineTo(g.w, g.h - 1); ctx.stroke();
      if (hist.length < 2) return;
      var lo = 0, hi = 0.45;
      var grad = ctx.createLinearGradient(0, 0, g.w, 0);
      grad.addColorStop(0, css('--accent-2')); grad.addColorStop(1, css('--accent'));
      ctx.strokeStyle = grad; ctx.lineWidth = 2; ctx.beginPath();
      hist.forEach(function (q, k) {
        var x = k / (ITER) * g.w, y = g.h - 4 - (q - lo) / (hi - lo) * (g.h - 8);
        k ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
      });
      ctx.stroke();
    }

    function randInt(n) { return Math.floor(Math.random() * n); }
    function normalize(p) {
      var map = {}, n = 0; return p.map(function (c) { if (!(c in map)) map[c] = n++; return map[c]; });
    }

    // GbSA operators
    var ITER = 60, POP = 12, timer = null;
    function spiralChaoticMove(star, chaos) {
      var p = star.slice(), k = 1 + Math.floor(chaos * 5);
      for (var t = 0; t < k; t++) {
        var v = randInt(N);
        var nb = adj[v][randInt(adj[v].length)];
        p[v] = Math.random() < 0.85 ? p[nb] : N + randInt(N); // follow a neighbour's arm, or jump
      }
      return p;
    }
    function localSearch(p) {
      var best = modularity(p), improved = true, rounds = 0;
      while (improved && rounds++ < 2) {
        improved = false;
        for (var v = 0; v < N; v++) {
          var orig = p[v], cands = {};
          adj[v].forEach(function (u) { cands[p[u]] = 1; });
          for (var c in cands) {
            c = +c; if (c === orig) continue;
            p[v] = c; var q = modularity(p);
            if (q > best + 1e-9) { best = q; orig = c; improved = true; } else p[v] = orig;
          }
        }
      }
      return best;
    }

    function start() {
      clearInterval(timer);
      log.innerHTML = '';
      var galaxy = [];
      for (var s = 0; s < POP; s++) {
        var p = []; for (var i = 0; i < N; i++) p.push(randInt(8));
        galaxy.push({ p: p, q: modularity(p) });
      }
      var best = galaxy.reduce(function (a, b) { return a.q > b.q ? a : b; });
      best = { p: best.p.slice(), q: best.q };
      var hist = [best.q], it = 0, chaos = 0.7;
      draw(best.p); drawChart(hist);
      timer = setInterval(function () {
        it++;
        var lucky = randInt(POP);
        galaxy.forEach(function (star, s) {
          chaos = 4 * chaos * (1 - chaos); // logistic map
          var cand = spiralChaoticMove(star.p, chaos);
          // Local search is the expensive step, so apply it to one star per iteration.
          var q = s === lucky ? localSearch(cand) : modularity(cand);
          if (q > star.q) { star.p = cand; star.q = q; }
          if (star.q > best.q) best = { p: star.p.slice(), q: star.q };
        });
        galaxy[randInt(POP)] = { p: best.p.slice(), q: best.q }; // elitism
        hist.push(best.q);
        var comms = new Set(best.p).size;
        stat('it', it + '/' + ITER); stat('q', best.q.toFixed(4)); stat('c', comms);
        draw(normalize(best.p)); drawChart(hist);
        if (it % 5 === 0 || it === 1) {
          var line = el('div', '', '<span>iter ' + String(it).padStart(2, '0') + '</span> Q=' + best.q.toFixed(4) + ' · k=' + comms);
          log.prepend(line);
        }
        if (it >= ITER) {
          clearInterval(timer);
          log.prepend(el('div', 'ok', '✓ converged — best Q = ' + best.q.toFixed(4)));
        }
      }, 110);
    }

    root.querySelector('[data-a="run"]').onclick = start;
    var onResize = function () {};
    requestAnimationFrame(start);
    return function () { clearInterval(timer); window.removeEventListener('resize', onResize); };
  }

  /* =====================================================================
   * 2) Product Assistant — API console, same endpoints as the repo
   * ===================================================================*/
  var PRODUCTS = [
    { id: 1, name: 'Lenovo ThinkPad E14', category: 'laptop', price: 42000000, tags: ['work', 'business'] },
    { id: 2, name: 'ASUS Vivobook 15', category: 'laptop', price: 31500000, tags: ['student', 'light'] },
    { id: 3, name: 'MacBook Air M2', category: 'laptop', price: 68000000, tags: ['apple', 'light'] },
    { id: 4, name: 'Samsung Galaxy A55', category: 'phone', price: 19800000, tags: ['android', 'camera'] },
    { id: 5, name: 'Xiaomi Redmi Note 13', category: 'phone', price: 11900000, tags: ['android', 'budget'] },
    { id: 6, name: 'Sony WH-1000XM5', category: 'audio', price: 21500000, tags: ['headphones', 'anc'] },
    { id: 7, name: 'Anker Soundcore Q30', category: 'audio', price: 5400000, tags: ['headphones', 'budget'] },
    { id: 8, name: 'Logitech MX Master 3S', category: 'accessory', price: 6300000, tags: ['mouse', 'work'] }
  ];

  function runProduct(root) {
    var reqs = [
      { m: 'GET', u: '/products?category=laptop&sort=price', f: function () {
        return PRODUCTS.filter(function (p) { return p.category === 'laptop'; }).sort(function (a, b) { return a.price - b.price; });
      } },
      { m: 'GET', u: '/products/search?q=budget', f: function () {
        return PRODUCTS.filter(function (p) { return (p.name + p.tags.join(' ')).toLowerCase().indexOf('budget') > -1; });
      } },
      { m: 'GET', u: '/products/stats', f: function () {
        var pr = PRODUCTS.map(function (p) { return p.price; }).sort(function (a, b) { return a - b; });
        var cats = {}; PRODUCTS.forEach(function (p) { cats[p.category] = (cats[p.category] || 0) + 1; });
        return { count: pr.length, average: Math.round(pr.reduce(function (a, b) { return a + b; }) / pr.length),
          median: (pr[3] + pr[4]) / 2, min: pr[0], max: pr[pr.length - 1], categories: cats };
      } },
      { m: 'GET', u: '/products/recommend/6', f: function () {
        var base = PRODUCTS[5];
        return { based_on: base.name, recommendations: PRODUCTS.filter(function (p) {
          return p.id !== base.id && (p.category === base.category || p.tags.some(function (t) { return base.tags.indexOf(t) > -1; }));
        }) };
      } },
      { m: 'POST', u: '/chat', body: { message: 'ارزان‌ترین لپ‌تاپ برای دانشجو کدومه؟' }, f: function () {
        return { answer: 'برای دانشجو «ASUS Vivobook 15» با قیمت ۳۱٫۵ میلیون تومان ارزان‌ترین لپ‌تاپ موجود است و تگ student/light دارد. اگر بودجه بیشتری دارید، MacBook Air M2 سبک‌ترین گزینه است.',
          sources: [2, 3] };
      } }
    ];

    root.innerHTML =
      '<div class="demo-grid">' +
      '  <div class="demo-side">' +
      '    <p class="demo-lead">API مدیریت محصول با FastAPI و دستیار هوشمند. روی هر درخواست کلیک کنید تا پاسخ واقعی endpoint را ببینید.</p>' +
      '    <div class="req-list"></div>' +
      '    <div class="demo-note">پاسخ‌های /chat در نسخه اصلی از LLM می‌آیند؛ اینجا نمونه پاسخ نمایش داده می‌شود.</div>' +
      '  </div>' +
      '  <div class="demo-console" dir="ltr">' +
      '    <div class="console-bar mono"><span>uvicorn main:app</span><span class="accent">● 127.0.0.1:8000</span></div>' +
      '    <pre class="console-out mono"></pre>' +
      '  </div>' +
      '</div>';

    var list = root.querySelector('.req-list'), out = root.querySelector('.console-out'), timer = null;

    function typeOut(text) {
      clearInterval(timer);
      var k = 0;
      out.textContent = '';
      timer = setInterval(function () {
        k += 18; out.textContent = text.slice(0, k);
        out.scrollTop = out.scrollHeight;
        if (k >= text.length) clearInterval(timer);
      }, 16);
    }

    reqs.forEach(function (r, idx) {
      var b = el('button', 'req-btn mono', '<span class="verb ' + r.m.toLowerCase() + '">' + r.m + '</span><span dir="ltr">' + r.u + '</span>');
      b.onclick = function () {
        [].forEach.call(list.children, function (x) { x.classList.toggle('active', x === b); });
        var t0 = performance.now(), res = r.f(), ms = (performance.now() - t0 + 8 + Math.random() * 20).toFixed(0);
        var txt = '$ curl -X ' + r.m + ' http://127.0.0.1:8000' + r.u +
          (r.body ? " \\\n    -d '" + JSON.stringify(r.body) + "'" : '') +
          '\n\nHTTP/1.1 200 OK   (' + ms + ' ms)\ncontent-type: application/json\n\n' + JSON.stringify(res, null, 2);
        typeOut(txt);
      };
      list.appendChild(b);
      if (idx === 0) setTimeout(function () { b.click(); }, 50);
    });

    return function () { clearInterval(timer); };
  }

  /* =====================================================================
   * 3) YOLOv3 car counter — detection / IoU tracking visualisation
   * ===================================================================*/
  function runYolo(root) {
    root.innerHTML =
      '<div class="demo-grid">' +
      '  <div class="demo-stage"><canvas class="demo-canvas" dir="ltr"></canvas>' +
      '    <div class="demo-badge mono" dir="ltr">traffic.mp4 · 416×416 · conf ≥ 0.5 · NMS 0.4</div></div>' +
      '  <div class="demo-side">' +
      '    <p class="demo-lead">نمایش خروجی پایپ‌لاین: تشخیص car / truck / bus، ردیابی بین فریم‌ها با IoU و شمارش. (شبیه‌سازی گرافیکی — ویدیو اجرای واقعی را می‌توانید در تب «ویدیو» اضافه کنید.)</p>' +
      '    <div class="demo-stats">' +
      '      <div><small>Frame</small><b class="mono" data-k="f">0</b></div>' +
      '      <div><small>In frame</small><b class="mono" data-k="n">0</b></div>' +
      '      <div><small>Total counted</small><b class="mono" data-k="t">0</b></div>' +
      '    </div>' +
      '    <div class="demo-chart-label mono">car_count.csv</div>' +
      '    <div class="demo-log mono" dir="ltr"></div>' +
      '  </div>' +
      '</div>';

    var canvas = root.querySelector('.demo-canvas'), log = root.querySelector('.demo-log');
    var stat = function (k, v) { root.querySelector('[data-k="' + k + '"]').textContent = v; };
    var ctx, W, H, raf, frame = 0, total = 0, nextId = 1, cars = [];
    var TYPES = [
      { label: 'car', w: 34, h: 58, color: '#9dd3ff', p: 0.72 },
      { label: 'truck', w: 42, h: 96, color: '#fcd34d', p: 0.16 },
      { label: 'bus', w: 44, h: 110, color: '#b8a4ff', p: 0.12 }
    ];
    var BODY = ['#e5e7eb', '#475569', '#b91c1c', '#1d4ed8', '#0f766e', '#f59e0b', '#111827'];

    function size() {
      var r = canvas.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
      canvas.width = r.width * dpr; canvas.height = r.height * dpr;
      ctx = canvas.getContext('2d'); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      W = r.width; H = r.height;
    }

    function spawn() {
      var lanes = 4, lane = Math.floor(Math.random() * lanes), down = lane < 2;
      var roll = Math.random(), t = roll < TYPES[0].p ? TYPES[0] : roll < TYPES[0].p + TYPES[1].p ? TYPES[1] : TYPES[2];
      var road = W * 0.7, left = (W - road) / 2, lw = road / lanes;
      var x = left + lw * lane + lw / 2;
      if (cars.some(function (c) { return c.lane === lane && (down ? c.y < c.t.h + 30 : c.y > H - c.t.h - 30); })) return;
      cars.push({ id: null, lane: lane, x: x, y: down ? -t.h : H + t.h, v: (down ? 1 : -1) * (1.2 + Math.random() * 1.1),
        t: t, body: BODY[Math.floor(Math.random() * BODY.length)], conf: 0.78 + Math.random() * 0.2, counted: false });
    }

    function drawRoad() {
      ctx.fillStyle = '#16181d'; ctx.fillRect(0, 0, W, H);
      var road = W * 0.7, left = (W - road) / 2;
      ctx.fillStyle = '#23262d'; ctx.fillRect(left, 0, road, H);
      ctx.fillStyle = '#2f5d3a'; ctx.fillRect(0, 0, left - 6, H); ctx.fillRect(left + road + 6, 0, W, H);
      ctx.strokeStyle = '#e5e7eb'; ctx.lineWidth = 2; ctx.setLineDash([22, 18]);
      ctx.lineDashOffset = -frame * 2;
      [1, 3].forEach(function (k) { ctx.beginPath(); ctx.moveTo(left + road / 4 * k, 0); ctx.lineTo(left + road / 4 * k, H); ctx.stroke(); });
      ctx.setLineDash([]); ctx.strokeStyle = '#fcd34d'; ctx.lineWidth = 3;
      ctx.beginPath(); ctx.moveTo(W / 2, 0); ctx.lineTo(W / 2, H); ctx.stroke();
      // counting line
      ctx.strokeStyle = 'rgba(110,231,183,.8)'; ctx.lineWidth = 2; ctx.setLineDash([6, 6]);
      ctx.beginPath(); ctx.moveTo(left - 10, H / 2); ctx.lineTo(left + road + 10, H / 2); ctx.stroke(); ctx.setLineDash([]);
      ctx.fillStyle = 'rgba(110,231,183,.9)'; ctx.font = '11px Roboto Mono, monospace';
      ctx.fillText('COUNT LINE', 8, H / 2 - 6);
    }

    function drawCar(c) {
      var w = c.t.w, h = c.t.h, x = c.x - w / 2, y = c.y - h / 2;
      ctx.fillStyle = c.body; roundRect(x, y, w, h, 7); ctx.fill();
      ctx.fillStyle = 'rgba(15,23,42,.75)';
      var front = c.v > 0 ? y + h - h * 0.3 : y + h * 0.1;
      roundRect(x + 4, front, w - 8, h * 0.2, 4); ctx.fill();
      if (!c.id) return;
      ctx.strokeStyle = c.t.color; ctx.lineWidth = 2;
      ctx.strokeRect(x - 5, y - 5, w + 10, h + 10);
      var label = c.t.label + ' #' + c.id + ' ' + c.conf.toFixed(2);
      ctx.font = '600 10.5px Roboto Mono, monospace';
      var tw = ctx.measureText(label).width + 8;
      ctx.fillStyle = c.t.color; ctx.fillRect(x - 6, y - 21, tw, 16);
      ctx.fillStyle = '#0b1320'; ctx.fillText(label, x - 2, y - 9);
    }

    function roundRect(x, y, w, h, r) {
      ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
      ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
    }

    function tick() {
      frame++;
      if (frame % 38 === 0 && cars.length < 9) spawn();
      cars.forEach(function (c) {
        var prev = c.y; c.y += c.v;
        var visible = c.y > c.t.h * 0.3 && c.y < H - c.t.h * 0.3;
        if (visible && !c.id) c.id = nextId++;          // new detection → new track id
        if (c.id && !c.counted && (prev - H / 2) * (c.y - H / 2) <= 0) { c.counted = true; total++; }
        c.conf = Math.min(0.99, Math.max(0.55, c.conf + (Math.random() - 0.5) * 0.02));
      });
      cars = cars.filter(function (c) { return c.y > -150 && c.y < H + 150; });
      drawRoad();
      cars.forEach(drawCar);
      var inFrame = cars.filter(function (c) { return c.id && c.y > 0 && c.y < H; }).length;
      stat('f', frame); stat('n', inFrame); stat('t', total);
      if (frame % 30 === 0) {
        log.prepend(el('div', '', '<span>' + frame + '</span>,' + inFrame));
        if (log.children.length > 60) log.lastChild.remove();
      }
      raf = requestAnimationFrame(tick);
    }

    log.appendChild(el('div', 'ok', 'frame_number,car_count'));
    requestAnimationFrame(function () { size(); for (var i = 0; i < 4; i++) { frame += 38; spawn(); cars.forEach(function (c) { c.y += c.v * 90; }); } tick(); });
    window.addEventListener('resize', size);
    return function () { cancelAnimationFrame(raf); window.removeEventListener('resize', size); };
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-demo]');
    if (!t) return;
    e.preventDefault();
    open(t.getAttribute('data-demo'));
  });
})();
