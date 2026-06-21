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

const products = [
  {
    id: 'lawasz-kurczak',
    name: 'Lawasz Kurczak',
    category: 'lawasz',
    badge: 'Bestseller',
    price: 24,
    description: 'Kurczak, świeże warzywa i wybrany sos w lawaszu.',
    image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'lawasz-mieszany',
    name: 'Lawasz Mieszany',
    category: 'lawasz',
    badge: 'Najczęściej wybierane',
    price: 29,
    description: 'Kurczak + wołowina-baranina, warzywa i sos mieszany.',
    image: 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'box-frytki',
    name: 'Kebab Box z frytkami',
    category: 'box',
    badge: 'Na szybko',
    price: 26,
    description: 'Mięso, frytki, surówka i sos. Dobry na lunch.',
    image: 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'talerz',
    name: 'Kebab na talerzu',
    category: 'talerz',
    badge: 'Duża porcja',
    price: 37,
    description: 'Mięso, frytki albo ryż, surówka i sos.',
    image: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'adana',
    name: 'Adana Grill',
    category: 'grill',
    badge: 'Ostry hit',
    price: 44,
    description: 'Ostry szaszłyk wołowy, lawasz, dodatki i sos.',
    image: 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'falafel',
    name: 'Falafel Rolada',
    category: 'vege',
    badge: 'Vege',
    price: 22,
    description: 'Falafel, warzywa, hummus albo sos czosnkowy.',
    image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'zestaw',
    name: 'Lawasz Zestaw',
    category: 'lawasz',
    badge: 'Zestaw',
    price: 32.5,
    description: 'Lawasz, frytki i napój 0,5 l.',
    image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'frytki',
    name: 'Frytki',
    category: 'dodatki',
    badge: 'Dodatek',
    price: 9,
    description: 'Chrupiące frytki z przyprawą.',
    image: 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=85'
  },
  {
    id: 'baklava',
    name: 'Baklawa',
    category: 'dodatki',
    badge: 'Deser',
    price: 17,
    description: 'Słodki deser do zestawu.',
    image: 'https://images.unsplash.com/photo-1605190557072-5b43a4a916b6?auto=format&fit=crop&w=900&q=85'
  }
];

const categoryNames = {
  lawasz: 'Lawasz',
  box: 'Box',
  talerz: 'Talerz',
  grill: 'Grill',
  vege: 'Vege',
  dodatki: 'Dodatki'
};

let activeCategory = 'all';
let lastOrderText = '';

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

document.querySelectorAll('img[data-fallback]').forEach((image) => {
  image.addEventListener('error', () => image.classList.add('image-failed'));
});

function formatPrice(value) {
  return new Intl.NumberFormat('pl-PL', {
    style: 'currency',
    currency: 'PLN'
  }).format(value);
}

function getProductById(id) {
  return products.find((product) => product.id === id) || products[0];
}

function renderProductOptions() {
  if (!productSelect) return;

  productSelect.innerHTML = products
    .map((product) => `<option value="${product.id}">${product.name} — od ${formatPrice(product.price)}</option>`)
    .join('');
}

