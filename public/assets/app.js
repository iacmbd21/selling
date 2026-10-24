document.querySelector('[data-nav-toggle]')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    const open = document.querySelector('.topbar')?.classList.toggle('nav-open');
    if (button instanceof HTMLButtonElement) {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
});

document.getElementById('add-line')?.addEventListener('click', () => {
    const wrap = document.getElementById('lines');
    const line = wrap?.querySelector('.line');
    if (!wrap || !line) {
        return;
    }
    const copy = line.cloneNode(true);
    const index = wrap.querySelectorAll('.line').length;
    copy.querySelectorAll('select, input').forEach((field) => {
        field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
        if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
        }
        if (field.type === 'number') {
            field.value = '1';
        }
    });
    wrap.appendChild(copy);
});

document.querySelectorAll('[data-open-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById(button.getAttribute('data-open-modal') || '');
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            dialog.showModal();
        }
    });
});

document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = button.closest('dialog');
        if (dialog instanceof HTMLDialogElement) {
            dialog.close();
        }
    });
});

document.querySelectorAll('dialog.product-dialog, dialog.zoom-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

document.querySelectorAll('dialog[data-open]').forEach((dialog) => {
    if (dialog instanceof HTMLDialogElement && !dialog.open) {
        dialog.showModal();
    }
});

document.querySelectorAll('input[type="file"][multiple]').forEach((input) => {
    input.addEventListener('change', () => {
        const note = input.closest('label')?.querySelector('.file-picks');
        if (!(note instanceof HTMLElement) || !(input instanceof HTMLInputElement)) {
            return;
        }
        const names = Array.from(input.files || []).map((file) => file.name);
        note.textContent = names.join(', ');
    });
});

const zoomDialog = document.getElementById('image-zoom');
const zoomStage = zoomDialog?.querySelector('[data-zoom-stage]');
const zoomImage = zoomStage?.querySelector('img');
const zoomCaption = zoomDialog?.querySelector('.zoom-caption');
let zoomScale = 1.8;

function setZoom(scale) {
    zoomScale = Math.min(4, Math.max(1, scale));
    if (zoomImage instanceof HTMLImageElement) {
        zoomImage.style.transform = 'scale(' + zoomScale + ')';
    }
}

function openZoom(src, alt) {
    if (!(zoomDialog instanceof HTMLDialogElement) || !(zoomImage instanceof HTMLImageElement)) {
        return;
    }
    zoomImage.src = src;
    zoomImage.alt = alt || '';
    if (zoomCaption) {
        zoomCaption.textContent = alt || 'Product photo';
    }
    zoomImage.style.transformOrigin = 'center center';
    setZoom(1.8);
    if (!zoomDialog.open) {
        zoomDialog.showModal();
    }
}

document.querySelectorAll('[data-zoom-src]').forEach((control) => {
    control.addEventListener('click', () => {
        const src = control.getAttribute('data-zoom-src');
        if (src) {
            openZoom(src, control.getAttribute('data-zoom-alt') || '');
        }
    });
});

document.querySelectorAll('[data-gallery-src]').forEach((thumb) => {
    thumb.addEventListener('click', () => {
        const src = thumb.getAttribute('data-gallery-src');
        const frame = document.querySelector('.zoom-frame[data-zoom-src]');
        const image = frame?.querySelector('img');
        if (!src || !frame || !(image instanceof HTMLImageElement)) {
            return;
        }
        frame.setAttribute('data-zoom-src', src);
        image.src = src;
        document.querySelectorAll('[data-gallery-src]').forEach((item) => item.removeAttribute('aria-current'));
        thumb.setAttribute('aria-current', 'true');
    });
});

zoomStage?.addEventListener('mousemove', (event) => {
    if (!(zoomStage instanceof HTMLElement) || !(zoomImage instanceof HTMLImageElement)) {
        return;
    }
    const rect = zoomStage.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    zoomImage.style.transformOrigin = x + '% ' + y + '%';
});

document.querySelector('[data-zoom-in]')?.addEventListener('click', () => setZoom(zoomScale + 0.4));
document.querySelector('[data-zoom-out]')?.addEventListener('click', () => setZoom(zoomScale - 0.4));

document.querySelectorAll('[data-copy-link]').forEach((button) => {
    button.addEventListener('click', async () => {
        const url = button.getAttribute('data-copy-link') || window.location.href;
        try {
            await navigator.clipboard.writeText(url);
        } catch (error) {
            const field = document.createElement('textarea');
            field.value = url;
            document.body.appendChild(field);
            field.select();
            document.execCommand('copy');
            field.remove();
        }
        button.setAttribute('data-copied', 'true');
        const note = button.parentElement?.querySelector('[data-share-note]');
        if (note instanceof HTMLElement) {
            note.hidden = false;
        }
        window.setTimeout(() => {
            button.removeAttribute('data-copied');
            if (note instanceof HTMLElement) {
                note.hidden = true;
            }
        }, 1600);
    });
});

document.querySelectorAll('[data-native-share]').forEach((button) => {
    if (typeof navigator.share !== 'function') {
        return;
    }
    button.hidden = false;
    button.addEventListener('click', () => {
        const row = button.closest('[data-share-url]');
        navigator.share({
            title: row?.getAttribute('data-share-title') || document.title,
            url: row?.getAttribute('data-share-url') || window.location.href,
        }).catch(() => {});
    });
});

document.querySelectorAll('[data-qty-step]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('form');
        const input = form?.querySelector('input[name="quantity"]');
        if (!(input instanceof HTMLInputElement)) {
            return;
        }
        const next = Math.min(20, Math.max(0, Number(input.value || 0) + Number(button.getAttribute('data-qty-step'))));
        input.value = String(next);
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        }
    });
});

document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-submit]');
        if (button instanceof HTMLButtonElement) {
            button.disabled = true;
        }
    });
});
