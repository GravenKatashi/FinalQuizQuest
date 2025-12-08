document.addEventListener("DOMContentLoaded", () => {
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const form = document.querySelector("form");

    // Email validation
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    emailInput.addEventListener("input", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
        } else {
            emailInput.classList.remove("invalid");
        }
    });

    // Password tooltip
    let tooltip;
    passwordInput.addEventListener("focus", (e) => {
        tooltip = document.createElement("div");
        tooltip.className = "tooltip-popup";
        tooltip.textContent = "Minimum of 8 characters";
        document.body.appendChild(tooltip);

        const rect = passwordInput.getBoundingClientRect();
        tooltip.style.top = `${rect.top - 30 + window.scrollY}px`;
        tooltip.style.left = `${rect.left + 5}px`;
    });

    passwordInput.addEventListener("blur", () => {
        if (tooltip) {
            tooltip.remove();
            tooltip = null;
        }
    });

    // Password validation
    passwordInput.addEventListener("input", () => {
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add("invalid");
        } else {
            passwordInput.classList.remove("invalid");
        }
    });

    // Form submission check
    form.addEventListener("submit", (e) => {
        let valid = true;
        if (!validateEmail(emailInput.value)) valid = false;
        if (passwordInput.value.length < 8) valid = false;

        if (!valid) {
            e.preventDefault();
            if (!validateEmail(emailInput.value)) emailInput.focus();
            else passwordInput.focus();
        }
    });
});
