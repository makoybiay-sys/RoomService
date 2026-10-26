/* ============================================================
   ADMIN PANEL JAVASCRIPT (Laravel Version)
   ============================================================ */

   let allStaffData = []; 
   let lastOrderHash = ''; // Para sa auto-refresh
   let lastRoomHash = '';
   const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
   
   document.addEventListener('DOMContentLoaded', () => {
       // --- SIDEBAR LOGIC ---
       const adminBurger = document.getElementById('adminBurger');
       const adminSidebar = document.getElementById('adminSidebar');
       const adminBackdrop = document.getElementById('adminBackdrop');
       const adminSidebarClose = document.getElementById('adminSidebarClose');
   
       if (adminBurger) {
           adminBurger.addEventListener('click', () => {
               adminSidebar.classList.toggle('open');
               adminBackdrop.classList.toggle('show');
           });
           adminBackdrop.addEventListener('click', () => {
               adminSidebar.classList.remove('open');
               adminBackdrop.classList.remove('show');
           });
           adminSidebarClose.addEventListener('click', () => {
               adminSidebar.classList.remove('open');
               adminBackdrop.classList.remove('show');
           });
       }
   
       // --- TABS LOGIC ---
       document.querySelectorAll('.admin-nav-item[data-tab]').forEach(btn => {
           btn.onclick = () => {
               document.querySelectorAll('.admin-nav-item').forEach(b => b.classList.remove('active'));
               btn.classList.add('active');
               const tab = btn.dataset.tab;
               document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
               document.getElementById('tab-' + tab).classList.add('active');
   
               if (tab === 'orders') loadOrders();
               if (tab === 'rooms') loadRooms();
               if (tab === 'delivered') loadDelivered();
               if (tab === 'staff') loadStaff();
               if (tab === 'theme') loadThemeIntoEditor();
   
               if (window.innerWidth < 900) { 
                   adminSidebar.classList.remove('open'); 
                   adminBackdrop.classList.remove('show'); 
               }
           };
       });
   
       // Initial Load
       loadMenu();
   
       // --- AUTO-REFRESH PARA SA ORDERS TAB (Every 5 seconds) ---
       setInterval(() => {
           const activeTab = document.querySelector('.admin-tab-content.active');
           if (activeTab) {
               if (activeTab.id === 'tab-orders') loadOrders();
               if (activeTab.id === 'tab-rooms') loadRooms();
           }
       }, 5000);
   });
   
   /* ============================================================
      MENU CRUD
      ============================================================ */
   async function loadMenu() {
       try {
           const res = await fetch('/api/menu');
           const items = await res.json();
           const rows = document.getElementById('menuRows');
   
           if (items.length === 0) {
               rows.innerHTML = '<tr><td colspan="8" class="empty-state">No items yet.</td></tr>';
               return;
           }
   
           rows.innerHTML = items.map(i => {
               const img = i.image_path
                   ? `<img src="/${i.image_path}" class="thumb-img" alt="${i.name}">`
                   : `<div class="no-thumb">No Image</div>`;
               const status = i.is_available == 1 && i.remaining_stock > 0
                   ? '<span class="status-badge" style="background:#10b981;">Available</span>'
                   : '<span class="status-badge" style="background:#ef4444;">Unavailable</span>';
               return `
                   <tr>
                       <td>${img}</td>
                       <td><strong>${i.name}</strong><br><small style="color:var(--muted);">${i.description || ''}</small></td>
                       <td>${i.category}</td>
                       <td>PHP ${parseFloat(i.price).toFixed(2)}</td>
                       <td>${i.daily_stock}</td>
                       <td>${i.remaining_stock}</td>
                       <td>${status}</td>
                       <td>
                           <div class="table-actions">
                               <button class="btn-icon-btn btn-icon-primary" onclick='openEditModal(${JSON.stringify(i).replace(/'/g, "&#39;")})'>Edit</button>
                               <button class="btn-icon-btn btn-icon-danger" onclick="deleteItem(${i.id})">Delete</button>
                           </div>
                       </td>
                   </tr>`;
           }).join('');
       } catch (e) { console.error(e); }
   }
   
   function openCreateModal() {
       document.getElementById('modalTitle').textContent = 'Add New Item';
       document.getElementById('formAction').value = 'create';
       document.getElementById('formId').value = '';
       document.getElementById('menuForm').reset();
       document.getElementById('imagePreview').style.display = 'none';
       document.getElementById('modalOverlay').classList.add('active');
   }
   
   function openEditModal(item) {
       document.getElementById('modalTitle').textContent = 'Edit Item';
       document.getElementById('formAction').value = 'update';
       document.getElementById('formId').value = item.id;
       document.getElementById('fName').value = item.name;
       document.getElementById('fDescription').value = item.description || '';
       document.getElementById('fCategory').value = item.category;
       document.getElementById('fPrice').value = item.price;
       document.getElementById('fStock').value = item.daily_stock;
   
       const prev = document.getElementById('imagePreview');
       if (item.image_path) {
           prev.src = '/' + item.image_path;
           prev.style.display = 'block';
       } else {
           prev.style.display = 'none';
       }
       document.getElementById('modalOverlay').classList.add('active');
   }
   
   function closeModal() { document.getElementById('modalOverlay').classList.remove('active'); }
   
   function previewImage(e) {
       const file = e.target.files[0];
       if (!file) return;
       const reader = new FileReader();
       reader.onload = ev => {
           const prev = document.getElementById('imagePreview');
           prev.src = ev.target.result;
           prev.style.display = 'block';
       };
       reader.readAsDataURL(file);
   }
   
   document.getElementById('menuForm')?.addEventListener('submit', async (e) => {
       e.preventDefault();
       const form = new FormData(e.target);
       const action = document.getElementById('formAction').value;
       const id = document.getElementById('formId').value;
       
       let url = '/api/menu';
       if (action === 'update') url += `/${id}`;
   
       try {
           const res = await fetch(url, { 
               method: 'POST', 
               headers: { 'X-CSRF-TOKEN': csrfToken }, 
               body: form 
           });
           const data = await res.json();
           
           if (data.success) {
               showToast('Item saved successfully', 'success');
               closeModal();
               loadMenu();
           } else {
               showToast(data.message || 'Error saving item', 'error');
           }
       } catch (err) { showToast('Server error', 'error'); }
   });
   
   async function deleteItem(id) {
       if (!confirm('Delete this item permanently?')) return;
       try {
           const res = await fetch(`/api/menu/${id}`, { 
               method: 'DELETE',
               headers: { 'X-CSRF-TOKEN': csrfToken }
           });
           const data = await res.json();
           if (data.success) {
               showToast('Item deleted', 'success');
               loadMenu();
           }
       } catch (err) { showToast('Server error', 'error'); }
   }
   
   async function resetAllStock() {
       if (!confirm('Reset all remaining stock to daily stock?')) return;
       try {
           const res = await fetch('/api/menu/reset-stock', { 
               method: 'POST',
               headers: { 'X-CSRF-TOKEN': csrfToken } 
           });
           const data = await res.json();
           if (data.success) {
               showToast('Daily stock reset', 'success');
               loadMenu();
           }
       } catch (err) { showToast('Server error', 'error'); }
   }
   
   /* ============================================================
      ORDERS (May Auto-Refresh at Hash Comparison)
      ============================================================ */
   async function loadOrders() {
       const status = document.getElementById('orderFilterStatus').value;
       const floor  = document.getElementById('orderFilterFloor').value;
       let url = '/api/orders?';
       if (status) url += 'status=' + encodeURIComponent(status) + '&';
       if (floor)  url += 'floor=' + floor;
   
       const res = await fetch(url);
       const orders = await res.json();
       
       // HASH COMPARISON: Wag i-render kung walang nagbago
       const currentHash = orders.map(o => `${o.id}-${o.status}`).join('|');
       if (currentHash === lastOrderHash) return;
       lastOrderHash = currentHash;
   
       const rows = document.getElementById('ordersRows');
   
       if (orders.length === 0) {
           rows.innerHTML = '<tr><td colspan="9" class="empty-state">No orders found.</td></tr>';
           return;
       }
   
       const colorMap = {
           PENDING: '#f59e0b', PREPARING: '#3b82f6', READY: '#10b981',
           DELIVERING: '#8b5cf6', DELIVERED: '#06b6d4', CHARGED: '#6b7280', CANCELLED: '#ef4444', VOIDED: '#000'
       };
   
       rows.innerHTML = orders.map(o => {
           const items = o.items.map(i => `${i.item_name} x${i.qty}`).join(', ');
           const waiter = o.waiter ? `${o.waiter.full_name}` : '—';
           let statusLabel = o.status === 'CHARGED' ? 'COMPLETED' : o.status;
   
           return `
               <tr>
                   <td><strong>${o.order_code}</strong></td>
                   <td>F${o.floor_number}</td>
                   <td>${o.room_number}</td>
                   <td>${o.guest_name || '—'}</td>
                   <td><small>${items}</small></td>
                   <td><small>${waiter}</small></td>
                   <td>PHP ${parseFloat(o.total_amount).toFixed(2)}</td>
                   <td><span class="status-badge" style="background:${colorMap[o.status] || '#999'};">${statusLabel}</span></td>
                   <td><small>${new Date(o.updated_at).toLocaleString()}</small></td>
               </tr>`;
       }).join('');
   }
   
   /* ============================================================
      ROOMS (Inayos ang rendering para hindi mawala ang data)
      ============================================================ */
   async function loadRooms() {
       try {
           const res = await fetch('/api/orders');
           const orders = await res.json();
           
           // HASH COMPARISON
           const currentHash = orders.map(o => `${o.id}-${o.status}`).join('|');
           if (currentHash === lastRoomHash) return;
           lastRoomHash = currentHash;
   
           const container = document.getElementById('roomsContainer');
           const floors = {};
           for (let f = 5; f <= 16; f++) floors[f] = [];
   
           orders.forEach(o => {
               const f = parseInt(o.floor_number) || 1;
               if (!floors[f]) floors[f] = [];
               floors[f].push(o);
           });
   
           let html = '';
           for (let f = 5; f <= 16; f++) {
               const list = floors[f];
               if (list.length === 0) continue;
   
               html += `
                   <div class="admin-card floor-card">
                       <div class="floor-header">
                           <h3>Floor ${f}</h3>
                           <span class="floor-count">${list.length} order(s)</span>
                       </div>
                       <div class="admin-table-scroll">
                           <table class="data-table">
                               <thead><tr><th>Room</th><th>Order</th><th>Status</th><th>Total</th></tr></thead>
                               <tbody>`;
   
               list.forEach(o => {
                   let statusLabel = o.status === 'CHARGED' ? 'COMPLETED' : o.status;
                   html += `
                       <tr>
                           <td>Room ${o.room_number}</td>
                           <td>${o.order_code}</td>
                           <td>${statusLabel}</td>
                           <td>PHP ${parseFloat(o.total_amount).toFixed(2)}</td>
                       </tr>`;
               });
   
               html += `</tbody></table></div></div>`;
           }
   
           if (html === '') html = '<div class="empty-state" style="padding: 40px; text-align: center;">No orders yet.</div>';
           container.innerHTML = html;
       } catch (e) { 
           console.error(e);
           document.getElementById('roomsContainer').innerHTML = '<div class="empty-state">Failed to load rooms.</div>';
       }
   }
   
   /* ============================================================
      DELIVERED
      ============================================================ */
   async function loadDelivered() {
       const res = await fetch('/api/orders?status=DELIVERED,CHARGED');
       const orders = await res.json();
       const rows = document.getElementById('deliveredRows');
   
       if (orders.length === 0) {
           rows.innerHTML = '<tr><td colspan="7" class="empty-state">No completed orders yet.</td></tr>';
           return;
       }
   
       rows.innerHTML = orders.map(o => {
           const waiter = o.waiter ? o.waiter.full_name : '—';
           return `
               <tr>
                   <td><strong>${o.order_code}</strong></td>
                   <td>F${o.floor_number}</td>
                   <td>${o.room_number}</td>
                   <td>${o.guest_name || '—'}</td>
                   <td><small>${waiter}</small></td>
                   <td>PHP ${parseFloat(o.total_amount).toFixed(2)}</td>
                   <td><small>${new Date(o.updated_at).toLocaleString()}</small></td>
               </tr>`;
       }).join('');
   }
   
   /* ============================================================
      STAFF
      ============================================================ */
   async function loadStaff() {
       try {
           const res = await fetch('/api/staff');
           const staff = await res.json();
           allStaffData = staff;
           const rows = document.getElementById('staffRows');
   
           if (staff.length === 0) {
               rows.innerHTML = '<tr><td colspan="7" class="empty-state">No staff accounts yet.</td></tr>';
               return;
           }
   
           const roleColor = { ADMIN: '#8b5cf6', FNB_MANAGER: '#f59e0b', FRONT_DESK: '#3b82f6', COFFEE_MANAGER: '#92400e', KITCHEN: '#0ea5e9', WAITER: '#10b981' };
   
           rows.innerHTML = staff.map(s => `
               <tr>
                   <td><strong>${s.employee_id}</strong></td>
                   <td>${s.full_name}</td>
                   <td>${s.username}</td>
                   <td><span class="status-badge" style="background:${roleColor[s.role] || '#999'};">${s.role}</span></td>
                   <td><small>${s.last_login ? new Date(s.last_login).toLocaleString() : 'Never'}</small></td>
                   <td>${s.is_active == 1 ? '<span class="status-badge" style="background:#10b981;">Active</span>' : '<span class="status-badge" style="background:#ef4444;">Inactive</span>'}</td>
                   <td>
                       <div class="table-actions">
                           <button class="btn-icon-btn btn-icon-primary" onclick='openEditStaffModal(${s.id})'>Edit</button>
                           <button class="btn-icon-btn btn-icon-danger" onclick="deleteStaff(${s.id})">Delete</button>
                       </div>
                   </td>
               </tr>
           `).join('');
       } catch (e) { console.error(e); }
   }
   
   function openStaffModal() {
       document.getElementById('staffModalTitle').textContent = 'Add Staff Account';
       document.getElementById('staffAction').value = 'create';
       document.getElementById('staffId').value = '';
       document.getElementById('staffForm').reset();
       document.getElementById('pwdHint').textContent = '*';
       document.getElementById('sPassword').required = true;
       document.getElementById('sRole').value = 'KITCHEN';
       document.getElementById('staffModalOverlay').classList.add('active');
       fetchNextEmployeeId('KITCHEN');
   }
   
   function openEditStaffModal(id) {
       const staff = allStaffData.find(s => s.id === id);
       if (!staff) return;
       
       document.getElementById('staffModalTitle').textContent = 'Edit Staff Account';
       document.getElementById('staffAction').value = 'update';
       document.getElementById('staffId').value = staff.id;
       document.getElementById('sEmployeeId').value = staff.employee_id;
       document.getElementById('sFullName').value = staff.full_name;
       document.getElementById('sUsername').value = staff.username;
       document.getElementById('sRole').value = staff.role;
       document.getElementById('sPassword').value = '';
       document.getElementById('pwdHint').textContent = '(leave blank to keep current)';
       document.getElementById('sPassword').required = false;
       document.getElementById('staffModalOverlay').classList.add('active');
   }
   
   async function fetchNextEmployeeId(role) {
       document.getElementById('sEmployeeId').value = ''; 
   }
   
   document.getElementById('sRole')?.addEventListener('change', (e) => {
       if (document.getElementById('staffAction').value === 'create') {
           fetchNextEmployeeId(e.target.value);
       }
   });
   
   function closeStaffModal() { document.getElementById('staffModalOverlay').classList.remove('active'); }
   
   document.getElementById('staffForm')?.addEventListener('submit', async (e) => {
       e.preventDefault();
       const form = new FormData(e.target);
       const action = document.getElementById('staffAction').value;
       const id = document.getElementById('staffId').value;
       
       let url = '/api/staff';
       if (action === 'update') url += `/${id}`;
   
       try {
           const res = await fetch(url, { 
               method: 'POST', 
               headers: { 'X-CSRF-TOKEN': csrfToken }, 
               body: form 
           });
           const data = await res.json();
           if (data.success) {
               showToast('Staff saved successfully', 'success');
               closeStaffModal();
               loadStaff();
           } else {
               showToast(data.message || 'Error saving staff', 'error');
           }
       } catch (err) { showToast('Server error', 'error'); }
   });
   
   async function deleteStaff(id) {
       if (!confirm('Delete this staff account?')) return;
       try {
           const res = await fetch(`/api/staff/${id}`, { 
               method: 'DELETE',
               headers: { 'X-CSRF-TOKEN': csrfToken }
           });
           const data = await res.json();
           if (data.success) {
               showToast('Staff deleted', 'success');
               loadStaff();
           }
       } catch (err) { showToast('Server error', 'error'); }
   }
   
   /* ============================================================
      THEME
      ============================================================ */
   function applyPreset(primary, dark, light, accent) {
       const theme = { primary, primaryDark: dark, primaryLight: light, accent };
       if (typeof saveTheme === 'function') saveTheme(theme);
       if (typeof applyTheme === 'function') applyTheme(theme);
       const fields = { colorPrimary: primary, hexPrimary: primary, colorPrimaryDark: dark, hexPrimaryDark: dark, colorPrimaryLight: light, hexPrimaryLight: light, colorAccent: accent, hexAccent: accent };
       Object.keys(fields).forEach(id => { const el = document.getElementById(id); if (el) el.value = fields[id]; });
       showToast('Preset applied!', 'success');
   }
   
   function saveCustomTheme() {
       const theme = {
           primary: document.getElementById('hexPrimary').value,
           primaryDark: document.getElementById('hexPrimaryDark').value,
           primaryLight: document.getElementById('hexPrimaryLight').value,
           accent: document.getElementById('hexAccent').value
       };
       if (typeof saveTheme === 'function') saveTheme(theme);
       if (typeof applyTheme === 'function') applyTheme(theme);
       showToast('Theme saved!', 'success');
   }
   
   function resetTheme() {
       const def = { primary:'#e63a1a', primaryDark:'#c22f13', primaryLight:'#ffebe3', accent:'#ff7849' };
       if (typeof saveTheme === 'function') saveTheme(def);
       if (typeof applyTheme === 'function') applyTheme(def);
       showToast('Theme reset to default', 'success');
   }
   
   ['Primary', 'PrimaryDark', 'PrimaryLight', 'Accent'].forEach(name => {
       const colorInput = document.getElementById('color' + name);
       const hexInput = document.getElementById('hex' + name);
       if (!colorInput || !hexInput) return;
       colorInput.oninput = () => { hexInput.value = colorInput.value.toUpperCase(); };
       hexInput.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) colorInput.value = hexInput.value; };
   });
   
   function loadThemeIntoEditor() {
       if (typeof getTheme !== 'function') return;
       const theme = getTheme();
       document.getElementById('colorPrimary').value = theme.primary;
       document.getElementById('hexPrimary').value = theme.primary;
       document.getElementById('colorPrimaryDark').value = theme.primaryDark;
       document.getElementById('hexPrimaryDark').value = theme.primaryDark;
       document.getElementById('colorPrimaryLight').value = theme.primaryLight;
       document.getElementById('hexPrimaryLight').value = theme.primaryLight;
       document.getElementById('colorAccent').value = theme.accent;
       document.getElementById('hexAccent').value = theme.accent;
   }
   
   /* ============================================================
      TOAST
      ============================================================ */
   function showToast(msg, type = '') {
       const t = document.getElementById('toast');
       if (!t) return;
       t.textContent = msg;
       t.className = 'toast show ' + type;
       clearTimeout(t._timer);
       t._timer = setTimeout(() => t.classList.remove('show'), 2800);
   }