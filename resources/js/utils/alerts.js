let lastErrorSignature = '';
let lastErrorAt = 0;
let swalLoaderPromise = null;

function loadSweetAlertFromCdn() {
    if (typeof window === 'undefined') {
        return Promise.resolve(null);
    }

    if (window.Swal) {
        return Promise.resolve(window.Swal);
    }

    if (swalLoaderPromise) {
        return swalLoaderPromise;
    }

    swalLoaderPromise = new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js';
        script.async = true;
        script.onload = () => resolve(window.Swal || null);
        script.onerror = () => resolve(null);
        document.head.appendChild(script);
    });

    return swalLoaderPromise;
}

export async function showErrorAlert(message, title = 'Something went wrong') {
    const normalizedMessage = String(message || 'An unexpected error occurred.');
    const signature = `${title}:${normalizedMessage}`;
    const now = Date.now();

    // Prevent rapid duplicate popups for the same error.
    if (lastErrorSignature === signature && now - lastErrorAt < 1200) {
        return;
    }

    lastErrorSignature = signature;
    lastErrorAt = now;

    const swal = await loadSweetAlertFromCdn();
    if (!swal || typeof swal.fire !== 'function') {
        if (typeof window !== 'undefined') {
            window.alert(`${title}\n\n${normalizedMessage}`);
        }
        return;
    }

    await swal.fire({
        icon: 'error',
        title,
        text: normalizedMessage,
        confirmButtonText: 'OK',
        background: '#18181b',
        color: '#f4f4f5',
        confirmButtonColor: '#ef4444',
    });
}
