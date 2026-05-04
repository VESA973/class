const vehicles = Array.from(document.querySelectorAll(".vehicle-card")).map((card) => ({
    name: card.dataset.name,
    category: card.dataset.category,
    price: Number(card.dataset.price),
}));

const categorySelect = document.querySelector("#categorySelect");
const modelSelect = document.querySelector("#modelSelect");
const daysInput = document.querySelector("#daysInput");
const priceEstimate = document.querySelector("#priceEstimate");
const reserveLink = document.querySelector("#reserveLink");
const bookingForm = document.querySelector("#bookingForm");
const filterButtons = document.querySelectorAll("[data-filter]");
const cards = document.querySelectorAll(".vehicle-card");
const menuToggle = document.querySelector("[data-menu-toggle]");
const menu = document.querySelector("[data-menu]");
const header = document.querySelector("[data-header]");

function formatPrice(value) {
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: "EUR",
        maximumFractionDigits: 0,
    }).format(value);
}

function filteredVehicles() {
    const category = categorySelect.value;
    return vehicles.filter((vehicle) => category === "Tous" || vehicle.category === category);
}

function syncModelOptions() {
    const options = filteredVehicles();
    modelSelect.innerHTML = options
        .map((vehicle) => `<option value="${vehicle.name}">${vehicle.name}</option>`)
        .join("");
    updateEstimate();
}

function selectedVehicle() {
    return vehicles.find((vehicle) => vehicle.name === modelSelect.value) || filteredVehicles()[0] || vehicles[0];
}

function updateEstimate() {
    const vehicle = selectedVehicle();
    const days = Math.max(Number(daysInput.value) || 1, 1);
    priceEstimate.textContent = formatPrice(vehicle.price * days);
    reserveLink.href = `https://wa.me/33180114483?text=${encodeURIComponent(`Bonjour, je souhaite reserver ${vehicle.name} pour ${days} jour(s).`)}`;
}

function filterFleet(category) {
    cards.forEach((card) => {
        card.classList.toggle("is-hidden", category !== "Tous" && card.dataset.category !== category);
    });

    filterButtons.forEach((button) => {
        button.classList.toggle("active", button.dataset.filter === category);
    });
}

function toggleHeader() {
    header.classList.toggle("is-scrolled", window.scrollY > 24);
}

categorySelect.addEventListener("change", () => {
    syncModelOptions();
    filterFleet(categorySelect.value);
});

modelSelect.addEventListener("change", updateEstimate);
daysInput.addEventListener("input", updateEstimate);

bookingForm.addEventListener("submit", (event) => {
    event.preventDefault();
    reserveLink.click();
});

filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const category = button.dataset.filter;
        categorySelect.value = category;
        syncModelOptions();
        filterFleet(category);
    });
});

menuToggle.addEventListener("click", () => {
    const isOpen = menu.classList.toggle("is-open");
    header.classList.toggle("is-open", isOpen);
    menuToggle.setAttribute("aria-expanded", String(isOpen));
});

menu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
        menu.classList.remove("is-open");
        header.classList.remove("is-open");
        menuToggle.setAttribute("aria-expanded", "false");
    });
});

window.addEventListener("scroll", toggleHeader, { passive: true });
syncModelOptions();
toggleHeader();
