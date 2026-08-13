import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Set CSRF token for all requests
const updateCSRFToken = () => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        console.log('CSRF token updated:', token.content.substring(0, 10) + '...');
        return token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
        return null;
    }
};

// Initial CSRF token setup
updateCSRFToken();

// Handle CSRF token mismatch by refreshing the page
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            console.warn('CSRF token mismatch detected (419 error). Refreshing page...');
            // Refresh the page to get a new CSRF token
            window.location.reload();
            return;
        }
        return Promise.reject(error);
    }
);

// Refresh CSRF token on Inertia page visits
document.addEventListener('inertia:page-loaded', () => {
    updateCSRFToken();
});

// Export function for manual token refresh if needed
window.updateCSRFToken = updateCSRFToken;
