document.addEventListener('DOMContentLoaded', () => {
  const openStatus = document.querySelector('#openStatus');
  const openStatusText = document.querySelector('#openStatusText');
  const now = new Date();
  const hour = now.getHours();
  const isOpen = hour >= 10 && hour < 23;

  if (openStatus) {
    openStatus.textContent = isOpen ? 'Otwarte teraz' : 'Zamknięte teraz';
    openStatus.classList.toggle('closed', !isOpen);
  }

  if (openStatusText) {
    openStatusText.textContent = isOpen ? 'Dzisiaj działamy do 23:00.' : 'Otwieramy codziennie od 10:00.';
  }

  const popup = document.querySelector('#todayPopup');
  const close = document.querySelector('#todayPopupClose');
  const wasClosed = localStorage.getItem('kebabTodayPopupClosed') === new Date().toDateString();

  if (popup && !wasClosed) {
    setTimeout(() => popup.classList.add('show'), 1600);
  }

  if (close && popup) {
    close.addEventListener('click', () => {
      popup.classList.remove('show');
      localStorage.setItem('kebabTodayPopupClosed', new Date().toDateString());
    });
  }
});
