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

export async function showErrorAlert(message, title = 'Something went wrong', options = {}) {
    const normalizedMessage = String(message || 'An unexpected error occurred.');
    const signature = `${title}:${normalizedMessage}`;
    const now = Date.now();
    const secondaryButtonText = String(options?.secondaryButtonText || '').trim();
    const onSecondaryClick = typeof options?.onSecondaryClick === 'function' ? options.onSecondaryClick : null;
    const hasSecondaryAction = secondaryButtonText !== '' && onSecondaryClick !== null;

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

    const result = await swal.fire({
        icon: 'error',
        title,
        text: normalizedMessage,
        confirmButtonText: 'OK',
        showDenyButton: hasSecondaryAction,
        denyButtonText: hasSecondaryAction ? secondaryButtonText : '',
        background: '#18181b',
        color: '#f4f4f5',
        confirmButtonColor: '#ef4444',
        denyButtonColor: '#2563eb',
    });

    if (result?.isDenied && onSecondaryClick) {
        onSecondaryClick();
    }
}

export async function showConfirmAlert({
    title = 'Are you sure?',
    text = 'Please confirm this action.',
    confirmButtonText = 'Confirm',
    cancelButtonText = 'Cancel',
    confirmButtonColor = '#ef4444',
    onConfirm = null,
} = {}) {
    const swal = await loadSweetAlertFromCdn();
    if (!swal || typeof swal.fire !== 'function') {
        if (typeof window !== 'undefined') {
            return window.confirm(`${title}\n\n${text}`);
        }
        return false;
    }

    const hasAsyncConfirm = typeof onConfirm === 'function';
    const result = await swal.fire({
        icon: 'warning',
        title: String(title || 'Are you sure?'),
        text: String(text || 'Please confirm this action.'),
        showCancelButton: true,
        confirmButtonText: String(confirmButtonText || 'Confirm'),
        cancelButtonText: String(cancelButtonText || 'Cancel'),
        showLoaderOnConfirm: hasAsyncConfirm,
        allowOutsideClick: () => !swal.isLoading(),
        preConfirm: hasAsyncConfirm
            ? async () => {
                try {
                    await onConfirm();
                    return true;
                } catch (error) {
                    const message = String(error?.message || 'Action failed. Please try again.');
                    swal.showValidationMessage(message);
                    return false;
                }
            }
            : undefined,
        background: '#18181b',
        color: '#f4f4f5',
        confirmButtonColor: String(confirmButtonColor || '#ef4444'),
        cancelButtonColor: '#3f3f46',
    });

    return Boolean(result?.isConfirmed);
}
