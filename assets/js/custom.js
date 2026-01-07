// custom.js - Restaurant System Basic Package

// Format currency to Thai Baht
function formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB'
    }).format(amount);
}

// Format date and time
function formatDateTime(datetime) {
    return new Date(datetime).toLocaleString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Show loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '1050';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast bg-${type} text-white`;
    toast.innerHTML = `
        <div class="toast-body">
            ${message}
        </div>
    `;

    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

// Validate form input
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Handle API errors
function handleApiError(error) {
    console.error('API Error:', error);
    let errorMessage = 'An error occurred. Please try again.';
    
    if (error.response) {
        errorMessage = error.response.data.message || errorMessage;
    }

    alert(errorMessage);
}

// Get URL parameters
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const result = {};
    for (const [key, value] of params) {
        result[key] = value;
    }
    return result;
}

// Check auth status
function checkAuth() {
    if (!localStorage.getItem('isLoggedIn')) {
        window.location.href = '/admin/index.html';
        return false;
    }
    return true;
}

// Update table status colors
function getStatusClass(status) {
    const statusClasses = {
        'available': 'bg-success',
        'occupied': 'bg-danger',
        'pending': 'bg-warning',
        'paid': 'bg-info',
        'served': 'bg-success',
        'cancelled': 'bg-secondary'
    };
    return statusClasses[status] || 'bg-secondary';
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Smooth scroll to element
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy', 'danger');
    });
}

// Generate random ID
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Export functions (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatCurrency,
        formatDateTime,
        showLoading,
        showToast,
        validateForm,
        handleApiError,
        getUrlParams,
        checkAuth,
        getStatusClass,
        debounce,
        isInViewport,
        smoothScrollTo,
        copyToClipboard,
        generateId
    };
}