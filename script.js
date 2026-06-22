const navToggle = document.querySelector('#navToggle');
const navLinks = document.querySelector('#navLinks');
const year = document.querySelector('#year');
const menuGrid = document.querySelector('#menuGrid');
const categoryTabs = document.querySelector('#categoryTabs');
const productSelect = document.querySelector('#productSelect');
const orderForm = document.querySelector('#orderForm');
const quantityInput = document.querySelector('#quantity');
const qtyMinus = document.querySelector('#qtyMinus');
const qtyPlus = document.querySelector('#qtyPlus');
const modeSelect = document.querySelector('#modeSelect');
const noteInput = document.querySelector('#note');
const summaryTitle = document.querySelector('#summaryTitle');
const summaryDetails = document.querySelector('#summaryDetails');
const summaryPrice = document.querySelector('#summaryPrice');
const copyOrder = document.querySelector('#copyOrder');
const copyStatus = document.querySelector('#copyStatus');
const mailOrder = document.querySelector('#mailOrder');

const fallbackMenu = {
  products: [
    { id: 'lawasz-kurczak', name: 'Lawasz Kurczak', category: 'lawasz', badge: 'Bestseller', price: 24, description: 'Kurczak, świeże warzywa i wybrany sos w lawaszu.', image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'lawasz-mieszany', name: 'Lawasz Mieszany', category: 'lawasz', badge: 'Najczęściej wybierane', price: 29, description: 'Kurczak + wołowina-baranina, warzywa i sos mieszany.', image: 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'box-frytki', name: 'Kebab Box z frytkami', category: 'box', badge: 'Na szybko', price: 26, description: 'Mięso, frytki, surówka i sos. Dobry na lunch.', image: 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'talerz', name: 'Kebab na talerzu', category: 'talerz', badge: 'Duża porcja', price: 37, description: 'Mięso, frytki albo ryż, surówka i sos.', image: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'adana', name: 'Adana Grill', category: 'grill', badge: 'Ostry hit', price: 44, description: 'Ostry szaszłyk wołowy, lawasz, dodatki i sos.', image: 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'falafel', name: 'Falafel Rolada', category: 'vege', badge: 'Vege', price: 22, description: 'Falafel, warzywa, hummus albo sos czosnkowy.', image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85', active: true },
    { id: 'zestaw', name: 'Lawasz Zestaw', category: 'lawasz', badge: 'Zestaw', price: 32.5, description: 'Lawasz, frytki i napój 0,5 l.', image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=900&q=85', active: true }
  ],
  ingredients: {
    sizes: [
      { id: 'standard', name: 'standard', label: 'Standard', price: 0, default: true, active: true },
      { id: 'mega', name: 'mega', label: 'Mega', price: 8, default: false, active: true },
      { id: 'mini', name: 'mini', label: 'Mini', price: -5, default: false, active: true }
    ],
    meats: [
      { id: 'kurczak', name: 'Kurczak', label: 'Kurczak', price: 0, default: true, active: true },
      { id: 'wolowina', name: 'Wołowina-baranina', label: 'Wołowina-baranina', price: 4, default: false, active: true },
      { id: 'mieszane', name: 'Mieszane', label: 'Mieszane', price: 5, default: false, active: true },
      { id: 'falafel', name: 'Falafel', label: 'Falafel', price: -2, default: false, active: true }
    ],
    sauces: [
      { id: 'czosnkowy', name: 'czosnkowy', label: 'czosnkowy', price: 0, default: true, active: true },
      { id: 'lagodny', name: 'łagodny', label: 'łagodny', price: 0, default: false, active: true },
      { id: 'mieszany', name: 'mieszany', label: 'mieszany', price: 0, default: false, active: true },
      { id: 'ostry', name: 'ostry', label: 'ostry', price: 0, default: false, active: true }
    ],
    inside: [
      { id: 'surowka', name: 'surówka', label: 'surówka', price: 0, default: true, active: true },
      { id: 'pomidor', name: 'pomidor', label: 'pomidor', price: 0, default: true, active: true },
      { id: 'ogorek', name: 'ogórek', label: 'ogórek', price: 0, default: true, active: true },
      { id: 'cebula', name: 'cebula', label: 'cebula', price: 0, default: false, active: true }
    ],
    extras: [
      { id: 'ser', name: 'ser', label: 'Ser', price: 4, default: false, active: true },
      { id: 'frytki-srodek', name: 'frytki w środku', label: 'Frytki w środku', price: 6, default: false, active: true },
      { id: 'halloumi', name: 'halloumi', label: 'Halloumi', price: 8, default: false, active: true },
      { id: 'napoj', name: 'napój', label: 'Napój', price: 7, default: false, active: true }
    ]
  }
};

let menuData = JSON.parse(JSON.stringify(fallbackMenu));
let products = menuData.products;
let activeCategory = 'all';
let lastOrderText = '';
let lastPayload = null;

const categoryNames = { lawasz: 'Lawasz', box: 'Box', talerz: 'Talerz', grill: 'Grill', vege: 'Vege', dodatki: 'Dodatki' };

if (year) year.textContent = new Date().getFullYear();
if (mailOrder) mailOrder.textContent = 'Wyślij zamówienie';

if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
  navLinks.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => navLinks.classList.remove('open')));
}

