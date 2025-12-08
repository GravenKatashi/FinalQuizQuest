document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("form");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");

    // Tooltip for password
    let tooltip;
    passwordInput.addEventListener("focus", () => {
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

    // Email validation (must contain @)
    function validateEmail(email) {
        return email.includes("@");
    }

    emailInput.addEventListener("input", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
        } else {
            emailInput.classList.remove("invalid");
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

    // Required field validation for other inputs (on blur)
    const requiredInputs = form.querySelectorAll("input:not([type='password']):not(#email)");

    requiredInputs.forEach(input => {
        input.addEventListener("blur", () => {
            if (input.value.trim() === "") {
                input.classList.add("invalid");
            } else {
                input.classList.remove("invalid");
            }
        });
    });

    // Form submission check
    form.addEventListener("submit", (e) => {
        let valid = true;

        // Check email
        if (!validateEmail(emailInput.value)) valid = false;

        // Check password
        if (passwordInput.value.length < 8) valid = false;

        // Check required fields
        requiredInputs.forEach(input => {
            if (input.value.trim() === "") {
                input.classList.add("invalid");
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            // Focus on the first invalid field
            const firstInvalid = form.querySelector(".invalid");
            if (firstInvalid) firstInvalid.focus();
        }
    });

});