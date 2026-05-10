const nav = document.querySelector(".mega-nav");
const items = document.querySelectorAll(".mega-item");
const triggers = document.querySelectorAll("[data-mega-trigger]");
const mobileToggle = document.querySelector("[data-mobile-toggle]");
const mobileClose = document.querySelector("[data-mobile-close]");
const mobileBackdrop = document.querySelector("[data-mobile-backdrop]");
const mobileGroupTriggers = document.querySelectorAll(
    "[data-mobile-group-trigger]",
);

function closeDesktopMenus() {
    items.forEach((item) => {
        item.dataset.open = "false";
        const trigger = item.querySelector("[data-mega-trigger]");
        if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
}

triggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
        const item = trigger.closest(".mega-item");

        const isOpen = item.dataset.open === "true";

        closeDesktopMenus();

        if (!isOpen) {
            item.dataset.open = "true";
            const openedItem = document.querySelector(
                ".mega-item[data-open='true'] .mega-panel form input",
            );
            trigger.setAttribute("aria-expanded", "true");
            if (openedItem) {
                openedItem.focus();
            }
        }
    });
});

document.addEventListener("click", (event) => {
    if (!nav.contains(event.target)) {
        closeDesktopMenus();
        nav.dataset.mobileOpen = "false";
        if (mobileToggle) mobileToggle.setAttribute("aria-expanded", "false");
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeDesktopMenus();
        nav.dataset.mobileOpen = "false";
        if (mobileToggle) mobileToggle.setAttribute("aria-expanded", "false");
    }
});

if (mobileToggle) {
    mobileToggle.addEventListener("click", () => {
        const open = nav.dataset.mobileOpen === "true";
        nav.dataset.mobileOpen = open ? "false" : "true";
        mobileToggle.setAttribute("aria-expanded", open ? "false" : "true");
    });
}

if (mobileClose) {
    mobileClose.addEventListener("click", () => {
        nav.dataset.mobileOpen = "false";
        if (mobileToggle) mobileToggle.setAttribute("aria-expanded", "false");
    });
}

if (mobileBackdrop) {
    mobileBackdrop.addEventListener("click", () => {
        nav.dataset.mobileOpen = "false";
        if (mobileToggle) mobileToggle.setAttribute("aria-expanded", "false");
    });
}

mobileGroupTriggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
        const group = trigger.closest(".mega-mobile-group");
        const isOpen = group.dataset.open === "true";
        group.dataset.open = isOpen ? "false" : "true";
        trigger.querySelector("span").textContent = isOpen ? "+" : "−";
    });
});

// Theme toggle logic (3-state: system, light, dark)
const root = document.documentElement;
const themeToggle = document.getElementById("theme-toggle");
const iconSystem = document.getElementById("icon-system");
const iconLight = document.getElementById("icon-light");
const iconDark = document.getElementById("icon-dark");

function showIcon(which) {
    if (iconSystem) iconSystem.classList.toggle("hidden", which !== "system");
    if (iconLight) iconLight.classList.toggle("hidden", which !== "light");
    if (iconDark) iconDark.classList.toggle("hidden", which !== "dark");
}

function applyTheme(mode) {
    if (mode === "dark") {
        root.classList.add("dark");
    } else {
        root.classList.remove("dark");
    }
    localStorage.setItem("theme", mode);
    showIcon(mode);
}

let saved = localStorage.getItem("theme");
if (!["system", "light", "dark"].includes(saved)) {
    saved = "system";
}
applyTheme(saved);

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        const current = localStorage.getItem("theme") || "system";
        const next =
            current === "system"
                ? "light"
                : current === "light"
                  ? "dark"
                  : "system";
        applyTheme(next);
    });
}
