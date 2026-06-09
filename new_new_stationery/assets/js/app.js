/* ── State ─────────────────────────────────────────────────────── */
const state = {
  items:       [],
  departments: [],
  cart:        {},          // { itemId: quantity }
  search:      '',
  category:    'All',
  currency:    document.documentElement.dataset.currency || '£',
};

/* ── Bootstrap ─────────────────────────────────────────────────── */
async function init() {
  const [items, depts] = await Promise.all([
    apiFetch('api/items.php'),
    apiFetch('api/departments.php'),
  ]);
  state.items       = items || [];
  state.departments = depts || [];

  buildCategoryChips();
  renderItems();
  renderDepartments();
  renderCart();

  document.getElementById('search-input').addEventListener('input', e => {
    state.search = e.target.value.toLowerCase();
    renderItems();
  });
}

/* ── API helper ────────────────────────────────────────────────── */
async function apiFetch(url, opts = {}) {
  try {
    const res  = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...opts });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) { toast(data.error || 'Request failed', 'error'); return null; }
    return data;
  } catch {
    toast('Network error. Please refresh.', 'error');
    return null;
  }
}

/* ── Category chips ────────────────────────────────────────────── */
function buildCategoryChips() {
  const cats = ['All', ...new Set(state.items.map(i => i.category).filter(Boolean).sort())];
  const el   = document.getElementById('category-chips');
  el.innerHTML = cats.map(c =>
    `<button class="chip${c === 'All' ? ' active' : ''}" data-cat="${esc(c)}">${esc(c)}</button>`
  ).join('');

  el.addEventListener('click', e => {
    const btn = e.target.closest('.chip');
    if (!btn) return;
    state.category = btn.dataset.cat;
    el.querySelectorAll('.chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderItems();
  });
}

/* ── Items list (vertical) ─────────────────────────────────────── */
function renderItems() {
  const q        = state.search.trim();
  const cat      = state.category;
  const filtered = state.items.filter(item => {
    const matchCat  = cat === 'All' || item.category === cat;
    const matchText = !q
      || item.name.toLowerCase().includes(q)
      || (item.description || '').toLowerCase().includes(q)
      || (item.category || '').toLowerCase().includes(q);
    return matchCat && matchText;
  });

  const list = document.getElementById('items-list');
  if (filtered.length === 0) {
    list.innerHTML = '<p class="no-results">No items found.</p>';
    return;
  }

  list.innerHTML = filtered.map(item => {
    const qty    = state.cart[item.id] || 0;
    const inCart = qty > 0;
    return `
      <div class="item-row${inCart ? ' in-cart' : ''}" data-id="${esc(item.id)}">
        <div class="item-row-info">
          <div class="item-name">${esc(item.name)}</div>
          <div class="item-desc">${esc(item.description || '')}</div>
        </div>
        <div class="item-row-price">
          <span class="item-price">${state.currency}${fmtPrice(item.price)}</span>
          <span class="item-unit">/ ${esc(item.unit)}</span>
        </div>
        <div class="qty-control">
          <button class="qty-btn" data-action="dec" data-id="${esc(item.id)}" ${qty === 0 ? 'disabled' : ''}>−</button>
          <input class="qty-input" type="number" min="0" max="999" value="${qty}" data-id="${esc(item.id)}">
          <button class="qty-btn" data-action="inc" data-id="${esc(item.id)}">+</button>
        </div>
      </div>`;
  }).join('');
}

document.getElementById('items-list').addEventListener('click', e => {
  const btn = e.target.closest('.qty-btn');
  if (!btn) return;
  const id  = btn.dataset.id;
  const cur = state.cart[id] || 0;
  setQty(id, btn.dataset.action === 'inc' ? cur + 1 : Math.max(0, cur - 1));
});

document.getElementById('items-list').addEventListener('change', e => {
  if (!e.target.classList.contains('qty-input')) return;
  const id  = e.target.dataset.id;
  setQty(id, Math.max(0, Math.min(999, parseInt(e.target.value, 10) || 0)));
});

function setQty(id, qty) {
  if (qty === 0) delete state.cart[id];
  else state.cart[id] = qty;
  renderItems();
  renderCart();
}

/* ── Cart ──────────────────────────────────────────────────────── */
function renderCart() {
  const entries = Object.entries(state.cart).filter(([, q]) => q > 0);
  const badge   = document.getElementById('cart-badge');
  const count   = entries.reduce((s, [, q]) => s + q, 0);
  badge.textContent = count;
  badge.style.display = count > 0 ? '' : 'none';

  const itemsEl = document.getElementById('cart-items');
  if (entries.length === 0) {
    itemsEl.innerHTML = '<p class="cart-empty">Your cart is empty.<br>Add items from the list.</p>';
    document.getElementById('cart-total-val').textContent = state.currency + '0.00';
    document.getElementById('btn-order').disabled = true;
    return;
  }

  let total = 0;
  itemsEl.innerHTML = entries.map(([id, qty]) => {
    const item = state.items.find(i => i.id === id);
    if (!item) return '';
    const subtotal = item.price * qty;
    total += subtotal;
    return `
      <div class="cart-row">
        <span class="cart-row-name">${esc(item.name)}</span>
        <div class="cart-row-qty-ctrl">
          <button class="qty-btn qty-btn-sm" data-cart-action="dec" data-id="${esc(id)}" ${qty === 1 ? 'disabled' : ''}>−</button>
          <input class="qty-input qty-input-sm" type="number" min="1" max="999" value="${qty}" data-cart-id="${esc(id)}">
          <button class="qty-btn qty-btn-sm" data-cart-action="inc" data-id="${esc(id)}">+</button>
        </div>
        <span class="cart-row-price">${state.currency}${fmtPrice(subtotal)}</span>
        <button class="cart-row-del" data-id="${esc(id)}" title="Remove item">&times;</button>
      </div>`;
  }).join('');

  document.getElementById('cart-total-val').textContent = state.currency + fmtPrice(total);
  document.getElementById('btn-order').disabled = false;
}

// Cart interactions — all delegated from the cart-items container
document.getElementById('cart-items').addEventListener('click', e => {
  const del = e.target.closest('.cart-row-del');
  if (del) {
    delete state.cart[del.dataset.id];
    renderItems();
    renderCart();
    return;
  }
  const btn = e.target.closest('[data-cart-action]');
  if (btn) {
    const id  = btn.dataset.id;
    const cur = state.cart[id] || 1;
    setQty(id, btn.dataset.cartAction === 'inc' ? cur + 1 : Math.max(1, cur - 1));
  }
});

document.getElementById('cart-items').addEventListener('change', e => {
  if (!e.target.dataset.cartId) return;
  const id  = e.target.dataset.cartId;
  const qty = Math.max(1, Math.min(999, parseInt(e.target.value, 10) || 1));
  setQty(id, qty);
});

/* ── Departments dropdown ──────────────────────────────────────── */
function renderDepartments() {
  const sel = document.getElementById('dept-select');
  sel.innerHTML = '<option value="">— Select department —</option>'
    + state.departments.map(d =>
        `<option value="${esc(d.id)}">${esc(d.name)}</option>`
      ).join('');
}

/* ── Place order ───────────────────────────────────────────────── */
document.getElementById('btn-order').addEventListener('click', async () => {
  const deptId = document.getElementById('dept-select').value;
  const notes  = document.getElementById('cart-notes').value;

  if (!deptId) { toast('Please select a department.', 'error'); return; }
  if (Object.keys(state.cart).length === 0) { toast('Your cart is empty.', 'error'); return; }

  const btn = document.getElementById('btn-order');
  btn.disabled = true;
  btn.textContent = 'Sending…';

  const cart = Object.entries(state.cart).map(([id, qty]) => ({ id, quantity: qty }));
  const result = await apiFetch('api/order.php', {
    method: 'POST',
    body:   JSON.stringify({ cart, department_id: deptId, notes }),
  });

  btn.textContent = 'Place Order';

  if (result && result.success) {
    toast(result.message || 'Order sent successfully!', 'success');
    state.cart = {};
    document.getElementById('dept-select').value = '';
    document.getElementById('cart-notes').value  = '';
    renderItems();
    renderCart();
  } else {
    btn.disabled = false;
  }
});

/* ── Utilities ─────────────────────────────────────────────────── */
function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
function fmtPrice(n) { return Number(n).toFixed(2); }

function toast(msg, type = 'success') {
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.textContent = msg;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

init();
