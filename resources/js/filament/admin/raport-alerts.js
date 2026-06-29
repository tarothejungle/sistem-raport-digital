document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const action = form.getAttribute('action') ?? '';

    if (
        !action.includes('/logout')
        || form.dataset.logoutConfirmed === 'true'
        || typeof window.Swal === 'undefined'
    ) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const result = await window.Swal.fire({
        title: 'Keluar dari akun?',
        text: 'Sesi Anda akan diakhiri. Anda perlu masuk kembali untuk melanjutkan.',
        icon: 'warning',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: 'Keluar',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#475569',
        customClass: {
            popup: 'raport-swal-popup',
            title: 'raport-swal-title',
            htmlContainer: 'raport-swal-content',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    form.dataset.logoutConfirmed = 'true';

    HTMLFormElement.prototype.submit.call(form);
}, true);