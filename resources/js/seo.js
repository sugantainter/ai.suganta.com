function initPublicNav() {
    const toggle = document.getElementById('public-nav-toggle');
    const drawer = document.getElementById('public-nav-drawer');
    if (!toggle || !drawer) {
        return;
    }

    const setOpen = (open) => {
        drawer.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('public-nav-open', open);
    };

    toggle.addEventListener('click', () => {
        setOpen(drawer.hidden);
    });

    drawer.querySelectorAll('[data-nav-close]').forEach((el) => {
        el.addEventListener('click', () => setOpen(false));
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !drawer.hidden) {
            setOpen(false);
        }
    });
}

function initFaqAccordion() {
    const faqItems = document.querySelectorAll('.faq-item');
    if (!faqItems.length) {
        return;
    }

    faqItems.forEach((item) => {
        const trigger = item.querySelector('.faq-trigger');
        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', () => {
            const isActive = item.classList.contains('active');

            faqItems.forEach((otherItem) => {
                otherItem.classList.remove('active');
                const otherTrigger = otherItem.querySelector('.faq-trigger');
                otherTrigger?.setAttribute('aria-expanded', 'false');
            });

            if (!isActive) {
                item.classList.add('active');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPublicNav();
    initFaqAccordion();
});
