const loadComponent = (selector, path) => {
    fetch(path)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to load component: ${path}`);
            }
            return response.text();
        })
        .then(data => {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = data;
            } else {
                console.warn(`Selector "${selector}" not found for component "${path}".`);
            }
        })
        .catch(error => console.error(error));
};

document.addEventListener("DOMContentLoaded", () => {
    
    // --- Load all our reusable components ---
    loadComponent("#header-placeholder", "components/header.html");

    // test to make sure our script.js is loaded
    console.log("TAO Portal script.js is loaded!");


    /* Show/Hide Password (Image Toggle Version) */
    
    // icon images
    const eyeOpenIcon = "assets/eye-open.png";
    const eyeSlashIcon = "assets/eye-slash.png";

    const togglePassword = document.querySelector('#toggle-password');
    const password = document.querySelector('#password');

    // to check if the elements exist on the page
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
    }

});