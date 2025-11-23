// js/forgot-password.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgot-password-form');
    const emailInput = document.getElementById('email');
    const messageDiv = document.getElementById('response-message');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            messageDiv.classList.remove('visible');
            messageDiv.textContent = '';

            const email = emailInput.value;

            fetch('api/request_password_reset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.textContent = data.message;
                
                // Style the message based on success or failure
                if (data.success) {
                    messageDiv.style.backgroundColor = '#D4EDDA'; // Light green
                    messageDiv.style.borderColor = '#C3E6CB';
                    messageDiv.style.color = '#155724';
                } else {
                    messageDiv.style.backgroundColor = '#FED7D7'; // Light red
                    messageDiv.style.borderColor = '#F56565';
                    messageDiv.style.color = '#9B2C2C';
                }
                
                messageDiv.classList.add('visible');
                
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.textContent = 'A network error occurred.';
                messageDiv.classList.add('visible');
            });
        });
    }
});