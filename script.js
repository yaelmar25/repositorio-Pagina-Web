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




function onlyNumbers(value) {
    return value.replace(/\D/g, "");
}

const paymentForm = document.querySelector("#payment-form");

if (paymentForm) {
    const cardName = document.querySelector("#card-name");
    const cardNumber = document.querySelector("#card-number");
    const expiryDate = document.querySelector("#expiry-date");
    const securityCode = document.querySelector("#security-code");
    const transactionStatus = document.querySelector("#transaction-status");
    const formMessage = document.querySelector("#form-message");

    function setStatus(status) {
        transactionStatus.className = "status-badge";

        if (status === "approved") {
            transactionStatus.classList.add("approved");
            transactionStatus.textContent = "Aprobado";
            formMessage.className = "form-message success";
            formMessage.textContent = "Pago confirmado correctamente.";
            return;
        }

        if (status === "rejected") {
            transactionStatus.classList.add("rejected");
            transactionStatus.textContent = "Rechazado";
            formMessage.className = "form-message error";
            formMessage.textContent = "Revisa los datos de la tarjeta e intenta nuevamente.";
            return;
        }

        transactionStatus.classList.add("pending");
        transactionStatus.textContent = "Pendiente";
        formMessage.className = "form-message";
        formMessage.textContent = "";
    }

    cardNumber.addEventListener("input", () => {
        const numbers = onlyNumbers(cardNumber.value).slice(0, 16);
        cardNumber.value = numbers.replace(/(\d{4})(?=\d)/g, "$1 ");
        setStatus("pending");
    });

    expiryDate.addEventListener("input", () => {
        const numbers = onlyNumbers(expiryDate.value).slice(0, 4);

        if (numbers.length > 2) {
            expiryDate.value = `${numbers.slice(0, 2)} / ${numbers.slice(2)}`;
        } else {
            expiryDate.value = numbers;
        }

        setStatus("pending");
    });

    securityCode.addEventListener("input", () => {
        securityCode.value = onlyNumbers(securityCode.value).slice(0, 3);
        setStatus("pending");
    });

    cardName.addEventListener("input", () => {
        setStatus("pending");
    });

    paymentForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const cleanCardNumber = onlyNumbers(cardNumber.value);
        const cleanExpiryDate = onlyNumbers(expiryDate.value);
        const cleanSecurityCode = onlyNumbers(securityCode.value);

        const hasValidName = cardName.value.trim().length >= 3;
        const hasValidCard = cleanCardNumber.length === 16;
        const hasValidExpiry = cleanExpiryDate.length === 4;
        const hasValidSecurityCode = cleanSecurityCode.length === 3;

        if (!hasValidName || !hasValidCard || !hasValidExpiry || !hasValidSecurityCode) {
            setStatus("rejected");
            return;
        }

        setStatus("approved");
    });
}