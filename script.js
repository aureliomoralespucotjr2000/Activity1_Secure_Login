/* script.js - entrance animation + fluid background for the login / register pages.
   The real login check happens on the server (PHP); this file has no passwords in it. */
(function () {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Word-by-word reveal: wrap each word in a span with its own delay
  function splitWords(el, fromY, base, stagger, dur) {
    if (!el) return;
    const text = el.textContent.trim();
    const words = text.split(/\s+/);
    el.setAttribute('aria-label', text);
    el.textContent = '';
    words.forEach((word, i) => {
      const s = document.createElement('span');
      s.className = 'w';
      s.setAttribute('aria-hidden', 'true');
      s.textContent = word;
      s.style.transform = 'translateY(' + fromY + 'px)';
      const delay = base + i * stagger;
      s.style.transition =
        'transform ' + dur + 'ms cubic-bezier(.215,.61,.355,1) ' + delay + 'ms, ' +
        'opacity '   + dur + 'ms cubic-bezier(.215,.61,.355,1) ' + delay + 'ms';
      el.appendChild(s);
      if (i < words.length - 1) el.appendChild(document.createTextNode(' '));
    });
  }

  if (!reduce) {
    splitWords(document.querySelector('[data-title]'), 26, 480, 85, 720);
    splitWords(document.querySelector('[data-sub]'),   14, 1150, 22, 600);
  }
  requestAnimationFrame(function () {
    requestAnimationFrame(function () { document.body.classList.add('in'); });
  });

  // Fluid background (WebGL). If it cannot start, the CSS ink glow stays as the background.
  const canvas = document.getElementById('fluid');
  if (canvas && typeof fluidSimulation === 'function' && !reduce) {
    try { fluidSimulation(canvas); }
    catch (e) { canvas.style.display = 'none'; }
  }

  // Login form: only the "verifying" feel. The server does the real check.
  const form = document.getElementById('form');
  const user = document.getElementById('user');
  const pass = document.getElementById('pass');
  const go = document.getElementById('go');
  const status = document.getElementById('status');
  if (form && user && pass && go) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!user.value || !pass.value) {
        status.hidden = false;
        status.className = 'alert';
        status.textContent = 'Invalid username or password.';
        form.classList.remove('shake'); void form.offsetWidth; form.classList.add('shake');
        return;
      }
      go.disabled = true;
      go.textContent = 'Verifying...';
      setTimeout(function () { form.submit(); }, reduce ? 0 : 450);
    });
    // After the Back button, make the button usable again
    addEventListener('pageshow', function () { go.disabled = false; go.textContent = go.dataset.label || go.textContent; });
    go.dataset.label = go.textContent;
    (user.value ? pass : user).focus({ preventScroll: true });
  }
})();