function renderMenu() {
  if (!menuGrid) return;

  const filtered = activeCategory === 'all'
    ? products
    : products.filter((product) => product.category === activeCategory);

  menuGrid.innerHTML = filtered.map((product) => `
    <article class="dish">
      <div class="dish-image photo-shell">
        <img src="${product.image}" alt="${product.name}" data-fallback>
        <div class="photo-fallback">🥙</div>
      </div>
      <div class="dish-body">
        <div class="dish-topline">
          <span class="dish-badge">${product.badge}</span>
          <span class="dish-category">${categoryNames[product.category]}</span>
        </div>
        <h3>${product.name}</h3>
        <p>${product.description}</p>
        <div class="dish-footer">
          <span class="dish-price">od ${formatPrice(product.price)}</span>
          <button type="button" data-select-product="${product.id}">Personalizuj</button>
        </div>
      </div>
    </article>
  `).join('');

  menuGrid.querySelectorAll('img[data-fallback]').forEach((image) => {
    image.addEventListener('error', () => image.classList.add('image-failed'));
  });

  menuGrid.querySelectorAll('[data-select-product]').forEach((button) => {
    button.addEventListener('click', () => {
      const selectedId = button.dataset.selectProduct;
      productSelect.value = selectedId;
      updateSummary();
      document.querySelector('#personalizacja')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

if (categoryTabs) {
  categoryTabs.querySelectorAll('button').forEach((button) => {
    button.addEventListener('click', () => {
      activeCategory = button.dataset.category;

      categoryTabs.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');

      renderMenu();
    });
  });
}

function getCheckedValues(name) {
  return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map((input) => input.value);
}

function getCheckedPrice(name) {
  return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).reduce((sum, input) => {
    return sum + Number(input.dataset.price || 0);
  }, 0);
}

function getRadioValue(name) {
  return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
}

function getRadioPrice(name) {
  const input = document.querySelector(`input[name="${name}"]:checked`);
  return Number(input?.dataset.price || 0);
}

function updateSummary() {
  if (!productSelect || !summaryTitle || !summaryDetails || !summaryPrice) return;

  const product = getProductById(productSelect.value);
  const quantity = Math.max(1, Number(quantityInput?.value || 1));
  const size = getRadioValue('size');
  const meat = getRadioValue('meat');
  const sauces = getCheckedValues('sauce');
  const inside = getCheckedValues('inside');
  const extras = getCheckedValues('extra');
  const note = noteInput?.value.trim();
  const modeOption = modeSelect?.selectedOptions?.[0];
  const mode = modeOption?.value || 'Odbiór własny';

  const extrasPrice = getCheckedPrice('extra');
  const singlePrice = product.price + getRadioPrice('size') + getRadioPrice('meat') + extrasPrice + Number(modeOption?.dataset.price || 0);
  const total = Math.max(0, singlePrice * quantity);

  const sizeLabel = {
    standard: 'standard',
    mega: 'mega',
    kids: 'mini'
  }[size] || size;

  const sauceText = sauces.length ? sauces.join(', ') : 'bez sosu';
  const insideText = inside.length ? inside.join(', ') : 'bez dodatków';
  const extrasText = extras.length ? extras.join(', ') : 'brak';

  summaryTitle.textContent = `${quantity}x ${product.name}`;
  summaryDetails.innerHTML = `
    <strong>Rozmiar:</strong> ${sizeLabel}<br>
    <strong>Baza:</strong> ${meat}<br>
    <strong>Sos:</strong> ${sauceText}<br>
    <strong>Dodatki:</strong> ${insideText}<br>
    <strong>Dodatki płatne:</strong> ${extrasText}<br>
    <strong>Odbiór:</strong> ${mode}${note ? `<br><strong>Uwagi:</strong> ${note}` : ''}
  `;
  summaryPrice.textContent = formatPrice(total);

  lastOrderText = [
    `Zamówienie: ${quantity}x ${product.name}`,
    `Rozmiar: ${sizeLabel}`,
    `Baza: ${meat}`,
    `Sos: ${sauceText}`,
    `Dodatki: ${insideText}`,
    `Dodatki płatne: ${extrasText}`,
    `Odbiór: ${mode}`,
    note ? `Uwagi: ${note}` : null,
    `Razem: ${formatPrice(total)}`
  ].filter(Boolean).join('\n');

  if (mailOrder) {
    const subject = encodeURIComponent('Zamówienie KebabKing');
    const body = encodeURIComponent(lastOrderText);
    mailOrder.href = `mailto:kontakt@example.pl?subject=${subject}&body=${body}`;
  }
}

if (orderForm) {
  orderForm.addEventListener('change', updateSummary);
  orderForm.addEventListener('input', updateSummary);
}

if (quantityInput) {
  quantityInput.addEventListener('input', () => {
    if (Number(quantityInput.value) < 1) quantityInput.value = 1;
    updateSummary();
  });
}

if (qtyMinus && quantityInput) {
  qtyMinus.addEventListener('click', () => {
    quantityInput.value = Math.max(1, Number(quantityInput.value || 1) - 1);
    updateSummary();
  });
}

if (qtyPlus && quantityInput) {
  qtyPlus.addEventListener('click', () => {
    quantityInput.value = Math.min(20, Number(quantityInput.value || 1) + 1);
    updateSummary();
  });
}

if (copyOrder) {
  copyOrder.addEventListener('click', async () => {
    if (!lastOrderText) updateSummary();

    try {
      await navigator.clipboard.writeText(lastOrderText);
      copyStatus.textContent = 'Skopiowano zamówienie. Możesz wkleić je do SMS, Messengera albo maila.';
    } catch {
      copyStatus.textContent = lastOrderText;
    }
  });
}

renderProductOptions();
renderMenu();
updateSummary();
