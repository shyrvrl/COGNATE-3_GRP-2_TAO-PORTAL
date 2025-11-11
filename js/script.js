const loadComponent = (selector, path) => {
    fetch(path)
        .then(response => {
            if (!response.ok) {
                console.warn(`Component at ${path} not found or failed to load.`);
                return null;
            }
            return response.text();
        })
        .then(data => {
            if (data === null) return; // Stop if the fetch failed
            
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = data;
            } else {
                // This is safe. e.g., #app-sidebar isn't on the login page.
            }
        })
        .catch(error => console.warn(error));
};

document.addEventListener("DOMContentLoaded", () => {
    
    // --- 1. LOAD ALL REUSABLE COMPONENTS ---
    loadComponent("#app-header", "components/header.html");
    loadComponent("#app-sidebar", "components/sidebar.html");

    
    // --- 2. SET ACTIVE SIDEBAR LINK (NEW SCRIPT) ---
    // This script will find the correct link to highlight
    const allLinks = document.querySelectorAll('.nav-item');
    allLinks.forEach(item => {
        item.classList.remove('active');
    });

    if (document.body.classList.contains('page-dashboard')) {
        document.querySelector('#nav-dashboard')?.classList.add('active');
    } else if (document.body.classList.contains('page-applicants')) {
        document.querySelector('#nav-applicants')?.classList.add('active');
    } else if (document.body.classList.contains('page-interview')) {
        document.querySelector('#nav-interview')?.classList.add('active');
    }

    
    // --- 3. PASSWORD TOGGLE SCRIPT (YOUR EXISTING CODE) ---
    const eyeOpenIcon = "assets/eye-open.png";
    const eyeSlashIcon = "assets/eye-slash.png";
    const togglePassword = document.querySelector('#toggle-password');
    const password = document.querySelector('#password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'password') {
                togglePassword.src = eyeOpenIcon;
                togglePassword.alt = "Show password";
            } else {
                togglePassword.src = eyeSlashIcon;
                togglePassword.alt = "Hide password";
            }
        });
    }

    
    // --- 4. TAB SWITCHING SCRIPT (MOVED INSIDE) ---
    const tabNav = document.querySelector('.tab-nav');

    if (tabNav) {
        const tabLinks = tabNav.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabNav.addEventListener('click', (e) => {
            const clickedLink = e.target.closest('.tab-link');
            if (!clickedLink) return;
            
            e.preventDefault(); 

            tabLinks.forEach(link => link.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            clickedLink.classList.add('active');
            
            const tabId = clickedLink.dataset.tab;
            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    }

    // --- 5. FILTER MODAL POPUP (NEW SCRIPT) ---
    const filterForm = document.querySelector('#filter-form');
    const modal = document.querySelector('#filter-modal');
    const closeModalBtn = document.querySelector('#close-modal-btn');

    if (filterForm && modal && closeModalBtn) {
        
        // Show the modal when "Apply Filters" is clicked
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Stop the page from reloading
            // (In a real app, fetch new data here)
            modal.classList.remove('hidden');
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }

});