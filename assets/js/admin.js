/* AKSOY GROUP — Admin JS */

// Modal toggle
function toggleModal(id, show) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('show', show !== undefined ? show : !el.classList.contains('show'));
    document.body.style.overflow = el.classList.contains('show') ? 'hidden' : '';
}

// Backdrop click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Confirm helper
function confirmAction(message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
    return false;
}

// Auto-slugify
document.addEventListener('input', (e) => {
    const t = e.target;
    if (t.dataset.autoSlug) {
        const target = document.querySelector(t.dataset.autoSlug);
        if (target && !target.dataset.touched) {
            target.value = t.value.toLowerCase()
                .replace(/ı/g, 'i').replace(/ğ/g, 'g').replace(/ü/g, 'u')
                .replace(/ş/g, 's').replace(/ö/g, 'o').replace(/ç/g, 'c')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }
    }
    if (t.dataset.touchOnInput !== undefined) t.dataset.touched = 1;
});

// Auto-hide flash messages
document.querySelectorAll('.alert').forEach(a => {
    if (a.classList.contains('success')) {
        setTimeout(() => { a.style.transition = 'opacity .4s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 400); }, 4000);
    }
});

// Image preview
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    input.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const preview = document.querySelector(input.dataset.preview);
        if (preview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = ev => { preview.src = ev.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(file);
        }
    });
});
