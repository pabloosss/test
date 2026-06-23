const navToggle = document.querySelector('#navToggle');
const navLinks = document.querySelector('#navLinks');
const year = document.querySelector('#year');
const contactForm = document.querySelector('#contactForm');
const formNote = document.querySelector('#formNote');
const siteType = document.querySelector('#siteType');
const packageButtons = document.querySelectorAll('.choose-package');
const faqQuestions = document.querySelectorAll('.faq-question');
const CONTACT_EMAIL = 'kontakt@cyberforma.pl';
const CONTACT_PHONE = '+48 000 000 000';
if (year) year.textContent = new Date().getFullYear();
if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(isOpen));
  });
  navLinks.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  }));
}
function scrollToContact(){document.querySelector('#kontakt')?.scrollIntoView({behavior:'smooth'});}
function setMessage(text){const field=document.querySelector('textarea[name="message"]');if(field&&!field.value.trim())field.value=text;}
packageButtons.forEach((button)=>button.addEventListener('click',()=>{if(siteType)siteType.value='Strona firmowa';setMessage(`Interesuje mnie pakiet: ${button.dataset.package}. Proszę o wycenę i informację, co przygotować do startu.`);scrollToContact();}));
faqQuestions.forEach((question)=>question.addEventListener('click',()=>{question.classList.toggle('open');const sign=question.querySelector('span');if(sign)sign.textContent=question.classList.contains('open')?'−':'+';}));
function getCheckedValues(name){return [...document.querySelectorAll(`input[name="${name}"]:checked`)].map((item)=>item.value);}
if(contactForm){contactForm.addEventListener('submit',(event)=>{event.preventDefault();const formData=new FormData(contactForm);const name=String(formData.get('name')||'').trim();const company=String(formData.get('company')||'').trim();const phone=String(formData.get('phone')||'').trim();const email=String(formData.get('email')||'').trim();const selectedSiteType=String(formData.get('siteType')||'').trim();const budget=String(formData.get('budget')||'').trim();const deadline=String(formData.get('deadline')||'').trim();const message=String(formData.get('message')||'').trim();const features=getCheckedValues('features');if(!phone&&!email){if(formNote){formNote.textContent='Podaj telefon albo e-mail, żebym mógł odpisać.';formNote.classList.add('error');}return;}const subject=encodeURIComponent(`Wycena strony internetowej - ${selectedSiteType||'zapytanie'}`);const body=encodeURIComponent(['Nowe zapytanie o stronę internetową','',`Imię i nazwisko: ${name}`,`Firma / branża: ${company||'nie podano'}`,`Telefon: ${phone||'nie podano'}`,`E-mail: ${email||'nie podano'}`,`Typ strony: ${selectedSiteType||'nie wybrano'}`,`Budżet: ${budget||'nie podano'}`,`Termin: ${deadline||'nie podano'}`,`Oczekiwane elementy: ${features.length?features.join(', '):'nie wybrano'}`,'','Opis projektu:',message,'',`Kontakt ze strony: ${CONTACT_PHONE}`].join('\n'));window.location.href=`mailto:${CONTACT_EMAIL}?subject=${subject}&body=${body}`;if(formNote){formNote.textContent=`Otwieram program pocztowy. Dane kontaktowe: ${CONTACT_EMAIL} / ${CONTACT_PHONE}`;formNote.classList.remove('error');}});}
