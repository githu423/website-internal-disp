/**
 * Batas awal di browser; backend tetap memvalidasi setiap permintaan unggah.
 * Elemen input bertanda data-max-file-mb (nilai dalam MB).
 */
document.querySelectorAll('input[type="file"][data-max-file-mb]').forEach((input) => {
    // ===== PERIKSA APAKAH ADA FILE YANG MELEBIHI BATAS =====
    const validate = () => {
        const limit = Number(input.dataset.maxFileMb);
        const tooLarge = Array.from(input.files || []).some((file) => file.size > limit * 1024 * 1024);
        input.setCustomValidity(tooLarge ? `Ukuran dokumen maksimal ${limit} MB.` : '');
    };
    input.addEventListener('change', () => {
        validate();
        if (!input.validity.valid) input.reportValidity();
    });
    input.form?.addEventListener('reset', () => input.setCustomValidity(''));
    input.form?.addEventListener('submit', (event) => {
        validate();
        if (!input.validity.valid) {
            event.preventDefault();
            input.reportValidity();
        }
    });
});
