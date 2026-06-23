/* Dennis Augustin – zentrales Script für alle Seiten */
(function () {
  // Header-Hintergrund beim Scrollen
  var header = document.getElementById('header');
  if (header) {
    var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 30); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // Mobiles Menü
  var burger = document.getElementById('burger');
  var nav = document.getElementById('navlinks');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
    });
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        nav.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Scroll-Reveal
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  // Cursor-folgender Glow auf Karten
  document.querySelectorAll('.serv').forEach(function (card) {
    card.addEventListener('mousemove', function (e) {
      var r = card.getBoundingClientRect();
      card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
      card.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
    });
  });

  // Aktiven Menüpunkt anhand des Dateinamens markieren
  var page = (location.pathname.split('/').pop() || 'index.html');
  document.querySelectorAll('.nav-links a:not(.btn)').forEach(function (a) {
    var href = a.getAttribute('href');
    if (href === page) a.classList.add('active');
  });

  // Kontaktformular – AJAX-Handling
  document.querySelectorAll('form[data-contact]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn  = form.querySelector('button[type="submit"]');
      var orig = btn.textContent;

      btn.disabled    = true;
      btn.textContent = 'Wird gesendet …';

      fetch('contact.php', {
        method:  'POST',
        body:    new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          // Formular durch Erfolgsmeldung ersetzen
          form.innerHTML =
            '<div class="form-success">' +
            '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">' +
            '<circle cx="24" cy="24" r="24" fill="#ff5a30"/>' +
            '<path d="M14 24l8 8 12-14" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>' +
            '<h3>Ihre Nachricht ist angekommen.</h3>' +
            '<p>Vielen Dank – ich melde mich schnellstmöglich bei Ihnen zurück.</p>' +
            '</div>';
        } else {
          btn.disabled    = false;
          btn.textContent = orig;
          showFormError(form, data.msg || 'Unbekannter Fehler.');
        }
      })
      .catch(function () {
        btn.disabled    = false;
        btn.textContent = orig;
        showFormError(form, 'Verbindungsfehler. Bitte direkt an mail@dennis-augustin.com schreiben.');
      });
    });
  });

  function showFormError(form, msg) {
    var existing = form.querySelector('.form-error');
    if (existing) existing.remove();
    var el = document.createElement('p');
    el.className   = 'form-error';
    el.textContent = msg;
    form.appendChild(el);
  }
})();
