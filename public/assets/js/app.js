/**
 * Global App Script for Framework AI
 */

/**
 * Show premium loading overlay using SweetAlert2
 * 
 * @param {string} title 
 * @param {string} text 
 */
window.showLoading = function (title = 'Memproses...', text = 'Mohon tunggu sebentar') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showDenyButton: false,
            showCancelButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    } else {
        console.warn('SweetAlert2 is not loaded. Fallback loading applied.');
    }
};

/**
 * Hide active loading overlay
 */
window.hideLoading = function () {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
};

// Automatic Form Submit Loading Trigger to prevent double submits
document.addEventListener('DOMContentLoaded', () => {
    // Intercept form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            // Check if native HTML5 validation passes before showing overlay
            if (form.checkValidity()) {
                window.showLoading('Menyimpan Data...', 'Sedang memproses permintaan Anda');
            }
        });
    });

    // Axios CSRF Token Injection setup if axios is loaded
    if (typeof axios !== 'undefined') {
        const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
        if (csrfTokenEl) {
            const token = csrfTokenEl.getAttribute('content');
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        }
    }
});
