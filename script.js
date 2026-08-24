const navToggle = document.querySelector('#navToggle');
const navLinks = document.querySelector('#navLinks');
const year = document.querySelector('#year');
const contactForm = document.querySelector('#contactForm');
const formStatus = document.querySelector('#formStatus');

if (year) year.textContent = new Date().getFullYear();

if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(open));
  });

  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      navToggle.setAttribute('aria-expanded', 'false');
    });
  });
}

if (contactForm) {
  contactForm.addEventListener('submit', (event) => {
    event.preventDefault();

    const name = document.querySelector('#name')?.value.trim() || '';
    const phone = document.querySelector('#phone')?.value.trim() || '';
    const email = document.querySelector('#email')?.value.trim() || '';
    const message = document.querySelector('#message')?.value.trim() || '';

    const subject = encodeURIComponent(`Zapytanie ze strony CyberForma — ${name || 'nowy klient'}`);
    const body = encodeURIComponent(
      `Imię / firma: ${name}\nTelefon: ${phone || '-'}\nE-mail: ${email}\n\nWiadomość:\n${message}`
    );

    const contactEmail = 'kontakt@cyberforma.pl';
    window.location.href = `mailto:${contactEmail}?subject=${subject}&body=${body}`;

    if (formStatus) {
      formStatus.textContent = `Otwieram wiadomość do ${contactEmail}.`;
    }
  });
}
