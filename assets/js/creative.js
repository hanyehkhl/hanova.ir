(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // reveal sections on scroll
  var els = document.querySelectorAll('.section, .metrics, .featured-project, .project-card, .skill-group, .timeline-item');
  els.forEach(function (el) { el.classList.add('reveal'); });
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
  } else {
    els.forEach(function (el) { el.classList.add('in'); });
  }

  // cursor spotlight on cards
  document.querySelectorAll('.skill-group, .project-card, .featured-project, .form-card, .contact-card').forEach(function (card) {
    card.classList.add('spot');
    card.addEventListener('pointermove', function (e) {
      var r = card.getBoundingClientRect();
      card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      card.style.setProperty('--my', (e.clientY - r.top) + 'px');
    });
  });

  // gentle 3D tilt on hero image
  var tilt = document.querySelector('.tilt');
  if (tilt && !reduce) {
    var wrap = tilt.parentElement;
    wrap.addEventListener('pointermove', function (e) {
      var r = wrap.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      tilt.style.transform = 'perspective(900px) rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 8) + 'deg)';
    });
    wrap.addEventListener('pointerleave', function () { tilt.style.transform = ''; });
  }
})();

(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // typewriter role
  var el = document.getElementById('typeRole');
  if (el && !reduce) {
    var words = ['Python Developer', 'AI & Machine Learning', 'Computer Vision', 'LLM Builder', 'Automation Nerd'];
    var w = 0, c = words[0].length, del = true;
    (function tick() {
      var word = words[w];
      c += del ? -1 : 1;
      el.textContent = word.slice(0, c);
      var wait = del ? 40 : 80;
      if (!del && c === word.length) { del = true; wait = 1800; }
      else if (del && c === 0) { del = false; w = (w + 1) % words.length; wait = 300; }
      setTimeout(tick, wait);
    })();
  }

  // sliding pill + scrollspy on nav
  var nav = document.getElementById('navLinks');
  if (nav) {
    var pill = nav.querySelector('.nav-pill');
    var links = [].slice.call(nav.querySelectorAll('a[href^="#"]'));
    var move = function (a) {
      if (!a) { pill.style.opacity = 0; return; }
      pill.style.opacity = 1;
      pill.style.width = a.offsetWidth + 'px';
      pill.style.transform = 'translateX(' + (a.offsetLeft) + 'px)';
      links.forEach(function (l) { l.classList.toggle('active', l === a); });
    };
    var current = null;
    nav.addEventListener('pointerover', function (e) { if (e.target.tagName === 'A') move(e.target); });
    nav.addEventListener('pointerleave', function () { move(current); });
    if ('IntersectionObserver' in window) {
      var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { current = nav.querySelector('a[href="#' + e.target.id + '"]'); move(current); }
        });
      }, { rootMargin: '-45% 0px -50% 0px' });
      links.forEach(function (l) { var t = document.querySelector(l.getAttribute('href')); if (t) spy.observe(t); });
    }
  }

  // cursor glow + magnetic buttons (pointer devices only)
  if (!reduce && matchMedia('(pointer:fine)').matches) {
    var glow = document.querySelector('.cursor-glow');
    addEventListener('pointermove', function (e) {
      glow.style.transform = 'translate(' + (e.clientX - 200) + 'px,' + (e.clientY - 200) + 'px)';
      glow.style.opacity = 1;
    });
    document.querySelectorAll('.btn, .chat-fab').forEach(function (b) {
      b.addEventListener('pointermove', function (e) {
        var r = b.getBoundingClientRect();
        b.style.transform = 'translate(' + ((e.clientX - r.left - r.width / 2) * .25) + 'px,' + ((e.clientY - r.top - r.height / 2) * .35) + 'px)';
      });
      b.addEventListener('pointerleave', function () { b.style.transform = ''; });
    });
  }
})();
