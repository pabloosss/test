document.addEventListener('DOMContentLoaded', () => {
  const openStatus = document.querySelector('#openStatus');
  const openStatusText = document.querySelector('#openStatusText');
  const now = new Date();
  const hour = now.getHours();
  const isOpen = hour >= 10 && hour < 23;

  if (openStatus) {
    openStatus.textContent = isOpen ? 'Otwarte teraz' : '10:00–23:00';
  }

  if (openStatusText) {
    openStatusText.textContent = isOpen ? 'Dzisiaj działamy do 23:00.' : 'Otwieramy codziennie od 10:00.';
  }
});
