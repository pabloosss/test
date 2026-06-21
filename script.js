const navToggle = document.querySelector('#navToggle');
const navLinks = document.querySelector('#navLinks');
const year = document.querySelector('#year');
const packageInput = document.querySelector('#packageInput');
const packageButtons = document.querySelectorAll('.choose-package');
const contactForm = document.querySelector('#contactForm');
const formNote = document.querySelector('#formNote');
const faqQuestions = document.querySelectorAll('.faq-question');

if (year) {
  year.textContent = new Date().getFullYear();
}

if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });

  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => navLinks.classList.remove('open'));
  });
}

packageButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const selectedPackage = button.dataset.package;

    if (packageInput) {
      packageInput.value = selectedPackage;
    }

    document.querySelector('#kontakt')?.scrollIntoView({ behavior: 'smooth' });
  });
});

faqQuestions.forEach((question) => {
  question.addEventListener('click', () => {
    question.classList.toggle('faq-item-open');
    const sign = question.querySelector('span');

    if (sign) {
      sign.textContent = question.classList.contains('faq-item-open') ? '−' : '+';
    }
  });
});

if (contactForm) {
  contactForm.addEventListener('submit', (event) => {
    event.preventDefault();

    const formData = new FormData(contactForm);
    const name = formData.get('name');
    const email = formData.get('email');
    const selectedPackage = formData.get('package') || 'nie wybrano';
    const message = formData.get('message');

    const subject = encodeURIComponent(`Wycena strony internetowej - ${selectedPackage}`);
    const body = encodeURIComponent(
      `Imię: ${name}\nE-mail: ${email}\nPakiet: ${selectedPackage}\n\nWiadomość:\n${message}`
    );

    window.location.href = `mailto:kontakt@example.pl?subject=${subject}&body=${body}`;

    if (formNote) {
      formNote.textContent = 'Otwieram program pocztowy z gotową wiadomością.';
    }
  });
}
