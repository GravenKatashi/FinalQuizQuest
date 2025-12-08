document.addEventListener("DOMContentLoaded", () => {
    const emailInput = document.getElementById("email");
    const emailMsg = document.getElementById("emailMsg");
    const passwordInput = document.getElementById("password");
    const passwordMsg = document.getElementById("passwordMsg");
    const form = document.querySelector("form");

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    emailInput.addEventListener("input", () => {
        if (!validateEmail(emailInput.value)) {
            emailInput.classList.add("invalid");
            emailMsg.textContent = "Invalid email address";
        } else {
            emailInput.classList.remove("invalid");
            emailMsg.textContent = "";
        }
    });

    passwordInput.addEventListener("input", () => {
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add("invalid");
            passwordMsg.style.display = "block";
        } else {
            passwordInput.classList.remove("invalid");
            passwordMsg.style.display = "none";
        }
    });

    form.addEventListener("submit", (e) => {
        if (!validateEmail(emailInput.value) || passwordInput.value.length < 8) {
            e.preventDefault();
            emailInput.classList.add("invalid");
            passwordInput.classList.add("invalid");
        }
    });
});
