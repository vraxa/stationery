/* ── Shared ─────────────────────────────────────────────────────── */
const currency = document.documentElement.dataset.currency || '£';

async function apiFetch(url, opts = {}) {
  try {
    const res  = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...opts });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) { toast(data.error || 'Request failed', 'error'); return null; }
    return data;
  } catch {
    toast('Network error.', 'error');
    return null;
  }
}

function toast(msg, type = 'success') {
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.textContent = msg;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function confirm(msg) {
  return window.confirm(msg);
}

/* ── Tabs ───────────────────────────────────────────────────────── */
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});

/* ══════════════════════════════════════════════════════════════════
   ITEMS
══════════════════════════════════════════════════════════════════ */
let items = [];

async function loadItems() {
  const data = await apiFetch('api/items.php');
  if (!data) return;
  items = data;
  renderItemsTable();
}

function renderItemsTable() {
  const tbody = document.getElementById('items-tbody');
  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-results">No items yet.</td></tr>';
    return;
  }
  tbody.innerHTML = items.map(item => `
    <tr data-id="${esc(item.id)}">
      <td style="color:#605E5C;font-size:12px;white-space:nowrap;">${esc(item.sku || '')}</td>
      <td>${esc(item.name)}</td>
      <td>${esc(item.category)}</td>
      <td>${esc(item.unit)}</td>
      <td>${currency}${Number(item.price).toFixed(2)}</td>
      <td style="max-width:260px;color:#605E5C;font-size:12px;">${esc(item.description)}</td>
      <td>
        <div class="actions-cell">
          <button class="btn btn-outline btn-sm" onclick="openItemModal('${esc(item.id)}')">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteItem('${esc(item.id)}')">Delete</button>
        </div>
      </td>
    </tr>`).join('');

  // Keep the category datalist in sync
  const dl = document.getElementById('category-list');
  if (dl) {
    const cats = [...new Set(items.map(i => i.category).filter(Boolean).sort())];
    dl.innerHTML = cats.map(c => `<option value="${c.replace(/"/g, '&quot;')}">`).join('');
  }
}

/* Item modal */
function openItemModal(id) {
  const item = id ? items.find(i => i.id === id) : null;
  document.getElementById('item-modal-title').textContent = item ? 'Edit Item' : 'Add New Item';
  document.getElementById('item-id').value          = item ? item.id          : '';
  document.getElementById('item-sku').value         = item ? (item.sku || '') : '';
  document.getElementById('item-name').value        = item ? item.name        : '';
  document.getElementById('item-category').value    = item ? item.category    : '';
  document.getElementById('item-unit').value        = item ? item.unit        : '';
  document.getElementById('item-price').value       = item ? item.price       : '';
  document.getElementById('item-description').value = item ? item.description : '';
  document.getElementById('item-modal').classList.add('open');
}

function closeItemModal() {
  document.getElementById('item-modal').classList.remove('open');
}

document.getElementById('item-form').addEventListener('submit', async e => {
  e.preventDefault();
  const id = document.getElementById('item-id').value;
  const payload = {
    id:          id,
    sku:         document.getElementById('item-sku').value.trim(),
    name:        document.getElementById('item-name').value.trim(),
    category:    document.getElementById('item-category').value.trim(),
    unit:        document.getElementById('item-unit').value.trim(),
    price:       parseFloat(document.getElementById('item-price').value),
    description: document.getElementById('item-description').value.trim(),
  };

  const method = id ? 'PUT' : 'POST';
  const result = await apiFetch('api/items.php', { method, body: JSON.stringify(payload) });
  if (!result) return;

  toast(id ? 'Item updated.' : 'Item added.', 'success');
  closeItemModal();
  loadItems();
});

async function deleteItem(id) {
  if (!confirm('Delete this item? This cannot be undone.')) return;
  const result = await apiFetch('api/items.php', {
    method: 'DELETE',
    body:   JSON.stringify({ id }),
  });
  if (!result) return;
  toast('Item deleted.', 'success');
  loadItems();
}

/* Datalist for category autocomplete */
async function buildCategoryList() {
  // Populated after items load
}

/* ══════════════════════════════════════════════════════════════════
   DEPARTMENTS
══════════════════════════════════════════════════════════════════ */
let departments = [];

async function loadDepts() {
  const data = await apiFetch('api/departments.php');
  if (!data) return;
  departments = data;
  renderDeptsTable();
}

function renderDeptsTable() {
  const tbody = document.getElementById('depts-tbody');
  if (departments.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="no-results">No departments yet.</td></tr>';
    return;
  }
  tbody.innerHTML = departments.map(d => `
    <tr data-id="${esc(d.id)}">
      <td>${esc(d.name)}</td>
      <td>${esc(d.head_name)}</td>
      <td><a href="mailto:${esc(d.email)}">${esc(d.email)}</a></td>
      <td>
        <div class="actions-cell">
          <button class="btn btn-outline btn-sm" onclick="openDeptModal('${esc(d.id)}')">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteDept('${esc(d.id)}')">Delete</button>
        </div>
      </td>
    </tr>`).join('');
}

function openDeptModal(id) {
  const dept = id ? departments.find(d => d.id === id) : null;
  document.getElementById('dept-modal-title').textContent = dept ? 'Edit Department' : 'Add New Department';
  document.getElementById('dept-id').value        = dept ? dept.id        : '';
  document.getElementById('dept-name').value      = dept ? dept.name      : '';
  document.getElementById('dept-head').value      = dept ? dept.head_name : '';
  document.getElementById('dept-email').value     = dept ? dept.email     : '';
  document.getElementById('dept-modal').classList.add('open');
}

function closeDeptModal() {
  document.getElementById('dept-modal').classList.remove('open');
}

document.getElementById('dept-form').addEventListener('submit', async e => {
  e.preventDefault();
  const id = document.getElementById('dept-id').value;
  const payload = {
    id:        id,
    name:      document.getElementById('dept-name').value.trim(),
    head_name: document.getElementById('dept-head').value.trim(),
    email:     document.getElementById('dept-email').value.trim(),
  };

  const method = id ? 'PUT' : 'POST';
  const result = await apiFetch('api/departments.php', { method, body: JSON.stringify(payload) });
  if (!result) return;

  toast(id ? 'Department updated.' : 'Department added.', 'success');
  closeDeptModal();
  loadDepts();
});

async function deleteDept(id) {
  if (!confirm('Delete this department? This cannot be undone.')) return;
  const result = await apiFetch('api/departments.php', {
    method: 'DELETE',
    body:   JSON.stringify({ id }),
  });
  if (!result) return;
  toast('Department deleted.', 'success');
  loadDepts();
}

/* Close modals on backdrop click */
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
  backdrop.addEventListener('click', e => {
    if (e.target === backdrop) {
      backdrop.classList.remove('open');
    }
  });
});

/* Escape key closes modals */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
  }
});

/* ── Init ──────────────────────────────────────────────────────── */
loadItems();
loadDepts();
