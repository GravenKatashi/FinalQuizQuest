document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("form");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = form.querySelector("input[name='confirm_password']");

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
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add("invalid");
        } else {
            passwordInput.classList.remove("invalid");
        }
    });

    confirmPasswordInput.addEventListener("blur", () => {
        if (confirmPasswordInput.value.length < 8) {
            confirmPasswordInput.classList.add("invalid");
        } else {
            confirmPasswordInput.classList.remove("invalid");
        }
    });

    // Email validation
    function validateEmail(email) {
        return email.includes("@");
    }

    emailInput.addEventListener("blur", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
        } else {
            emailInput.classList.remove("invalid");
        }
    });

    emailInput.addEventListener("input", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
        } else {
            emailInput.classList.remove("invalid");
        }
    });

    // Required inputs (username, full name, school affiliation)
    const requiredInputs = form.querySelectorAll("input:not([type='password']):not(#email)");

    requiredInputs.forEach(input => {
        input.addEventListener("blur", () => {
            if (input.value.trim().length === 0) {
                input.classList.add("invalid");
            } else {
                input.classList.remove("invalid");
            }
        });

        input.addEventListener("input", () => {
            if (input.value.trim().length > 0) {
                input.classList.remove("invalid");
            }
        });
    });

    // Password & Confirm Password input listeners for real-time removal
    passwordInput.addEventListener("input", () => {
        if (passwordInput.value.length >= 8) {
            passwordInput.classList.remove("invalid");
        }
    });

    confirmPasswordInput.addEventListener("input", () => {
        if (confirmPasswordInput.value.length >= 8) {
            confirmPasswordInput.classList.remove("invalid");
        }
    });

    // Form submission
    form.addEventListener("submit", (e) => {
        let valid = true;

        // Check email
        if (!validateEmail(emailInput.value)) valid = false;

        // Check password
        if (passwordInput.value.length < 8) valid = false;

        // Check confirm password
        if (confirmPasswordInput.value.length < 8) valid = false;

        // Check required inputs
        requiredInputs.forEach(input => {
            if (input.value.trim().length === 0) {
                input.classList.add("invalid");
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            const firstInvalid = form.querySelector(".invalid");
            if (firstInvalid) firstInvalid.focus();
        }
    });

});
