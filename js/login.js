document.addEventListener("DOMContentLoaded", () => {

    // --- Get all the elements we need to work with ---
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('email'); // Get the email input
    const rememberMeCheckbox = document.getElementById('remember-me'); // Get the checkbox
    const errorMessageDiv = document.getElementById('login-error-message');
    const togglePassword = document.querySelector('#toggle-password');
    const passwordInput = document.querySelector('#password');
    const eyeOpenIcon = "assets/eye-open.png";
    const eyeSlashIcon = "assets/eye-slash.png";

        // Check if a "remembered" email exists in localStorage
    const rememberedEmail = localStorage.getItem('rememberedEmail');
    if (rememberedEmail) {
        emailInput.value = rememberedEmail; // Fill in the email field
        rememberMeCheckbox.checked = true; // Check the box
    }


    // --- Logic for the "Show/Hide Password" eye icon ---
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.src = (type === 'password') ? eyeSlashIcon : eyeOpenIcon;
        });
    }

    // --- Logic for handling the form submission ---
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Stop the form from reloading the page

            // **MODIFICATION 1: Hide any previous error message when the user tries again.**
            errorMessageDiv.classList.remove('visible');
            errorMessageDiv.textContent = ''; // Clear old text

            const email = emailInput.value;
            const password = passwordInput.value;

            if (rememberMeCheckbox.checked) {
                // If the box is checked, save the email to localStorage
                localStorage.setItem('rememberedEmail', email);
            } else {
                // If the box is not checked, remove any previously saved email
                localStorage.removeItem('rememberedEmail');
            }

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, password: password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'Dashboard.php';
                } else {
                    errorMessageDiv.textContent = data.message;
                    errorMessageDiv.classList.add('visible');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                errorMessageDiv.textContent = 'A server or network error occurred.';
                errorMessageDiv.classList.add('visible');
            });
        });
    }
});