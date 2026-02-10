// Check authentication on protected pages
function checkAuth() {
    if (!isAuthenticated()) {
        window.location.href = 'index.html';
        return false;
    }

    const user = getUser();
    if (user && user.role !== 'admin') {
        alert('Access denied. Admin privileges required.');
        logout();
        return false;
    }

    return true;
}

// Display user info in navbar
function displayUserInfo() {
    const user = getUser();
    if (user) {
        const userNameElement = document.getElementById('userName');
        if (userNameElement) {
            userNameElement.textContent = user.name;
        }

        const userEmailElement = document.getElementById('userEmail');
        if (userEmailElement) {
            userEmailElement.textContent = user.email;
        }
    }
}

// Logout handler
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        logout();
    }
}