document.querySelectorAll('img[data-fallback]').forEach((image) => image.addEventListener('error', () => image.classList.add('image-failed')));

function formatPrice(value) {
  return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(Number(value || 0));
}
function plusPrice(value) {
  const price = Number(value || 0);
  if (price === 0) return '';
  return price > 0 ? ` +${formatPrice(price)}` : ` ${formatPrice(price)}`;
}
function activeItems(items = []) { return items.filter((item) => item.active !== false); }
function getProductById(id) { return products.find((product) => product.id === id) || products[0] || fallbackMenu.products[0]; }
function getSelectedProduct() { return getProductById(productSelect?.value); }
function getPaymentSelect() { return document.querySelector('#paymentSelect'); }

function getAllowedItems(group) {
  const product = getSelectedProduct();
  const all = activeItems(menuData.ingredients[group] || []);
  const allowed = product?.allowed_options?.[group];
  if (!Array.isArray(allowed) || allowed.length === 0) return all;
  const allowedSet = new Set(allowed.map(String));
  const filtered = all.filter((item) => allowedSet.has(String(item.id)) || allowedSet.has(String(item.name)));
  return filtered.length ? filtered : all;
}

async function loadMenuFromServer() {
  try {
    const response = await fetch('get-menu.php', { cache: 'no-store' });
    if (!response.ok) throw new Error('no php menu');
    const data = await response.json();
    if (Array.isArray(data.products)) {
      menuData = { products: data.products.filter((p) => p.active !== false), ingredients: { ...fallbackMenu.ingredients, ...(data.ingredients || {}) } };
      products = menuData.products;
    }
  } catch {
    menuData = JSON.parse(JSON.stringify(fallbackMenu));
    products = menuData.products;
  }
}

function injectCustomerFields() {
  if (!orderForm || document.querySelector('#customerPhone')) return;
  orderForm.insertAdjacentHTML('afterbegin', `
    <div class="form-row">
      <div class="form-block"><label for="customerName">Imię</label><input id="customerName" type="text" placeholder="Np. Paweł"></div>
      <div class="form-block"><label for="customerPhone">Telefon *</label><input id="customerPhone" type="tel" placeholder="Np. 123 456 789" required></div>
    </div>
  `);
  modeSelect?.closest('.form-row')?.insertAdjacentHTML('afterend', `
    <div class="form-block" id="addressBlock" hidden>
      <label for="customerAddress">Adres dostawy</label>
      <input id="customerAddress" type="text" placeholder="Ulica, numer, miasto">
    </div>
    <div class="form-block">
      <label for="paymentSelect">Płatność</label>
      <select id="paymentSelect">
        <option value="Płatność przy odbiorze">Płatność przy odbiorze</option>
        <option value="Karta przy odbiorze">Karta przy odbiorze</option>
        <option value="BLIK na telefon">BLIK na telefon — ręcznie</option>
      </select>
    </div>
  `);
}

