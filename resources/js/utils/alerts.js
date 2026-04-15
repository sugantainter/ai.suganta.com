import Swal from 'sweetalert2';

let lastErrorSignature = '';
let lastErrorAt = 0;

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

    await Swal.fire({
        icon: 'error',
        title,
        text: normalizedMessage,
        confirmButtonText: 'OK',
        background: '#18181b',
        color: '#f4f4f5',
        confirmButtonColor: '#ef4444',
    });
}
