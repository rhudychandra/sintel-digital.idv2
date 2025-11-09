// Login functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in on protected pages
    const currentPage = window.location.pathname.split('/').pop();
    const protectedPages = ['dashboard.html', 'performance-cluster.html'];
    
    if (protectedPages.includes(currentPage)) {
        const isLoggedIn = sessionStorage.getItem('isLoggedIn');
        if (!isLoggedIn) {
            window.location.href = 'index.html';
        }
    }
    
    // Login form handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            
            // Simple authentication (username: admin, password: admin)
            if (username === 'admin' && password === 'admin') {
                sessionStorage.setItem('isLoggedIn', 'true');
                sessionStorage.setItem('username', username);
                window.location.href = 'dashboard.html';
            } else {
                errorMessage.textContent = 'Username atau password salah!';
                errorMessage.style.display = 'block';
                
                // Clear error message after 3 seconds
                setTimeout(() => {
                    errorMessage.textContent = '';
                    errorMessage.style.display = 'none';
                }, 3000);
            }
        });
    }
    
    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            sessionStorage.removeItem('isLoggedIn');
            sessionStorage.removeItem('username');
            window.location.href = 'index.html';
        });
    }
    
    // Add animation to hexagon menus
    const hexagonMenus = document.querySelectorAll('.hexagon-menu');
    hexagonMenus.forEach((menu, index) => {
        menu.style.animation = `fadeIn 0.5s ease-in ${index * 0.2}s both`;
    });
    
    // Add animation to rounded menus
    const roundedMenus = document.querySelectorAll('.rounded-menu-item');
    roundedMenus.forEach((menu, index) => {
        menu.style.animation = `fadeIn 0.5s ease-in ${index * 0.15}s both`;
    });
});

// Prevent back button after logout
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});
