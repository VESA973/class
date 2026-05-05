const vehicles = Array.from(document.querySelectorAll(".vehicle-card")).map((card) => ({
    id: card.dataset.id,
    name: card.dataset.name,
    category: card.dataset.category,
    price: Number(card.dataset.price),
}));

const categorySelect = document.querySelector("#categorySelect");
const modelSelect = document.querySelector("#modelSelect");
const daysInput = document.querySelector("#daysInput");
const startDateInput = document.querySelector("#startDateInput");
const priceEstimate = document.querySelector("#priceEstimate");
const filterButtons = document.querySelectorAll("[data-filter]");
const cards = document.querySelectorAll(".vehicle-card");
const menuToggle = document.querySelector("[data-menu-toggle]");
const menu = document.querySelector("[data-menu]");
const header = document.querySelector("[data-header]");
const prestationCarousel = document.querySelector("[data-prestation-carousel]");

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
    modelSelect.innerHTML = "";

    if (!options.length) {
        modelSelect.add(new Option("Aucun modele disponible", ""));
    }

    options.forEach((vehicle) => {
        modelSelect.add(new Option(vehicle.name, vehicle.id));
    });

    updateEstimate();
}

function selectedVehicle() {
    return vehicles.find((vehicle) => vehicle.id === modelSelect.value) || filteredVehicles()[0] || vehicles[0];
}

function updateEstimate() {
    const vehicle = selectedVehicle();
    const days = Math.max(Number(daysInput.value) || 1, 1);
    priceEstimate.textContent = vehicle ? formatPrice(vehicle.price * days) : "Sur demande";
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

function initPrestationCarousel() {
    if (!prestationCarousel) {
        return;
    }

    const track = prestationCarousel.querySelector("[data-carousel-track]");
    const carouselCards = Array.from(prestationCarousel.querySelectorAll("[data-carousel-card]"));
    const prevButton = prestationCarousel.querySelector("[data-carousel-prev]");
    const nextButton = prestationCarousel.querySelector("[data-carousel-next]");
    const count = prestationCarousel.querySelector("[data-carousel-count]");
    const dots = prestationCarousel.querySelector("[data-carousel-dots]");
    let activeIndex = 0;

    if (!track || !carouselCards.length) {
        prevButton?.setAttribute("disabled", "disabled");
        nextButton?.setAttribute("disabled", "disabled");
        return;
    }

    function twoDigits(value) {
        return String(value).padStart(2, "0");
    }

    function setActive(index, shouldScroll = true) {
        activeIndex = Math.max(0, Math.min(index, carouselCards.length - 1));

        carouselCards.forEach((card, cardIndex) => {
            card.classList.toggle("is-active", cardIndex === activeIndex);
        });

        dots.querySelectorAll("button").forEach((dot, dotIndex) => {
            dot.classList.toggle("is-active", dotIndex === activeIndex);
        });

        count.textContent = `${twoDigits(activeIndex + 1)} / ${twoDigits(carouselCards.length)}`;
        prevButton.disabled = activeIndex === 0;
        nextButton.disabled = activeIndex === carouselCards.length - 1;

        if (shouldScroll) {
            carouselCards[activeIndex].scrollIntoView({
                behavior: "smooth",
                block: "nearest",
                inline: "start",
            });
        }
    }

    carouselCards.forEach((_, index) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.setAttribute("aria-label", `Afficher la prestation ${index + 1}`);
        dot.addEventListener("click", () => setActive(index));
        dots.append(dot);
    });

    prevButton.addEventListener("click", () => setActive(activeIndex - 1));
    nextButton.addEventListener("click", () => setActive(activeIndex + 1));

    track.addEventListener("scroll", () => {
        const nextIndex = carouselCards.reduce((closestIndex, card, index) => {
            const cardDistance = Math.abs(card.offsetLeft - track.scrollLeft);
            const closestDistance = Math.abs(carouselCards[closestIndex].offsetLeft - track.scrollLeft);
            return cardDistance < closestDistance ? index : closestIndex;
        }, activeIndex);

        if (nextIndex !== activeIndex) {
            setActive(nextIndex, false);
        }
    }, { passive: true });

    setActive(0, false);
}

categorySelect.addEventListener("change", () => {
    syncModelOptions();
    filterFleet(categorySelect.value);
});

modelSelect.addEventListener("change", updateEstimate);
daysInput.addEventListener("input", updateEstimate);

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

if (startDateInput) {
    const today = new Date().toISOString().slice(0, 10);
    startDateInput.min = today;
    startDateInput.value ||= today;
}

syncModelOptions();
initPrestationCarousel();
toggleHeader();
