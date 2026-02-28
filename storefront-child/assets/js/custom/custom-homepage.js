document.addEventListener('DOMContentLoaded', () => {
    const top = document.getElementById('hero-card-top');
    const body = document.querySelector('.rcl24-hero-card-body');

    if (!top || !body) return;

    const isMobile = () => window.matchMedia('(max-width: 600px)').matches;

    // initial state: collapsed on mobile, open on desktop
    const sync = () => {
        if (isMobile()) {
            body.classList.remove('is-open');
            top.classList.remove('is-open');
            top.setAttribute('role', 'button');
            top.setAttribute('tabindex', '0');
            top.setAttribute('aria-expanded', 'false');
        } else {
            body.classList.add('is-open');
            top.classList.add('is-open');
            top.removeAttribute('role');
            top.removeAttribute('tabindex');
            top.removeAttribute('aria-expanded');
        }
    };

    const toggle = () => {
        if (!isMobile()) return;
        const open = body.classList.toggle('is-open');
        top.classList.toggle('is-open', open);
        top.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    top.addEventListener('click', toggle);
    top.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggle();
        }
    });

    window.addEventListener('resize', sync);
    sync();
});