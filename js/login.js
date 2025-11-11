// js/login.js - Handles logic specific to the LoginPage (Password Toggle)

document.addEventListener("DOMContentLoaded", () => {
    
    // Icon images (assuming correct paths relative to the HTML page)
    const eyeOpenIcon = "assets/eye-open.png";
    const eyeSlashIcon = "assets/eye-slash.png";

    const togglePassword = document.querySelector('#toggle-password');
    const password = document.querySelector('#password');

    // Only run this logic if the elements exist on the page
    if (togglePassword && password) {
        
        togglePassword.addEventListener('click', function () {
            
            // Toggle the type of the password field
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the icon image source
            if (type === 'password') {
                togglePassword.src = eyeOpenIcon;
                togglePassword.alt = "Show password";
            } else {
                togglePassword.src = eyeSlashIcon;
                togglePassword.alt = "Hide password";
            }
        });
        
        console.log("Login page password toggle active.");
    }
});