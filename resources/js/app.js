document.addEventListener("DOMContentLoaded", () => {
    const htmlElement = document.documentElement;
    const toggleButton = document.getElementById("theme-toggle");
    const lightIcon = document.getElementById("icon-light");
    const darkIcon = document.getElementById("icon-dark");
    const systemIcon = document.getElementById("icon-system");
    // --- 1. Helper functions to determine and apply theme ---
    /**
     * Checks if the system prefers dark mode.
     * @returns {boolean} True if system prefers dark mode.
     */
    const prefersDarkMode = () => {
        return window.matchMedia("(prefers-color-scheme: dark)").matches;
    };
    /**
     * Sets the theme on the root element and updates localStorage.
     * @param {'light' | 'dark' | 'system'} theme - The theme to set.
     */
    const setTheme = (theme) => {
        let isDark = false;
        // Determine if the theme is explicitly dark or if the system dictates it
        if (theme === "dark") {
            isDark = true;
        } else if (theme === "system") {
            isDark = prefersDarkMode();
        } else {
            isDark = false; // 'light'
        }
        if (isDark) {
            htmlElement.setAttribute("data-theme", "dark");
            document.body.classList.add("dark"); // Assuming you use a body class for styling
            darkIcon.classList.remove("hidden");
            lightIcon.classList.add("hidden");
            systemIcon.classList.add("hidden");
        } else {
            htmlElement.setAttribute("data-theme", "light");
            document.body.classList.remove("dark");
            lightIcon.classList.remove("hidden");
            darkIcon.classList.add("hidden");
            systemIcon.classList.add("hidden");
        }

        // Update localStorage with a simplified state for toggling
        if (theme !== "system") {
            localStorage.setItem("theme", theme);
        } else {
            localStorage.setItem("theme", "system");
            systemIcon.classList.remove("hidden");
            lightIcon.classList.add("hidden");
            darkIcon.classList.add("hidden");
        }
    };

    /**
     * Initializes the theme based on local storage or system preference.
     */
    const initializeTheme = () => {
        // 1. Check for user's stored preference
        const storedTheme = localStorage.getItem("theme");

        if (storedTheme) {
            if (storedTheme === "dark" || storedTheme === "light") {
                setTheme(storedTheme);
                return;
            }
        }

        // 2. If no explicit preference, check system preference
        if (prefersDarkMode()) {
            setTheme("system");
        } else {
            setTheme("light");
        }
    };
    // --- 2. Toggle Handler ---

    const toggleTheme = () => {
        // Check current state first to decide what to switch *to*
        let currentTheme =
            localStorage.getItem("theme") ||
            (prefersDarkMode() ? "system" : "light");
        let newTheme;
        if (currentTheme === "dark") {
            newTheme = "light";
        } else if (currentTheme === "light") {
            // Switch to system mode instead of assuming the opposite
            newTheme = "system";
        } else {
            // currentTheme === 'system'
            // Switch to explicitly dark mode for demonstration, or use 'light'
            newTheme = "dark";
        }

        // Set the new theme, which updates both the DOM and localStorage
        setTheme(newTheme);

        // Optional: If switching *to* system mode, you might want to re-sync on OS change.
        // For simplistic toggle, this is fine.
    };
    // --- 3. Event Listeners ---
    // Initialize on load
    initializeTheme();

    // Attach the toggle function to the button click
    if (toggleButton) {
        toggleButton.addEventListener("click", toggleTheme);
    }

    // OPTIONAL: Listen for system preference changes (e.g., user toggles dark mode in OS settings)
    window.matchMedia("(prefers-color-scheme: dark)").addListener((e) => {
        // Only re-sync if the user hasn't explicitly overridden the system setting
        if (
            !localStorage.getItem("theme") ||
            localStorage.getItem("theme") === "system"
        ) {
            setTheme("system");
        }
    });
});
