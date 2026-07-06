const moneyFormatter = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD"
});

const cartPanel = document.querySelector(".cart-panel");
const itemTotal = document.querySelector("#item-total");
const cartCount = document.querySelector("#cart-count");
const summaryProducts = document.querySelector("#summary-products");
const summarySubtotal = document.querySelector("#summary-subtotal");
const summaryTotal = document.querySelector("#summary-total");

function getRows() {
    return [...document.querySelectorAll(".cart-row")];
}

function updateCart() {
    let productCount = getRows().length;
    let cartSubtotal = 0;

    getRows().forEach((row) => {
        const price = Number(row.dataset.price);
        const qty = Number(row.querySelector(".qty-value").textContent);
        const subtotal = price * qty;

        row.querySelector(".line-subtotal").textContent = moneyFormatter.format(subtotal);
        cartSubtotal += subtotal;
    });

    itemTotal.textContent = productCount;
    cartCount.textContent = productCount;
    summaryProducts.textContent = productCount;
    summarySubtotal.textContent = moneyFormatter.format(cartSubtotal);
    summaryTotal.textContent = moneyFormatter.format(cartSubtotal);

    if (getRows().length === 0 && !document.querySelector(".empty-cart")) {
        const emptyMessage = document.createElement("p");
        emptyMessage.className = "empty-cart";
        emptyMessage.textContent = "Tu carrito está vacío.";
        cartPanel.insertBefore(emptyMessage, document.querySelector(".secure-note"));
    }
}

document.addEventListener("click", (event) => {
    const row = event.target.closest(".cart-row");

    if (!row) {
        return;
    }

    const qtyValue = row.querySelector(".qty-value");
    const currentQty = Number(qtyValue.textContent);

    if (event.target.matches(".qty-plus")) {
        qtyValue.textContent = currentQty + 1;
        updateCart();
    }

    if (event.target.matches(".qty-minus") && currentQty > 1) {
        qtyValue.textContent = currentQty - 1;
        updateCart();
    }

    if (event.target.matches(".remove-button")) {
        row.remove();
        updateCart();
    }
});

updateCart();