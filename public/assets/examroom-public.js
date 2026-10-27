'use strict';
const confirmation = document.getElementById('confirmation');
if (confirmation) {
    const submit = confirmation.form.querySelector('button[type="submit"]');
    const sync = () => { submit.disabled = confirmation.value !== 'HAPUS'; };
    confirmation.addEventListener('input', sync);
    sync();
}
document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
    const submit = form.querySelector('button[type="submit"]');
    if (submit) { submit.disabled = true; submit.textContent = 'Sedang memproses…'; }
}));
