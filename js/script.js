/*
  Adjusted script.js
  It only contains INTERACTIVITY.
  Component loading is handled by componentLoader.js.
*/

// --- REMOVED the loadComponent function from this file ---

// --- Wait for the page to be fully loaded before running script ---
document.addEventListener("DOMContentLoaded", () => {
    
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

    
    /*
      NEW SCRIPT: Tab Switching (used in Applicants page between 'summary' and 'application list')
    */
    const tabNav = document.querySelector('.tab-nav');

    if (tabNav) {
        const tabLinks = tabNav.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabNav.addEventListener('click', (e) => {
            const clickedLink = e.target.closest('.tab-link');
            
            if (!clickedLink) return; // Exit if they clicked outside a link
            
            e.preventDefault(); // Stop the URL from changing

            // 1. Deactivate all links and content
            tabLinks.forEach(link => link.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // 2. Activate the clicked link
            clickedLink.classList.add('active');

            // 3. Activate the corresponding content
            const tabId = clickedLink.dataset.tab;
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    }

});