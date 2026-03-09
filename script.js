// Display error message from URL parameter or session
document.addEventListener('DOMContentLoaded', function() {
    // Get error message from URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.textContent = decodeURIComponent(error);
            errorDiv.classList.add('show');
        }
    }
    
    // Add form validation
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = 'Please fill in all fields';
                errorDiv.classList.add('show');
            }
        });
    }
});