function renderProductOptions() {
  if (!productSelect) return;
  productSelect.innerHTML = products.map((p) => `<option value="${p.id}">${p.name} — od ${formatPrice(p.price)}</option>`).join('');
}
function renderRadio(container, name, items) {
  if (!container) return;
  const active = activeItems(items);
  const hasDefault = active.some((i) => i.default);
  container.innerHTML = active.map((item, index) => `
    <label class="choice-card">
      <input type="radio" name="${name}" value="${item.name}" data-price="${Number(item.price || 0)}" ${(item.default || (!hasDefault && index === 0)) ? 'checked' : ''}>
      <span>${item.label || item.name}${plusPrice(item.price)}</span>
    </label>
  `).join('');
}
function renderChecks(container, name, items, paid = false) {
  if (!container) return;
  container.innerHTML = activeItems(items).map((item) => `
    <label><input type="checkbox" name="${name}" value="${item.name}" data-price="${Number(item.price || 0)}" ${item.default ? 'checked' : ''}> ${item.label || item.name}${paid ? plusPrice(item.price) : ''}</label>
  `).join('');
}
function renderIngredients() {
  renderRadio(document.querySelector('[data-choice-group="size"]'), 'size', getAllowedItems('sizes'));
  renderRadio(document.querySelector('.order-form .choice-grid.two'), 'meat', getAllowedItems('meats'));
  const chips = document.querySelectorAll('.order-form .chips');
  renderChecks(chips[0], 'sauce', getAllowedItems('sauces'));
  renderChecks(chips[1], 'inside', getAllowedItems('inside'));
  renderChecks(document.querySelector('.order-form .extras-grid'), 'extra', getAllowedItems('extras'), true);
}
function renderMenu() {
  if (!menuGrid) return;
  const filtered = activeCategory === 'all' ? products : products.filter((p) => p.category === activeCategory);
  menuGrid.innerHTML = filtered.map((p) => `
    <article class="dish">
      <div class="dish-image photo-shell"><img src="${p.image || ''}" alt="${p.name}" data-fallback><div class="photo-fallback">🥙</div></div>
      <div class="dish-body">
        <div class="dish-topline"><span class="dish-badge">${p.badge || 'Menu'}</span><span class="dish-category">${categoryNames[p.category] || p.category}</span></div>
        <h3>${p.name}</h3><p>${p.description || ''}</p>
        <div class="dish-footer"><span class="dish-price">od ${formatPrice(p.price)}</span><button type="button" data-select-product="${p.id}">Personalizuj</button></div>
      </div>
    </article>
  `).join('');
  menuGrid.querySelectorAll('img[data-fallback]').forEach((image) => image.addEventListener('error', () => image.classList.add('image-failed')));
  menuGrid.querySelectorAll('[data-select-product]').forEach((button) => button.addEventListener('click', () => {
    productSelect.value = button.dataset.selectProduct;
    renderIngredients();
    updateSummary();
    document.querySelector('#personalizacja')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }));
}
if (categoryTabs) {
  categoryTabs.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => {
    activeCategory = button.dataset.category;
    categoryTabs.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
    renderMenu();
  }));
}

function getCheckedValues(name) { return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map((i) => i.value); }
function getCheckedPrice(name) { return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).reduce((sum, i) => sum + Number(i.dataset.price || 0), 0); }
function getRadioValue(name) { return document.querySelector(`input[name="${name}"]:checked`)?.value || ''; }
function getRadioPrice(name) { return Number(document.querySelector(`input[name="${name}"]:checked`)?.dataset.price || 0); }
function updateAddressVisibility() {
  const addressBlock = document.querySelector('#addressBlock');
  const customerAddress = document.querySelector('#customerAddress');
  if (!modeSelect || !addressBlock || !customerAddress) return;
  const isDelivery = modeSelect.value === 'Dostawa';
  addressBlock.hidden = !isDelivery;
  customerAddress.required = isDelivery;
}

