document.addEventListener("DOMContentLoaded", () => {
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const emailMsg = document.getElementById("emailMsg");
    const passwordMsg = document.getElementById("passwordMsg");
    const form = document.querySelector("form");

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Email validation
    emailInput.addEventListener("input", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
            emailMsg.textContent = "Invalid email format";
        } else {
            emailInput.classList.remove("invalid");
            emailMsg.textContent = "";
        }
    });

    // Show password helper on focus
    passwordInput.addEventListener("focus", () => {
        passwordMsg.style.display = "block";
    });

    passwordInput.addEventListener("blur", () => {
        passwordMsg.style.display = "none";
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
            e.preventDefault(); // Prevent submission
            if (!validateEmail(emailInput.value)) emailInput.focus();
            else passwordInput.focus();
        }
    });
});
