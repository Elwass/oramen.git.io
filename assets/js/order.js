const cart = new Map();

const renderCart = () => {
    const list = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const submitBtn = document.getElementById('submit-order');
    list.innerHTML = '';
    let total = 0;

    cart.forEach((item) => {
        total += item.price * item.quantity;
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `
            <div>
                <strong>${item.name}</strong>
                <div class="text-muted small">Rp ${formatCurrency(item.price)}</div>
            </div>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" data-action="decrease" data-id="${item.id}">-</button>
                <span class="btn btn-outline-light disabled">${item.quantity}</span>
                <button class="btn btn-outline-secondary" data-action="increase" data-id="${item.id}">+</button>
            </div>
        `;
        list.appendChild(li);
    });

    if (cart.size === 0) {
        list.innerHTML = '<li class="list-group-item text-center text-muted">Keranjang kosong. Tambahkan menu favorit Anda.</li>';
    }

    totalEl.textContent = `Rp ${formatCurrency(total)}`;
    submitBtn.disabled = cart.size === 0;
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID').format(value);

const addToCart = (item) => {
    const existing = cart.get(item.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.set(item.id, { ...item, quantity: 1 });
    }
    renderCart();
};

const updateQuantity = (id, delta) => {
    const item = cart.get(id);
    if (!item) return;
    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.delete(id);
    }
    renderCart();
};

document.addEventListener('click', (event) => {
    const target = event.target;
    if (target.matches('[data-item]')) {
        const item = JSON.parse(target.getAttribute('data-item'));
        addToCart(item);
    }
    if (target.matches('[data-action]')) {
        const id = Number(target.getAttribute('data-id'));
        if (target.getAttribute('data-action') === 'increase') {
            updateQuantity(id, 1);
        } else {
            updateQuantity(id, -1);
        }
    }
});

const scrollToCart = () => {
    document.getElementById('submit-order').scrollIntoView({ behavior: 'smooth' });
};

window.scrollToCart = scrollToCart;

const submitBtn = document.getElementById('submit-order');
const successAlert = document.getElementById('order-success');
const errorAlert = document.getElementById('order-error');

submitBtn.addEventListener('click', () => {
    const tableNumberInput = document.getElementById('table-number');
    const tableNumber = tableNumberInput.value.trim();
    if (!tableNumber) {
        tableNumberInput.classList.add('is-invalid');
        return;
    }
    tableNumberInput.classList.remove('is-invalid');

    const items = Array.from(cart.values()).map((item) => ({
        menu_item_id: item.id,
        quantity: item.quantity,
        price: item.price,
    }));

    fetch('/submit_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_number: tableNumber,
            items,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                cart.clear();
                renderCart();
                successAlert.classList.remove('d-none');
                errorAlert.classList.add('d-none');
                document.getElementById('table-number-display').textContent = tableNumber;
                window.__ramenTableId = data.table_id;
            } else {
                throw new Error(data.message || 'Terjadi kesalahan.');
            }
        })
        .catch((err) => {
            errorAlert.textContent = err.message;
            errorAlert.classList.remove('d-none');
            successAlert.classList.add('d-none');
        });
});

renderCart();
