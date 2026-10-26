/* ============================================================
   HOTEL ROOM SERVICE — GUEST ORDERING SCRIPT (Laravel Version)
   ============================================================ */

   let cart = [];
   let currentCategory = 'Main Course';
   let allMenuItems = [];
   const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
   
   /* ============================================================
      LOAD MENU
      ============================================================ */
   async function loadMenu() {
       try {
           const res = await fetch('/api/menu');
           allMenuItems = await res.json();
           updateCategoryCounts();
           renderMenu();
       } catch (e) {
           showToast('Failed to load menu', 'error');
           console.error(e);
       }
   }
   
   function updateCategoryCounts() {
       const counts = { 'Main Course': 0, 'Appetizer': 0, 'Dessert': 0, 'Beverage': 0 };
       allMenuItems.forEach(i => {
           if (counts[i.category] !== undefined) counts[i.category]++;
       });
       const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
       el('count-MainCourse', counts['Main Course']);
       el('count-Appetizer', counts['Appetizer']);
       el('count-Dessert', counts['Dessert']);
       el('count-Beverage', counts['Beverage']);
   }
   
   function renderMenu() {
       const grid = document.getElementById('menuGrid');
       const title = document.getElementById('menuTitle');
       const count = document.getElementById('menuCount');
       if (!grid) return;
       grid.innerHTML = '';
   
       const items = allMenuItems.filter(i => currentCategory === 'All' || i.category === currentCategory);
   
       const labelMap = {
           'Main Course': 'Main Dish',
           'Appetizer': 'Appetizers',
           'Dessert': 'Desserts',
           'Beverage': 'Coffee & Drinks'
       };
       if (title) title.textContent = labelMap[currentCategory] || currentCategory;
       if (count) count.textContent = items.length + ' item' + (items.length !== 1 ? 's' : '');
   
       if (items.length === 0) {
           grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;">No items available.</div>';
           return;
       }
   
       items.forEach(item => grid.appendChild(buildCard(item)));
   }
   
   function buildCard(item) {
       const outOfStock = item.remaining_stock <= 0 || item.is_available == 0;
       const card = document.createElement('div');
       card.className = 'menu-card' + (outOfStock ? ' out-of-stock' : '');
   
       const imgHtml = item.image_path
           ? `<img src="/${item.image_path}" alt="${item.name}" loading="lazy">`
           : `<span class="no-image">No Image</span>`;
   
       card.innerHTML = `
           <div class="img-box">${imgHtml}</div>
           <div class="card-body">
               <div class="name">${item.name}</div>
               <div class="desc">${item.description || ''}</div>
               <div class="price-row">
                   <div class="price">${formatPeso(item.price)}</div>
                   <button class="add-btn" ${outOfStock ? 'disabled' : ''}>
                       ${outOfStock ? 'Out of Stock' : '+ Add'}
                   </button>
               </div>
           </div>
       `;
   
       if (!outOfStock) {
           card.querySelector('.add-btn').onclick = (e) => {
               e.stopPropagation();
               addToCart(item);
           };
       }
       return card;
   }
   
   function addToCart(item) {
       const existing = cart.find(c => c.id === item.id);
       if (existing) {
           if (existing.qty + 1 > item.remaining_stock) {
               showToast(`Only ${item.remaining_stock} left for ${item.name}`, 'error');
               return;
           }
           existing.qty++;
       } else {
           cart.push({
               id: item.id,
               name: item.name,
               price: parseFloat(item.price),
               qty: 1,
               image_path: item.image_path,
               remaining_stock: item.remaining_stock
           });
       }
       renderCart();
       const cartPanel = document.getElementById('cartPanel');
       if (cartPanel) {
           cartPanel.classList.remove('shake');
           void cartPanel.offsetWidth;
           cartPanel.classList.add('shake');
           cartPanel.classList.add('has-items');
       }
       showToast(`${item.name} added`, 'success');
   
       if (isMobileView()) openMobileCart();
   }
   
   function renderCart() {
       const list = document.getElementById('cartItems');
       const subtotalEl = document.getElementById('subtotal');
       const totalEl = document.getElementById('grandTotal');
       const checkoutBtn = document.getElementById('checkoutBtn');
       const cartCount = document.getElementById('cartCount');
       const cartPanel = document.getElementById('cartPanel');
   
       if (!list) return;
       list.innerHTML = '';
       let subtotal = 0;
       let totalQty = 0;
   
       if (cart.length === 0) {
           list.innerHTML = '<div class="cart-empty">No items yet</div>';
           if (subtotalEl) subtotalEl.textContent = formatPeso(0);
           if (totalEl) totalEl.textContent = formatPeso(0);
           if (checkoutBtn) checkoutBtn.disabled = true;
           if (cartCount) cartCount.textContent = '0';
           if (cartPanel) {
               cartPanel.classList.remove('has-items');
               cartPanel.classList.remove('open');
           }
           return;
       }
   
       if (cartPanel) cartPanel.classList.add('has-items');
   
       cart.forEach((c, i) => {
           subtotal += c.price * c.qty;
           totalQty += c.qty;
           const thumbHtml = c.image_path
               ? `<img src="/${c.image_path}" alt="${c.name}">`
               : `<span class="no-image">No Img</span>`;
           
           const row = document.createElement('div');
           row.className = 'cart-item';
           row.innerHTML = `
               <div class="thumb">${thumbHtml}</div>
               <div class="info">
                   <div class="t">${c.name}</div>
                   <div class="p">${formatPeso(c.price)}</div>
               </div>
               <div class="qty-controls">
                   <button type="button" onclick="changeQty(${i}, -1)">−</button>
                   <span>${c.qty}</span>
                   <button type="button" onclick="changeQty(${i}, 1)">+</button>
               </div>
           `;
           list.appendChild(row);
       });
   
       if (subtotalEl) subtotalEl.textContent = formatPeso(subtotal);
       if (totalEl) totalEl.textContent = formatPeso(subtotal);
       if (checkoutBtn) checkoutBtn.disabled = false;
       if (cartCount) cartCount.textContent = totalQty;
   }
   
   function changeQty(index, delta) {
       const item = cart[index];
       const newQty = item.qty + delta;
       if (newQty <= 0) cart.splice(index, 1);
       else if (newQty > item.remaining_stock) {
           showToast(`Only ${item.remaining_stock} available`, 'error');
           return;
       } else item.qty = newQty;
       renderCart();
   }
   
   const checkoutBtnEl = document.getElementById('checkoutBtn');
   if (checkoutBtnEl) {
       checkoutBtnEl.onclick = async () => {
           const room = document.getElementById('roomNumber').value;
           if (!room) { showToast('Please select floor and room number', 'error'); return; }
           if (cart.length === 0) { showToast('Your cart is empty', 'error'); return; }
   
           localStorage.setItem('rs_current_room', room);
   
           const payload = {
               room_number: room,
               guest_name: (document.getElementById('guestName')?.value || '').trim(),
               notes: (document.getElementById('notes')?.value || '').trim(),
               items: cart.map(c => ({ id: c.id, qty: c.qty }))
           };
   
           const btn = checkoutBtnEl;
           btn.disabled = true;
           btn.textContent = 'Placing...';
   
           try {
               const res = await fetch('/api/orders', {
                   method: 'POST',
                   headers: { 
                       'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': csrfToken 
                   },
                   body: JSON.stringify(payload)
               });
               const data = await res.json();
   
               if (data.success) {
                   if (typeof addActiveOrder === 'function') {
                       const categoryTimes = { 'Main Course': 20, 'Appetizer': 10, 'Dessert': 10, 'Beverage': 5 };
                       let maxPrepTime = 5;
                       cart.forEach(c => {
                           const item = allMenuItems.find(m => m.id === c.id);
                           if (item) {
                               const t = categoryTimes[item.category] || 15;
                               if (t > maxPrepTime) maxPrepTime = t;
                           }
                       });
                       addActiveOrder(data.order_id, room, maxPrepTime);
                   }
                   
                   cart = [];
                   renderCart();
                   if (document.getElementById('guestName')) document.getElementById('guestName').value = '';
                   if (document.getElementById('notes')) document.getElementById('notes').value = '';
   
                   const cartPanel = document.getElementById('cartPanel');
                   if (cartPanel) cartPanel.classList.remove('open');
   
                   showWaitingModal(() => {
                       window.location.href = `/track?order_id=${data.order_id}&room=${room}`;
                   });
               } else {
                   showToast(data.message || 'Failed to place order', 'error');
                   btn.disabled = false;
                   btn.textContent = 'Check Out';
               }
           } catch (e) {
               showToast('Server error', 'error');
               console.error(e);
               btn.disabled = false;
               btn.textContent = 'Check Out';
           }
       };
   }
   
   function showWaitingModal(onDone) {
       const modal = document.getElementById('waitModal');
       if (!modal) { if (onDone) onDone(); return; }
       modal.classList.add('active');
       setTimeout(() => {
           modal.classList.remove('active');
           if (onDone) onDone();
       }, 3000); 
   }
   
   function isMobileView() { return window.innerWidth <= 900; }
   
   function openMobileCart() {
       const cartPanel = document.getElementById('cartPanel');
       if (!cartPanel || !isMobileView()) return;
       cartPanel.classList.add('open');
       document.body.classList.add('cart-open');
   }
   
   function closeMobileCart() {
       const cartPanel = document.getElementById('cartPanel');
       if (!cartPanel) return;
       cartPanel.classList.remove('open');
       document.body.classList.remove('cart-open');
   }
   
   function setupMobileCart() {
       const cartPanel = document.getElementById('cartPanel');
       const header = document.getElementById('cartPanelHeader');
       if (!cartPanel || !header) return;
   
       header.addEventListener('click', (e) => {
           if (!isMobileView()) return;
           e.preventDefault();
           e.stopPropagation();
           if (cartPanel.classList.contains('open')) closeMobileCart();
           else openMobileCart();
       });
   
       document.addEventListener('click', (e) => {
           if (!isMobileView()) return;
           if (!cartPanel.classList.contains('open')) return;
           if (cartPanel.contains(e.target)) return;
           closeMobileCart();
       }, true);
   
       cartPanel.addEventListener('click', (e) => {
           if (!isMobileView()) return;
           e.stopPropagation();
       });
   }
   
   document.addEventListener('DOMContentLoaded', () => {
       document.querySelectorAll('#categoryNav button').forEach(btn => {
           btn.onclick = () => {
               document.querySelectorAll('#categoryNav button').forEach(b => b.classList.remove('active'));
               btn.classList.add('active');
               currentCategory = btn.dataset.cat;
               renderMenu();
           };
       });
   
       setupMobileCart();
       loadMenu();
       renderCart();
   });
   
   function formatPeso(amount) {
       return 'PHP ' + parseFloat(amount).toFixed(2);
   }
   
   function showToast(msg, type = '') {
       const t = document.getElementById('toast');
       if (!t) return;
       t.textContent = msg;
       t.className = 'toast show ' + type;
       clearTimeout(t._timer);
       t._timer = setTimeout(() => t.classList.remove('show'), 2800);
   }