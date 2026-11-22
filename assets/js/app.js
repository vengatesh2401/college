/* ClubQuest front-end behaviour (vanilla JS, Fetch API) */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  /* page loader */
  const hideLoader = () => $('#page-loader')?.classList.add('done');
  window.addEventListener('load', () => setTimeout(hideLoader, 150));
  setTimeout(hideLoader, 2500);
  window.addEventListener('pageshow', hideLoader);
  document.addEventListener('click', e => {           // show loader on internal navigation
    const a = e.target.closest('a[href]');
    if (a && a.origin === location.origin && !a.target && !e.ctrlKey && !e.metaKey && !a.hash && !a.hasAttribute('download'))
      setTimeout(() => $('#page-loader')?.classList.remove('done'), 250);
  });

  /* theme toggle */
  $('#theme-toggle')?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('ccf-theme', next); } catch (e) {}
    $$('.chart').forEach(renderChart);
  });

  /* mobile nav / sidebar / filters / user menu */
  const navToggle = $('.nav-toggle');
  navToggle?.addEventListener('click', () => {
    const open = $('#main-nav').classList.toggle('open');
    navToggle.setAttribute('aria-expanded', open);
  });
  $('.filter-toggle')?.addEventListener('click', () => $('#filters').classList.toggle('open'));
  $('.user-chip:not(.static)')?.addEventListener('click', e => { e.stopPropagation(); e.currentTarget.parentElement.classList.toggle('open'); });
  document.addEventListener('click', () => $('.user-menu')?.classList.remove('open'));

  /* dismissible alerts, auto-hide success */
  document.addEventListener('click', e => { if (e.target.closest('.alert-x')) e.target.closest('.alert').remove(); });
  setTimeout(() => $$('.alert-success').forEach(a => { a.style.transition = 'opacity .5s'; a.style.opacity = 0; setTimeout(() => a.remove(), 500); }), 5000);

  /* confirm dialogs on destructive forms */
  document.addEventListener('submit', e => {
    const msg = e.target.dataset?.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });

  /* scroll reveal */
  const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { threshold: .08 }) : null;
  $$('.reveal').forEach((el, i) => { el.style.transitionDelay = Math.min(i % 6, 5) * 60 + 'ms'; io ? io.observe(el) : el.classList.add('in'); });

  /* count-up numbers */
  $$('[data-count]').forEach(el => {
    const target = +el.dataset.count, t0 = performance.now(), dur = 900;
    const tick = t => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  });

  /* live search suggestions */
  const input = $('#live-search'), box = $('#search-results');
  if (input && box) {
    let timer, ctrl, idx = -1;
    const close = () => { box.hidden = true; idx = -1; };
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 2) return close();
      timer = setTimeout(async () => {
        ctrl?.abort(); ctrl = new AbortController();
        try {
          const r = await fetch(`${CCF.base}/api/search.php?q=${encodeURIComponent(q)}`, { signal: ctrl.signal });
          const { results } = await r.json();
          box.innerHTML = results.length
            ? results.map(c => `<a href="${esc(c.url)}"><span class="sr-ico" style="--lc:${esc(c.color)}"><i class="fa-solid ${esc(c.icon)}"></i></span><span><b>${esc(c.name)}</b><small>${esc(c.cat_name)}</small></span></a>`).join('')
              + `<a href="${CCF.base}/clubs.php?q=${encodeURIComponent(q)}"><span class="sr-ico"><i class="fa-solid fa-magnifying-glass"></i></span><span><b>See all results for “${esc(q)}”</b></span></a>`
            : '<div class="none">No clubs found</div>';
          box.hidden = false; idx = -1;
        } catch (e) { if (e.name !== 'AbortError') close(); }
      }, 220);
    });
    input.addEventListener('keydown', e => {
      const items = $$('a', box);
      if (e.key === 'Enter') { e.preventDefault(); const t = items[idx]; location.href = t ? t.href : `${CCF.base}/clubs.php?q=${encodeURIComponent(input.value.trim())}`; }
      else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault(); if (!items.length) return;
        idx = (idx + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((a, i) => a.classList.toggle('sel', i === idx));
      } else if (e.key === 'Escape') close();
    });
    document.addEventListener('click', e => { if (!e.target.closest('.search-box')) close(); });
  }

  /* event registration (AJAX) */
  const toast = (msg, ok = true) => {
    const t = document.createElement('div');
    t.className = 'alert alert-' + (ok ? 'success' : 'error');
    t.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:200;max-width:340px;box-shadow:0 10px 30px rgba(0,0,0,.25)';
    t.innerHTML = `<i class="fa-solid ${ok ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i><span>${esc(msg)}</span>`;
    document.body.appendChild(t); setTimeout(() => t.remove(), 3500);
  };
  document.addEventListener('click', async e => {
    const btn = e.target.closest('.event-reg');
    if (!btn) return;
    btn.disabled = true;
    const old = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
    try {
      const body = new URLSearchParams({ event_id: btn.dataset.id });
      const r = await fetch(`${CCF.base}/api/event-register.php`, { method: 'POST', headers: { 'X-CSRF-Token': CCF.csrf }, body });
      const d = await r.json();
      if (d.ok) {
        btn.classList.toggle('btn-success', d.registered); btn.classList.toggle('btn-primary', !d.registered); btn.classList.toggle('is-on', d.registered);
        btn.innerHTML = d.registered ? '<i class="fa-solid fa-check"></i> Registered' : 'Register';
        document.querySelectorAll(`.event-card[data-event="${btn.dataset.id}"] .reg-count`).forEach(c => c.textContent = d.count);
        toast(d.message);
      } else { btn.innerHTML = old; toast(d.message || 'Could not register.', false); }
    } catch (err) { btn.innerHTML = old; toast('Network error. Please try again.', false); }
    btn.disabled = false;
  });

  /* dashboard charts (dependency-free SVG) */
  const PALETTE = ['#FF5A36', '#6C3CE9', '#14B8A6', '#FFC83D', '#E11D74', '#3B82F6', '#16A34A', '#F97316'];
  const STATUS = { pending: '#C27803', approved: '#14915B', rejected: '#D6336C' };
  function renderChart(el) {
    let rows; try { rows = JSON.parse(el.dataset.rows || '[]'); } catch (e) { rows = []; }
    rows = rows.map(r => ({ label: String(r.label), value: +r.value }));
    if (!rows.length || rows.every(r => !r.value)) { el.innerHTML = '<div class="chart-empty">No data yet</div>'; return; }
    const color = (r, i) => STATUS[r.label] || PALETTE[i % PALETTE.length];
    const type = el.dataset.chart;
    if (type === 'donut') {
      const total = rows.reduce((a, r) => a + r.value, 0), R = 70, C = 2 * Math.PI * R; let off = 0;
      const arcs = rows.map((r, i) => { const len = r.value / total * C; const s = `<circle cx="100" cy="90" r="${R}" fill="none" stroke="${color(r, i)}" stroke-width="28" stroke-dasharray="${len} ${C - len}" stroke-dashoffset="${-off}" transform="rotate(-90 100 90)"><title>${esc(r.label)}: ${r.value}</title></circle>`; off += len; return s; }).join('');
      el.innerHTML = `<svg viewBox="0 0 200 180" role="img"><circle cx="100" cy="90" r="${R}" fill="none" stroke="var(--line)" stroke-width="28"/>${arcs}<text x="100" y="96" text-anchor="middle" class="val" style="font-size:22px">${total}</text></svg>
        <div class="legend">${rows.map((r, i) => `<span><i style="background:${color(r, i)}"></i>${esc(r.label)} (${r.value})</span>`).join('')}</div>`;
    } else if (type === 'bar') {
      const W = 460, rowH = 34, H = rows.length * rowH + 6, max = Math.max(...rows.map(r => r.value), 1), lw = 150;
      el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img">${rows.map((r, i) => {
        const y = i * rowH + 4, w = Math.max(4, (W - lw - 40) * r.value / max), lab = r.label.length > 22 ? r.label.slice(0, 21) + '…' : r.label;
        return `<text x="0" y="${y + 18}" class="lbl">${esc(lab)}</text><rect x="${lw}" y="${y + 4}" width="${w}" height="18" rx="9" fill="${PALETTE[i % PALETTE.length]}"><title>${esc(r.label)}: ${r.value}</title></rect><text x="${lw + w + 8}" y="${y + 18}" class="val">${r.value}</text>`;
      }).join('')}</svg>`;
    } else { // line / area
      const W = 460, H = 220, pad = 30, max = Math.max(...rows.map(r => r.value), 1);
      const pts = rows.map((r, i) => [pad + i * (W - pad * 2) / Math.max(rows.length - 1, 1), H - pad - (H - pad * 2) * r.value / max]);
      const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
      el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img"><defs><linearGradient id="lg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#FF5A36" stop-opacity=".35"/><stop offset="1" stop-color="#FF5A36" stop-opacity="0"/></linearGradient></defs>
        <path d="${d} L${pts.at(-1)[0]} ${H - pad} L${pts[0][0]} ${H - pad} Z" fill="url(#lg)"/><path d="${d}" fill="none" stroke="#FF5A36" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        ${pts.map((p, i) => `<circle cx="${p[0]}" cy="${p[1]}" r="5" fill="var(--surface)" stroke="#FF5A36" stroke-width="3"><title>${esc(rows[i].label)}: ${rows[i].value}</title></circle><text x="${p[0]}" y="${H - 8}" text-anchor="middle" class="lbl">${esc(rows[i].label)}</text><text x="${p[0]}" y="${p[1] - 12}" text-anchor="middle" class="val">${rows[i].value}</text>`).join('')}</svg>`;
    }
  }
  $$('.chart').forEach(renderChart);


  /* responsive helpers */
  $$('.table').forEach(t => {                          // label cells so tables can stack on phones
    const heads = $$('thead th', t).map(th => th.textContent.trim());
    $$('tbody tr', t).forEach(tr => $$('td', tr).forEach((td, i) => td.setAttribute('data-label', heads[i] || '')));
  });
  const sb = $('#sidebar'), bd = $('#sidebar-backdrop');
  const setSidebar = open => { sb?.classList.toggle('open', open); bd?.classList.toggle('show', open); };
  $('.sidebar-toggle')?.addEventListener('click', () => setSidebar(!sb.classList.contains('open')));
  bd?.addEventListener('click', () => setSidebar(false));
  $$('#sidebar a').forEach(a => a.addEventListener('click', () => setSidebar(false)));
  window.addEventListener('resize', () => { if (innerWidth > 980) { setSidebar(false); $('#main-nav')?.classList.remove('open'); } });
})();
