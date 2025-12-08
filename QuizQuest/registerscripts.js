// ===== registerscripts.js =====
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const emailMsg = document.getElementById('emailMsg');
const passwordMsg = document.getElementById('passwordMsg');
const form = document.querySelector('form');

function validateEmail() {
    const email = emailInput.value.trim();
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!pattern.test(email)) {
        emailInput.classList.add('invalid');
        emailMsg.textContent = 'Please enter a valid email.';
        return false;
    } else {
        emailInput.classList.remove('invalid');
        emailMsg.textContent = '';
        return true;
    }
}

function validatePassword() {
    const pwd = passwordInput.value;
    if (pwd.length < 8) {
        passwordInput.classList.add('invalid');
        passwordMsg.textContent = 'Password must be at least 8 characters.';
        return false;
    } else {
        passwordInput.classList.remove('invalid');
        passwordMsg.textContent = 'Minimum 8 characters';
        return true;
    }
}

// Real-time validation
emailInput.addEventListener('input', validateEmail);
passwordInput.addEventListener('input', validatePassword);

// Prevent form submission if invalid
form.addEventListener('submit', function(e) {
    const emailValid = validateEmail();
    const pwdValid = validatePassword();
    if (!emailValid || !pwdValid) {
        e.preventDefault();
    }
});
