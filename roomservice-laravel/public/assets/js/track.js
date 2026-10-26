/* ============================================================
   ORDER TRACKING SCRIPT (Laravel Version)
   ============================================================ */

   const STATUS_FLOW = ['PENDING', 'PREPARING', 'READY', 'DELIVERING', 'DELIVERED', 'CHARGED'];

   const STATUS_LABEL = {
       PENDING:    'Received',
       PREPARING:  'Preparing',
       READY:      'Ready',
       DELIVERING: 'On the Way',
       DELIVERED:  'Delivered',
       CHARGED:    'Completed',
       CANCELLED:  'Cancelled',
       VOIDED:     'Voided'
   };
   
   const STATUS_DESC = {
       PENDING:    'Your order has been received.',
       PREPARING:  'Your order is being prepared by our chefs.',
       READY:      'Your order is ready for delivery.',
       DELIVERING: 'Your order is on its way to your room.',
       DELIVERED:  'Your order has been delivered. Enjoy!',
       CHARGED:    'Charge posted to your room folio.',
       CANCELLED:  'This order was cancelled.',
       VOIDED:     'This order was voided.'
   };
   
   const STATUS_COLOR = {
       PENDING:    '#f59e0b',
       PREPARING:  '#3b82f6',
       READY:      '#10b981',
       DELIVERING: '#8b5cf6',
       DELIVERED:  '#06b6d4',
       CHARGED:    '#6b7280',
       CANCELLED:  '#ef4444',
       VOIDED:     '#000000'
   };
   
   const params = new URLSearchParams(window.location.search);
   const orderId = params.get('order_id');
   const roomFromUrl = params.get('room');
   const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
   
   if (roomFromUrl) {
       localStorage.setItem('rs_current_room', roomFromUrl);
   }
   
   let lastOrderHash = '';
   let lastAllHash = '';
   
   /* ============================================================
      LOAD SINGLE ORDER
      ============================================================ */
   async function loadOrder() {
       const el = document.getElementById('trackContent');
       if (!el) return;
   
       if (!orderId) {
           el.innerHTML = '<div class="track-empty">No order selected. Click an order below to view details.</div>';
           return;
       }
   
       try {
           // Fetch all orders and find the specific ID
           const res = await fetch(`/api/orders`);
           let orders = await res.json();
           const o = orders.find(order => order.id == orderId);
   
           if (!o) {
               el.innerHTML = `<div class="track-empty">Order not found.</div>`;
               return;
           }
   
           const newHash = `${o.id}-${o.status}-${o.estimated_minutes}-${o.items.length}-${o.cancel_requested_at}`;
           
           if (newHash === lastOrderHash) {
               if (!['CHARGED', 'CANCELLED', 'VOIDED'].includes(o.status)) setTimeout(loadOrder, 4000);
               return;
           }
           lastOrderHash = newHash;
   
           const currentIndex = STATUS_FLOW.indexOf(o.status);
           const isDone = ['CHARGED', 'CANCELLED', 'VOIDED'].includes(o.status);
           const progressPct = currentIndex < 0 ? 0 : (currentIndex / (STATUS_FLOW.length - 1)) * 100;
   
           const itemsHtml = o.items.map(i => `
               <div class="track-item-row">
                   <span>${i.item_name} <span class="qty-tag">×${i.qty}</span></span>
                   <span>PHP ${parseFloat(i.subtotal).toFixed(2)}</span>
               </div>
           `).join('');
   
           const steps = STATUS_FLOW.map((s, i) => {
               let cls = 'pending', marker = i + 1;
               if (i < currentIndex) { cls = 'done'; marker = '✓'; }
               else if (i === currentIndex) { cls = 'current'; marker = i + 1; }
               return `
                   <div class="stack-step ${cls}">
                       <div class="marker">${marker}</div>
                       <div class="step-info">
                           <div class="step-label">${STATUS_LABEL[s]}</div>
                           <div class="step-desc">${STATUS_DESC[s]}</div>
                       </div>
                   </div>
               `;
           }).join('');
   
           let etaHtml = '';
           if (o.estimated_minutes && ['PREPARING', 'READY'].includes(o.status)) {
               etaHtml = `<div class="track-eta" style="color: var(--primary); font-weight: 800; font-size: 13px;">Estimated Time: ${o.estimated_minutes} mins</div>`;
           }
   
           let actionBtn = '';
           if (o.status === 'PENDING') {
               actionBtn = `<button type="button" onclick="openCancelModal(${o.id}, 'PENDING')" style="width:100%; margin-top:20px; padding:12px; background:var(--bg-alt); border:1px solid var(--border); color:var(--danger); border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">Cancel Order</button>`;
           } else if (['PREPARING', 'READY', 'DELIVERING'].includes(o.status) && !o.cancel_requested_at) {
               actionBtn = `<button type="button" onclick="openCancelModal(${o.id}, 'REQUEST')" style="width:100%; margin-top:20px; padding:12px; background:var(--bg-alt); border:1px solid var(--border); color:var(--muted); border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">Request Cancellation</button>`;
           } else if (o.cancel_requested_at) {
               actionBtn = `<div style="margin-top:20px; padding:12px; text-align:center; background:var(--primary-light); color:var(--primary); border-radius:10px; font-size:12px; font-weight:700;">Cancellation request pending manager approval.</div>`;
           }
   
           el.innerHTML = `
               <div class="track-card">
                   <div class="track-top">
                       <div>
                           <div class="track-order-code">${o.order_code}</div>
                           <div class="track-room">
                               Room ${o.room_number}${o.guest_name ? ' · ' + o.guest_name : ''}
                           </div>
                       </div>
                       <span class="status-badge" style="background:${STATUS_COLOR[o.status] || '#999'};">
                           ${o.status === 'CHARGED' ? 'COMPLETED' : o.status}
                       </span>
                   </div>
   
                   ${o.special_notes ? `<div class="track-notes"><strong>Notes:</strong> ${o.special_notes}</div>` : ''}
   
                   <h3 class="track-subtitle">Live Progress</h3>
                   <div class="track-headline">${STATUS_LABEL[o.status] || o.status}</div>
                   ${etaHtml}
                   <div class="track-eta">${STATUS_DESC[o.status] || ''}</div>
   
                   <div class="stack-timeline" style="--progress:${progressPct}%">${steps}</div>
   
                   ${isDone ? `<div class="track-done">${['CANCELLED', 'VOIDED'].includes(o.status) ? 'Order was cancelled.' : 'Order complete. Thank you!'}</div>` : ''}
                   ${actionBtn}
               </div>
   
               <div class="track-card">
                   <h3 class="track-subtitle">Order Summary</h3>
                   <div class="track-items">${itemsHtml}</div>
                   <div class="track-total">
                       <span>Total</span>
                       <span>PHP ${parseFloat(o.total_amount).toFixed(2)}</span>
                   </div>
                   <p class="track-note">Charged to Room ${o.room_number} folio. Paid at check-out.</p>
                   <button class="view-receipt-btn" onclick="openReceiptModal(${o.id})">View Receipt</button>
               </div>
           `;
   
           if (!isDone) setTimeout(loadOrder, 4000);
   
       } catch (e) {
           console.error(e);
           el.innerHTML = '<div class="track-empty">Failed to load order.</div>';
       }
   }
   
   /* ============================================================
      CANCEL REQUEST MODAL
      ============================================================ */
   function openCancelModal(id, type) {
       let modal = document.getElementById('guestCancelModal');
       if (!modal) {
           modal = document.createElement('div');
           modal.id = 'guestCancelModal';
           modal.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; z-index:100000; padding:16px; opacity:0; transition:opacity 0.2s;';
           document.body.appendChild(modal);
       }
   
       const title = type === 'PENDING' ? 'Cancel Order?' : 'Request Cancellation';
       const desc = type === 'PENDING' 
           ? 'Are you sure you want to cancel this order? This action cannot be undone.' 
           : 'Please provide a reason. The manager will review your request.';
   
       modal.innerHTML = `
           <div style="background:var(--card); border-radius:16px; width:100%; max-width:400px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.3); transform:scale(0.9); transition:transform 0.2s; border:1px solid var(--border);">
               <h2 style="font-size:18px; font-weight:900; color:var(--text-strong); margin-bottom:8px;">${title}</h2>
               <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">${desc}</p>
               
               ${type === 'REQUEST' ? `
               <textarea id="cancelReasonInput" placeholder="e.g. Nagbago ang isip, mali ang order..." style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--border); background:var(--bg-alt); color:var(--text); font-family:inherit; font-size:13px; height:100px; resize:none; margin-bottom:16px; outline:none;"></textarea>
               ` : ''}
               
               <div style="display:flex; gap:10px;">
                   <button type="button" onclick="closeCancelModal()" style="flex:1; padding:12px; background:var(--bg-alt); border:1px solid var(--border); color:var(--text); border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">Back</button>
                   <button type="button" onclick="submitCancel(${id}, '${type}')" style="flex:1; padding:12px; background:var(--danger); border:none; color:#fff; border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">${type === 'PENDING' ? 'Yes, Cancel' : 'Submit Request'}</button>
               </div>
           </div>
       `;
   
       modal.style.display = 'flex';
       setTimeout(() => {
           modal.style.opacity = '1';
           modal.querySelector('div').style.transform = 'scale(1)';
       }, 10);
   }
   
   function closeCancelModal() {
       const modal = document.getElementById('guestCancelModal');
       if (modal) {
           modal.style.opacity = '0';
           modal.querySelector('div').style.transform = 'scale(0.9)';
           setTimeout(() => { modal.style.display = 'none'; }, 200);
       }
   }
   
   async function submitCancel(id, type) {
       let reason = 'Guest requested cancellation';
       
       if (type === 'REQUEST') {
           const input = document.getElementById('cancelReasonInput');
           if (!input || input.value.trim() === '') {
               alert('Please provide a reason for cancellation.');
               return;
           }
           reason = input.value.trim();
       }
   
       try {
           const res = await fetch(`/api/orders/${id}/cancel-request`, {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
                   'X-CSRF-TOKEN': csrfToken
               },
               body: JSON.stringify({ reason: reason })
           });
           const data = await res.json();
           
           if (data.success) {
               closeCancelModal();
               lastOrderHash = ''; 
               loadOrder();
           } else {
               alert(data.message || 'Failed to process request');
           }
       } catch (e) {
           alert('Server error. Please try again.');
       }
   }
   
   /* ============================================================
      RECEIPT MODAL
      ============================================================ */
   async function openReceiptModal(id) {
       const modal = document.getElementById('receiptModal');
       const body = document.getElementById('receiptModalBody');
       if (!modal || !body) return;
   
       body.innerHTML = '<div class="receipt-loading">Loading receipt...</div>';
       modal.style.display = 'flex';
   
       try {
           const res = await fetch(`/api/orders`);
           let orders = await res.json();
           const o = orders.find(order => order.id == id);
   
           if (!o) {
               body.innerHTML = '<div class="receipt-loading">Order not found.</div>';
               return;
           }
   
           const itemsHtml = o.items.map(i => `
               <div class="receipt-item">
                   <span>${i.item_name} × ${i.qty}</span>
                   <span>PHP ${parseFloat(i.subtotal).toFixed(2)}</span>
               </div>
           `).join('');
   
           body.innerHTML = `
               <div class="receipt-header">
                   <div class="receipt-code">${o.order_code}</div>
                   <div class="receipt-room">Room ${o.room_number}</div>
                   ${o.guest_name ? `<div class="receipt-guest">${o.guest_name}</div>` : ''}
               </div>
   
               <div class="receipt-section">
                   <h4>Items</h4>
                   ${itemsHtml}
               </div>
   
               <div class="receipt-section">
                   <div class="receipt-row receipt-total">
                       <span>Total</span>
                       <span>PHP ${parseFloat(o.total_amount).toFixed(2)}</span>
                   </div>
                   <div class="receipt-row receipt-note">
                       <span>Charged to Room ${o.room_number} folio</span>
                   </div>
               </div>
   
               <div class="receipt-section receipt-staff">
                   <h4>Staff</h4>
                   <div class="receipt-row">
                       <span>Handled by</span>
                       <span>${o.coffee_manager ? o.coffee_manager.full_name : '—'}</span>
                   </div>
                   <div class="receipt-row">
                       <span>Delivered by</span>
                       <span>${o.waiter ? o.waiter.full_name : '—'}</span>
                   </div>
               </div>
   
               <div class="receipt-footer">
                   <p>Thank you for dining with us!</p>
                   <p class="receipt-small">This is a soft copy. Payment at check-out.</p>
               </div>
           `;
       } catch (e) {
           body.innerHTML = '<div class="receipt-loading">Failed to load receipt.</div>';
       }
   }
   
   function closeReceiptModal() {
       const modal = document.getElementById('receiptModal');
       if (modal) modal.style.display = 'none';
   }
   
   document.getElementById('receiptModal')?.addEventListener('click', (e) => {
       if (e.target.id === 'receiptModal') closeReceiptModal();
   });
   
   /* ============================================================
      LOAD ALL MY ORDERS
      ============================================================ */
   async function loadAllMyOrders() {
       const room = localStorage.getItem('rs_current_room');
       const el = document.getElementById('allOrdersContent');
       if (!el) return;
   
       if (!room) {
           if (lastAllHash !== 'empty') {
               el.innerHTML = '<div class="track-empty">No room set. Please scan your room QR code first.</div>';
               lastAllHash = 'empty';
           }
           return;
       }
   
       try {
           const res = await fetch(`/api/orders?room=${room}`);
           const orders = await res.json();
   
           if (orders.length === 0) {
               if (lastAllHash !== 'empty_' + room) {
                   el.innerHTML = `<div class="track-empty">No orders found for Room ${room}.</div>`;
                   lastAllHash = 'empty_' + room;
               }
               return;
           }
   
           const newHash = orders.map(o => `${o.id}-${o.status}`).join('|');
           if (newHash === lastAllHash) return;
           lastAllHash = newHash;
   
           el.innerHTML = orders.map(o => {
               const color = STATUS_COLOR[o.status] || '#999';
               const isCurrent = o.id == orderId;
               const statusLabel = o.status === 'CHARGED' ? 'COMPLETED' : o.status;
               
               return `
                   <div class="track-list-item ${isCurrent ? 'current' : ''}"
                        onclick="window.location.href='/track?order_id=${o.id}&room=${room}'">
                       <div class="track-list-left">
                           <div class="track-list-code">${o.order_code}</div>
                           <div class="track-list-meta">
                               Room ${o.room_number} · PHP ${parseFloat(o.total_amount).toFixed(2)}
                           </div>
                       </div>
                       <div class="track-list-right">
                           <span class="status-badge" style="background:${color};">${statusLabel}</span>
                           <span class="track-list-arrow">›</span>
                       </div>
                   </div>
               `;
           }).join('');
   
       } catch (e) {
           console.error(e);
           el.innerHTML = '<div class="track-empty">Failed to load orders.</div>';
       }
   }
   
   loadOrder();
   loadAllMyOrders();
   setInterval(loadAllMyOrders, 8000);