function updateSummary() {
  if (!productSelect || !summaryTitle || !summaryDetails || !summaryPrice) return;
  updateAddressVisibility();
  const product = getSelectedProduct();
  const quantity = Math.max(1, Number(quantityInput?.value || 1));
  const size = getRadioValue('size');
  const meat = getRadioValue('meat');
  const sauces = getCheckedValues('sauce');
  const inside = getCheckedValues('inside');
  const extras = getCheckedValues('extra');
  const note = noteInput?.value.trim();
  const name = document.querySelector('#customerName')?.value.trim() || '';
  const phone = document.querySelector('#customerPhone')?.value.trim() || '';
  const address = document.querySelector('#customerAddress')?.value.trim() || '';
  const paymentMethod = getPaymentSelect()?.value || 'Płatność przy odbiorze';
  const modeOption = modeSelect?.selectedOptions?.[0];
  const mode = modeOption?.value || 'Odbiór własny';
  const singlePrice = Number(product.price || 0) + getRadioPrice('size') + getRadioPrice('meat') + getCheckedPrice('extra') + Number(modeOption?.dataset.price || 0);
  const total = Math.max(0, singlePrice * quantity);
  const sauceText = sauces.length ? sauces.join(', ') : 'bez sosu';
  const insideText = inside.length ? inside.join(', ') : 'bez dodatków';
  const extrasText = extras.length ? extras.join(', ') : 'brak';
  summaryTitle.textContent = `${quantity}x ${product.name}`;
  summaryDetails.innerHTML = `<strong>Klient:</strong> ${name || 'brak imienia'}${phone ? `, tel. ${phone}` : ''}<br><strong>Rozmiar:</strong> ${size || 'brak'}<br><strong>Baza:</strong> ${meat || 'brak'}<br><strong>Sos:</strong> ${sauceText}<br><strong>Dodatki:</strong> ${insideText}<br><strong>Dodatki płatne:</strong> ${extrasText}<br><strong>Odbiór:</strong> ${mode}${address ? `, ${address}` : ''}<br><strong>Płatność:</strong> ${paymentMethod}${note ? `<br><strong>Uwagi:</strong> ${note}` : ''}`;
  summaryPrice.textContent = formatPrice(total);
  lastOrderText = [`Klient: ${name || 'brak imienia'}`, `Telefon: ${phone || 'brak telefonu'}`, mode === 'Dostawa' ? `Adres: ${address || 'brak adresu'}` : null, `Płatność: ${paymentMethod}`, '', `Zamówienie: ${quantity}x ${product.name}`, `Rozmiar: ${size || 'brak'}`, `Baza: ${meat || 'brak'}`, `Sos: ${sauceText}`, `Dodatki: ${insideText}`, `Dodatki płatne: ${extrasText}`, `Odbiór: ${mode}`, note ? `Uwagi: ${note}` : null, '', `Razem: ${formatPrice(total)}`].filter((line) => line !== null).join('\n');
  lastPayload = { name, phone, address, mode, payment_method: paymentMethod, order: lastOrderText, total };
}
function validateOrder() {
  const phone = document.querySelector('#customerPhone')?.value.trim();
  const address = document.querySelector('#customerAddress')?.value.trim();
  if (!phone) { copyStatus.textContent = 'Podaj telefon klienta.'; document.querySelector('#customerPhone')?.focus(); return false; }
  if (modeSelect?.value === 'Dostawa' && !address) { copyStatus.textContent = 'Przy dostawie podaj adres.'; document.querySelector('#customerAddress')?.focus(); return false; }
  return true;
}
async function sendOrderToServer() {
  updateSummary();
  if (!validateOrder()) return;
  copyStatus.textContent = 'Wysyłam zamówienie...';
  try {
    const response = await fetch('send-order.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(lastPayload) });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Błąd wysyłki');
    if (data.payment_url) {
      copyStatus.innerHTML = `Zamówienie zapisane. Numer: ${data.order_id}. <a href="${data.payment_url}">Pokaż instrukcję BLIK</a>`;
    } else {
      copyStatus.textContent = data.mail_sent ? `Zamówienie wysłane. Numer: ${data.order_id}` : `Zamówienie zapisane, ale mail mógł nie wyjść. Numer: ${data.order_id}`;
    }
  } catch {
    copyStatus.textContent = 'Nie udało się wysłać. To działa dopiero na hostingu z PHP, nie na GitHub Pages.';
  }
}

if (orderForm) {
  orderForm.addEventListener('change', (event) => { if (event.target === productSelect) renderIngredients(); updateSummary(); });
  orderForm.addEventListener('input', updateSummary);
}
if (quantityInput) quantityInput.addEventListener('input', () => { if (Number(quantityInput.value) < 1) quantityInput.value = 1; updateSummary(); });
if (qtyMinus && quantityInput) qtyMinus.addEventListener('click', () => { quantityInput.value = Math.max(1, Number(quantityInput.value || 1) - 1); updateSummary(); });
if (qtyPlus && quantityInput) qtyPlus.addEventListener('click', () => { quantityInput.value = Math.min(20, Number(quantityInput.value || 1) + 1); updateSummary(); });
if (copyOrder) copyOrder.addEventListener('click', async () => { updateSummary(); try { await navigator.clipboard.writeText(lastOrderText); copyStatus.textContent = 'Skopiowano zamówienie.'; } catch { copyStatus.textContent = lastOrderText; } });
if (mailOrder) mailOrder.addEventListener('click', (event) => { event.preventDefault(); sendOrderToServer(); });

async function init() {
  injectCustomerFields();
  await loadMenuFromServer();
  renderProductOptions();
  renderIngredients();
  renderMenu();
  updateSummary();
}

init();
