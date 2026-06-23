(() => {
  const ORDER_KEY = 'kebabDemoOrders';
  const sendButton = document.querySelector('#mailOrder');
  const copyStatus = document.querySelector('#copyStatus');

  if (!sendButton) return;

  const clean = (value) => String(value || '').trim();
  const readOrders = () => {
    try { return JSON.parse(localStorage.getItem(ORDER_KEY) || '[]'); }
    catch { return []; }
  };
  const saveOrders = (orders) => localStorage.setItem(ORDER_KEY, JSON.stringify(orders));
  const getValue = (selector) => clean(document.querySelector(selector)?.value);
  const getText = (selector) => clean(document.querySelector(selector)?.innerText || document.querySelector(selector)?.textContent);

  function makeOrder() {
    if (typeof updateSummary === 'function') updateSummary();

    const name = getValue('#customerName') || 'brak imienia';
    const phone = getValue('#customerPhone');
    const mode = getValue('#modeSelect') || 'Odbiór własny';
    const address = getValue('#customerAddress');
    const payment = getValue('#paymentSelect') || 'Płatność przy odbiorze';
    const product = getText('#summaryTitle');
    const details = getText('#summaryDetails');
    const total = getText('#summaryPrice');

    if (!phone) {
      copyStatus.textContent = 'Podaj telefon klienta.';
      document.querySelector('#customerPhone')?.focus();
      return null;
    }

    if (mode === 'Dostawa' && !address) {
      copyStatus.textContent = 'Przy dostawie podaj adres.';
      document.querySelector('#customerAddress')?.focus();
      return null;
    }

    return {
      id: `KK-DEMO-${new Date().toISOString().slice(0, 10).replaceAll('-', '')}-${Date.now().toString().slice(-5)}`,
      created_at: new Date().toLocaleString('pl-PL'),
      updated_at: new Date().toLocaleString('pl-PL'),
      status: 'nowe',
      payment_status: payment === 'BLIK na telefon' ? 'oczekuje na płatność' : 'płatność przy odbiorze',
      paid: false,
      name,
      phone,
      address,
      mode,
      payment_method: payment,
      product,
      details,
      total,
      order_text: `${product}\n\n${details}\n\nRazem: ${total}`
    };
  }

  const newButton = sendButton.cloneNode(true);
  newButton.textContent = 'Wyślij zamówienie';
  sendButton.replaceWith(newButton);

  newButton.addEventListener('click', (event) => {
    event.preventDefault();
    const order = makeOrder();
    if (!order) return;

    const orders = readOrders();
    orders.unshift(order);
    saveOrders(orders);

    copyStatus.innerHTML = `Zamówienie zapisane w demo. Numer: <b>${order.id}</b>. <a href="admin.html">Otwórz panel admina</a>`;
  });
})();
