// js/forgot-password.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgot-password-form');
    const emailInput = document.getElementById('email');
    const messageDiv = document.getElementById('response-message');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // UI Reset
            messageDiv.style.display = 'none';
            messageDiv.textContent = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';

            const email = emailInput.value;

            fetch('api/request_password_reset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.textContent = data.message;
                messageDiv.style.display = 'block'; // Make visible
                
                if (data.success) {
                    // Success Style (Green)
                    messageDiv.style.backgroundColor = '#D4EDDA'; 
                    messageDiv.style.borderColor = '#C3E6CB';
                    messageDiv.style.color = '#155724';
                    
                    // Optional: clear input on success
                    emailInput.value = '';
                } else {
                    // Error Style (Red) - Account not found
                    messageDiv.style.backgroundColor = '#FED7D7'; 
                    messageDiv.style.borderColor = '#F56565';
                    messageDiv.style.color = '#9B2C2C';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.textContent = 'A network error occurred. Please try again.';
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#FED7D7';
                messageDiv.style.color = '#9B2C2C';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Reset Link';
            });
        });
    }
});