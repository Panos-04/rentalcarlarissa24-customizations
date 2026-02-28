

document.addEventListener("DOMContentLoaded", function () {
    if (window.innerWidth <= 896) {
        const observer = new MutationObserver(() => {
            const leftHeaderRow = document.querySelector(".drp-calendar.left thead tr");
            const rightNext = document.querySelector(".drp-calendar.right .next.available");

            if (leftHeaderRow && rightNext) {
                // Remove any empty <th> directly after the month
                const monthTh = leftHeaderRow.querySelector(".month");
                if (
                    monthTh &&
                    monthTh.nextElementSibling &&
                    monthTh.nextElementSibling.tagName === "TH" &&
                    !monthTh.nextElementSibling.className &&
                    !monthTh.nextElementSibling.innerHTML.trim()
                ) {
                    monthTh.nextElementSibling.remove();
                    console.log("Removed empty <th> ✅");
                }

                // Move next button if not already there
                if (!leftHeaderRow.querySelector(".next.available")) {
                    leftHeaderRow.appendChild(rightNext);
                    console.log("Moved next button ✅");
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Run only on product URLs (like http://rental-cars-larissa-24.local/product/...)
    const onProductUrl = /\/product\/.+/i.test(window.location.pathname);
    // Extra-safe: also check WP body class
    const isSingleProduct = document.body.classList.contains('single-product');

    if (!(onProductUrl && isSingleProduct)) return;

    // 🔻 your existing code here (e.g., waiver card JS)
    const radios = document.querySelectorAll('input[name="larissa_coverage_plan"]');
    if (!radios.length) return;

    const updateSelected = () => {
        document.querySelectorAll('label.larissa-coverage-option').forEach(l => l.classList.remove('is-selected'));
        const checked = document.querySelector('input[name="larissa_coverage_plan"]:checked');
        if (checked) checked.closest('label.larissa-coverage-option')?.classList.add('is-selected');
    };

    radios.forEach(r => r.addEventListener('change', updateSelected));
    updateSelected();
});


document.addEventListener('DOMContentLoaded', () => {

    const onCartURL = /\/cart\/?$/.test(window.location.pathname);
    const onCartBody = document.body.classList.contains('woocommerce-cart');
    if (!(onCartURL || onCartBody)) return;

    // -------- helpers
    const onProduct = document.body.classList.contains('single-product');
    const onCart = document.body.classList.contains('woocommerce-cart');

    // === PRODUCT PAGE: selected waiver badge + remember per product ===
    if (onProduct) {
        const pidCls = [...document.body.classList].find(c => c.startsWith('postid-'));
        const pid = pidCls ? pidCls.split('-')[1] : null;
        const radios = document.querySelectorAll('input[name="larissa_coverage_plan"]');
        const wrap = document.querySelector('.larissa-coverage-wrap');

        if (pid && radios.length && wrap) {
            // create badge
            const badge = document.createElement('div');
            badge.className = 'lc-selected-badge';
            badge.style.display = 'none';
            badge.innerHTML = '<strong>Selected waiver:</strong> <span class="lc-selected-text"></span>';
            wrap.insertBefore(badge, wrap.firstChild);

            const setBadge = () => {
                const checked = document.querySelector('input[name="larissa_coverage_plan"]:checked');
                if (!checked) { badge.style.display = 'none'; return; }
                const card = checked.closest('.larissa-coverage-option');
                const title = card?.querySelector('.lc-head')?.textContent?.trim() || '';
                const excess = card?.querySelector('.lc-excess')?.textContent?.trim() || '';
                badge.querySelector('.lc-selected-text').textContent = `${title} — ${excess}`;
                badge.style.display = 'flex';

                // remember per product
                sessionStorage.setItem(`waiver_${pid}`, JSON.stringify({ key: checked.value, title, excess }));
            };

            // restore previous choice if exists
            try {
                const saved = JSON.parse(sessionStorage.getItem(`waiver_${pid}`) || 'null');
                if (saved?.key) {
                    const r = document.querySelector(`input[name="larissa_coverage_plan"][value="${saved.key}"]`);
                    if (r) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
                }
            } catch (e) { }

            radios.forEach(r => r.addEventListener('change', setBadge));
            setBadge(); // initial
        }
    }

    // === CART PAGE: highlight "Coverage" row as a badge via JS class ===
    if (onCart) {
        document.querySelectorAll('.wc-item-meta li').forEach(li => {
            const label = li.querySelector('.wc-item-meta-label');
            if (label && /coverage/i.test(label.textContent.trim())) {
                li.classList.add('is-coverage');
            }
        });
    }
});


document.addEventListener('DOMContentLoaded', () => {
    const carsUrl =
        (window.larissaSite && larissaSite.carsUrl) ||
        '/cars/';

    const pathNoSlash = p => p.replace(/\/+$/, '');
    const onCars =
        pathNoSlash(location.pathname) === pathNoSlash(new URL(carsUrl, location.origin).pathname);

    // ---- Read item count BEFORE we remove any header nodes
    function getItemsCount() {
        try {
            const store = window.wp?.data?.select?.('wc/store/cart');
            if (store && typeof store.getCartItemsCount === 'function') {
                return store.getCartItemsCount();
            }
        } catch (e) { }
        const el =
            document.querySelector('#site-header-cart .count, .site-header-cart .count') ||
            document.querySelector('.storefront-handheld-footer-bar .cart .count');
        const n = parseInt((el?.textContent || '0').replace(/[^\d]/g, ''), 10);
        return Number.isFinite(n) ? n : 0;
    }

    // ---- Remove existing cart UI (desktop + handheld)
    function removeCartNodes() {
        document.querySelectorAll(
            '#site-header-cart li.cart, .site-header-cart li.cart, .storefront-handheld-footer-bar li.cart'
        ).forEach(el => el.remove());

        document.querySelectorAll(
            '#site-header-cart .cart-contents, .site-header-cart .cart-contents, .site-header-cart .footer-cart-contents'
        ).forEach(el => el.remove());

        // remove stray “0,00 € 0 items” text nodes if theme printed them separately
        const headerCart = document.querySelector('#site-header-cart, .site-header-cart');
        if (headerCart) {
            [...headerCart.childNodes].forEach(n => {
                if (n.nodeType === 3 && n.textContent.trim()) n.remove();
            });
        }
    }

    // ---- Add Cars CTA in desktop header <ul class="site-header-cart menu">
    function addDesktopCarsCTA() {
        const ul = document.querySelector('#site-header-cart, .site-header-cart');
        if (!ul) return;
        let li = ul.querySelector('li.lc-cars-li');
        if (!li) {
            const a_text = document.createElement('div');
            a_text.className = 'custom-car-icon';
            a_text.innerHTML = 'All Cars';
            li = document.createElement('li');
            li.className = 'menu-item lc-cars-li';
            const a = document.createElement('a');
            a.className = 'lc-cars-link';

            a.innerHTML = '<i class="fa-solid fa-car-side" aria-hidden="true"></i>';
            a.appendChild(a_text)
            li.appendChild(a);
            ul.appendChild(li);
        }
        const a = li.querySelector('a.lc-cars-link');
        if (onCars) { a.removeAttribute('href'); li.classList.add('is-disabled'); }
        else { a.href = carsUrl; li.classList.remove('is-disabled'); }
    }

    // ---- Add Cars CTA to handheld footer bar
    function addMobileCarsCTA() {
        const bar = document.querySelector(
            '.storefront-handheld-footer-bar ul.columns-3, .storefront-handheld-footer-bar ul'
        );
        if (!bar) return;

        // 🔒 hide the stock cart ASAP to avoid the flash
        const cartLi = bar.querySelector('li.cart');
        if (cartLi) cartLi.style.visibility = 'hidden';

        // ensure a single Cars item
        let li = bar.querySelector('li.cars');
        if (!li) {
            li = document.createElement('li');
            li.className = 'cars';

            const a = document.createElement('a');
            a.className = 'footer-cars-link';
            a.setAttribute('aria-label', 'Browse cars');
            a.setAttribute('title', 'Cars');
            a.innerHTML = ''; // icon only via CSS ::before

            li.appendChild(a);
            bar.appendChild(li);                 // 👈 append LAST (no insertBefore)
        }

        // link state
        const a = li.querySelector('a.footer-cars-link');
        if (onCars) { li.classList.add('is-disabled'); a.removeAttribute('href'); }
        else { li.classList.remove('is-disabled'); a.href = carsUrl; }

        // show/hide depending on cart count (and reveal cart if it has items)
        const count = getItemsCount();
        if (count === 0) {
            if (cartLi) cartLi.remove();         // remove cart completely when empty
            li.style.display = '';
        }
    }
    // ---- Run once, only when cart is empty
    if (getItemsCount() === 0) {
        removeCartNodes();       // hide old “0,00 € 0 items” + basket
        addDesktopCarsCTA();     // add Cars pill in desktop header
        addMobileCarsCTA();      // add Cars item in handheld bar
    }
});

document.addEventListener('DOMContentLoaded', () => {


    if (!document.body.classList.contains('single-product')) return;

    // ---------- tiny helpers ----------
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const on = (el, evs, fn, opt) => {
        if (!el) return;
        (Array.isArray(evs) ? evs : [evs]).forEach(e => el.addEventListener(e, fn, opt));
    };
    const observe = (target, fn, cfg = { childList: true, subtree: true }) => {
        const mo = new MutationObserver(fn);
        mo.observe(target, cfg);
        return mo;
    };

    // bail if our waiver UI isn't on the page
    const waiverWrap = $('.larissa-coverage-wrap');
    if (!waiverWrap) return;

    // ---- business hours
    const OPEN = '09:00';
    const CLOSE = '21:00';

    // ---- find RnB fields (covers common keys)
    const pickDate = document.querySelector('input[name="pickup_date"], input[name="rnb_start_date"], input[name="start_date"]');
    const dropDate = document.querySelector('input[name="dropoff_date"], input[name="rnb_end_date"], input[name="end_date"]');
    const pickTime = document.querySelector('input[name="pickup_time"], input[name="rnb_start_time"], input[name="start_time"]');
    const dropTime = document.querySelector('input[name="dropoff_time"], input[name="rnb_end_time"], input[name="end_time"]');

    if (!pickDate) return; // nothing to do

    // ---- helpers
    const fmt2 = n => String(n).padStart(2, '0');

    function parseDateLoose(s) {
        if (!s) return null;
        s = String(s).trim();
        // YYYY-MM-DD or YYYY/MM/DD
        let m = s.match(/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/);
        if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        // D/M/Y or M/D/Y (decide by >12 heuristic on middle token)
        m = s.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/);
        if (m) {
            const a = Number(m[1]), b = Number(m[2]), y = Number(m[3]);
            // if first part > 12 -> it's D/M/Y, else assume M/D/Y
            const d = a > 12 ? a : b;
            const mo = a > 12 ? b : a;
            return new Date(y, mo - 1, d);
        }
        // last resort: Date() parser
        const t = Date.parse(s);
        return Number.isNaN(t) ? null : new Date(t);
    }

    function isToday(d) {
        if (!d) return false;
        const now = new Date();
        return d.getFullYear() === now.getFullYear() &&
            d.getMonth() === now.getMonth() &&
            d.getDate() === now.getDate();
    }

    function setIfAutoOrEmpty(input, val) {
        if (!input) return;
        if (!input.value || input.dataset.autofilled === '1') {
            input.value = val;
            input.dataset.autofilled = '1';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function clearIfAutofilled(input) {
        if (input && input.dataset.autofilled === '1') {
            input.value = '';
            input.removeAttribute('data-autofilled');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function prefillTimesBasedOnPickup() {
        const d = parseDateLoose(pickDate.value);
        if (!d) return;

        if (!isToday(d)) {
            // future pickup → prefill if empty/not user-edited
            setIfAutoOrEmpty(pickTime, OPEN);
            setIfAutoOrEmpty(dropTime, CLOSE);
        } else {
            // same-day booking → remove any auto times we set
            clearIfAutofilled(pickTime);
            clearIfAutofilled(dropTime);
        }
    }

    // attach listeners
    ['change', 'input', 'blur'].forEach(ev => pickDate.addEventListener(ev, prefillTimesBasedOnPickup));
    if (dropDate) ['change', 'input', 'blur'].forEach(ev => dropDate.addEventListener(ev, prefillTimesBasedOnPickup));

    // run once on load (in case pickup is already chosen)
    prefillTimesBasedOnPickup();

    // Optional: if the plugin replaces inputs dynamically, observe DOM and re-run.
    const mo = new MutationObserver(prefillTimesBasedOnPickup);
    mo.observe(document.body, { childList: true, subtree: true });



});

(function () {
    function ensureLightbox() {
        let lb = document.querySelector('.car-lightbox');
        if (lb) return lb;

        lb = document.createElement('div');
        lb.className = 'car-lightbox';
        lb.setAttribute('hidden', '');
        lb.innerHTML = `
        <button class="car-lightbox__close" aria-label="Close">×</button>
        <button class="car-lightbox__nav is-prev" aria-label="Previous">‹</button>
        <img alt="">
        <button class="car-lightbox__nav is-next" aria-label="Next">›</button>
      `;
        document.body.appendChild(lb);
        return lb;
    }

    function initCarousel(root) {
        if (!root || root.dataset.carouselInited === '1') return;
        root.dataset.carouselInited = '1';

        const track = root.querySelector('.car-carousel__track');
        const slides = Array.from(track?.children || []);
        const prev = root.querySelector('.car-carousel__nav.is-prev');
        const next = root.querySelector('.car-carousel__nav.is-next');
        const dotsWrap = root.querySelector('.car-carousel__dots');
        if (!track || !slides.length) return;

        const imgs = slides.map(s => s.querySelector('img'));

        let index = 0, startX = 0, deltaX = 0, dragging = false;

        // Dots
        const dots = slides.map((_, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'car-carousel__dot';
            b.setAttribute('aria-label', `Slide ${i + 1}`);
            b.addEventListener('click', () => goTo(i));
            dotsWrap.appendChild(b);
            return b;
        });

        function update() {
            track.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
            if (prev) prev.disabled = (slides.length <= 1 || index === 0);
            if (next) next.disabled = (slides.length <= 1 || index === slides.length - 1);
        }

        function goTo(i) {
            index = Math.max(0, Math.min(i, slides.length - 1));
            update();
        }

        // Arrows
        if (prev) prev.addEventListener('click', () => goTo(index - 1));
        if (next) next.addEventListener('click', () => goTo(index + 1));

        // Drag / swipe
        track.addEventListener('pointerdown', (e) => {
            dragging = true; startX = e.clientX; deltaX = 0;
            track.style.transition = 'none';
            track.setPointerCapture(e.pointerId);
        });

        track.addEventListener('pointermove', (e) => {
            if (!dragging) return;
            deltaX = e.clientX - startX;
            track.style.transform = `translateX(calc(-${index * 100}% + ${deltaX}px))`;
        });

        function endDrag() {
            if (!dragging) return;
            track.style.transition = 'transform .4s ease';
            if (Math.abs(deltaX) > 50) {
                if (deltaX < 0) goTo(index + 1);
                else goTo(index - 1);
            } else {
                update();
            }
            dragging = false; deltaX = 0;
        }
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        // Lightbox
        const lb = ensureLightbox();
        const lbImg = lb.querySelector('img');
        const lbPrev = lb.querySelector('.car-lightbox__nav.is-prev');
        const lbNext = lb.querySelector('.car-lightbox__nav.is-next');
        const lbClose = lb.querySelector('.car-lightbox__close');

        function openLightbox(i) {
            index = i;
            const full = imgs[index].dataset.full || imgs[index].src;
            lbImg.src = full;
            lb.removeAttribute('hidden');
            lb.classList.add('open');
        }
        function closeLightbox() {
            lb.classList.remove('open');
            lb.setAttribute('hidden', '');
            lbImg.src = '';
        }
        function lbPrevFn() { openLightbox((index - 1 + slides.length) % slides.length); }
        function lbNextFn() { openLightbox((index + 1) % slides.length); }

        imgs.forEach((img, i) => img.addEventListener('click', () => openLightbox(i)));
        lbPrev.addEventListener('click', lbPrevFn);
        lbNext.addEventListener('click', lbNextFn);
        lbClose.addEventListener('click', closeLightbox);
        lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
        document.addEventListener('keydown', (e) => {
            if (!lb.classList.contains('open')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') lbPrevFn();
            if (e.key === 'ArrowRight') lbNextFn();
        });

        // Init + hide controls if only one slide
        update();
        if (slides.length < 2) {
            if (prev) prev.style.display = 'none';
            if (next) next.style.display = 'none';
            dotsWrap.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-carousel]').forEach(initCarousel);
    });
})();

(function () {
    const wrap = document.querySelector('#customer_login');
    if (!wrap) return;

    const tabLogin = document.querySelector('.acc-tab[data-target="login"]');
    const tabRegister = document.querySelector('.acc-tab[data-target="register"]');

    // If URL hash is #register, force register
    const fromHash = location.hash.replace('#', '') === 'register';
    let mode = (fromHash ? 'register' : (window.LARISSA_ACC && LARISSA_ACC.defaultMode)) || 'login';

    const setMode = (m) => {
        mode = m;
        wrap.classList.remove('mode-login', 'mode-register');
        wrap.classList.add('mode-' + m);

        const active = (m === 'login') ? tabLogin : tabRegister;
        const other = (m === 'login') ? tabRegister : tabLogin;

        if (active && other) {
            active.classList.add('is-active');
            other.classList.remove('is-active');
            active.setAttribute('aria-selected', 'true');
            other.setAttribute('aria-selected', 'false');
        }

        // optional: keep selection in session
        try { sessionStorage.setItem('acc-mode', m); } catch (e) { }
    };

    // Restore last selection for UX (optional)
    try {
        const saved = sessionStorage.getItem('acc-mode');
        if (saved) mode = saved;
    } catch (e) { }

    // Click handlers
    if (tabLogin) tabLogin.addEventListener('click', () => setMode('login'));
    if (tabRegister) tabRegister.addEventListener('click', () => setMode('register'));

    // Auto-switch if register form contains validation errors (when present)
    const notices = document.querySelector('.woocommerce-notices-wrapper .woocommerce-error');
    if (notices && /register/i.test(notices.textContent || '')) mode = 'register';

    // Initialize
    setMode(mode);
})();

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var label = document.querySelector('.larissa-coming-soon-label');
        if (!label) return;

        // create a small toast element
        var toast = document.createElement('div');
        toast.className = 'larissa-toast';
        toast.textContent = (window.larissaCheckout && window.larissaCheckout.comingSoonText) ? window.larissaCheckout.comingSoonText : 'Card payments coming soon.';
        document.body.appendChild(toast);

        function showToast() {
            toast.style.display = 'block';
            toast.style.opacity = '1';
            setTimeout(function () {
                toast.style.opacity = '0';
                setTimeout(function () { toast.style.display = 'none'; }, 300);
            }, 2600);
        }

        label.addEventListener('click', function (e) {
            // if the radio is disabled, show the message and prevent selection
            var r = label.querySelector('input[type="radio"]');
            if (r && r.disabled) {
                e.preventDefault();
                showToast();
            }
        });
    });
})();


document.addEventListener('DOMContentLoaded', () => {
    jQuery(document).ready(function ($) {
        // Run only on the cart page
        if ($('body').hasClass('page-id-8')) {

            // Wait for TranslatePress to load
            const waitForTranslatePress = setInterval(function () {
                if (typeof window.trpTranslateDOM === 'function') {
                    clearInterval(waitForTranslatePress);

                    const rerunTranslatePress = () => {
                        setTimeout(function () {
                            if (typeof window.trpTranslateDOM === 'function') {
                                window.trpTranslateDOM(document.body);
                                console.log('✅ TranslatePress re-run after cart update');
                            } else {
                                console.warn('⚠️ trpTranslateDOM disappeared');
                            }
                        }, 500);
                    };

                    // Run once at page load (helps translate session cart)
                    rerunTranslatePress();

                    // Hook into WooCommerce events
                    $(document).on('updated_wc_div wc_fragments_refreshed wc_cart_emptied', rerunTranslatePress);
                }
            }, 300);
        }
    });
});


// -------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const masthead = document.getElementById('masthead');
    if (!masthead) return;

    // Find the real scrolling element (not window if you're using an overlay)
    const candidates = [
        document.querySelector('#page'),
        document.querySelector('.site'),
        document.querySelector('main'),
        document.querySelector('#content'),
        document.scrollingElement
    ].filter(Boolean);

    let scroller = window;

    for (const el of candidates) {
        const style = getComputedStyle(el);
        const canScroll = el.scrollHeight > el.clientHeight + 5;
        const overflowY = style.overflowY;
        if (canScroll && (overflowY === 'auto' || overflowY === 'scroll')) {
            scroller = el;
            break;
        }
    }

    const getTop = () => (scroller === window)
        ? (window.scrollY || window.pageYOffset || 0)
        : (scroller.scrollTop || 0);

    const tick = () => {
        masthead.classList.toggle('is-scrolled', getTop() > 2);
    };

    tick();
    (scroller === window ? window : scroller).addEventListener('scroll', tick, { passive: true });
});
document.addEventListener('DOMContentLoaded', function () {
    const menuBtn = document.getElementById('rcl24-menu-toggle');
    const menuPanel = document.getElementById('rcl24-menu-panel');
    const backdrop = document.getElementById('rcl24-backdrop');
    const closeBtns = document.querySelectorAll('.rcl24-panel-close');

    const closeMenu = () => {
        menuPanel?.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        menuBtn?.setAttribute('aria-expanded', 'false');
        document.documentElement.style.overflow = ''; // unlock scroll
    };

    const openMenu = () => {
        closeMenu(); // ensures consistent state
        menuPanel?.classList.add('is-open');
        backdrop?.classList.add('is-open');
        menuBtn?.setAttribute('aria-expanded', 'true');
        document.documentElement.style.overflow = 'hidden'; // lock scroll
    };

    menuBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!menuPanel) return;

        const isOpen = menuPanel.classList.contains('is-open');
        if (isOpen) closeMenu();
        else openMenu();
    });

    backdrop?.addEventListener('click', closeMenu);
    closeBtns.forEach(b => b.addEventListener('click', closeMenu));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const langBtn = document.getElementById('rcl24-lang-toggle');
    const langDD = document.getElementById('rcl24-lang-dd');   // your small dropdown
    const backdrop = document.getElementById('rcl24-backdrop');  // if you still use it

    if (!langBtn || !langDD) return;

    // Toggle dropdown
    langBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        langDD.classList.toggle('is-open');
    });

    // IMPORTANT: clicks inside dropdown should not bubble to "close" handlers
    langDD.addEventListener('click', (e) => {
        e.stopPropagation();
        // do NOT preventDefault here (links must work)
    });

    // Close on backdrop click (if you use it)
    backdrop?.addEventListener('click', () => {
        langDD.classList.remove('is-open');
    });

    // Close when clicking anywhere else
    document.addEventListener('click', () => {
        langDD.classList.remove('is-open');
    });

    // Escape closes
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') langDD.classList.remove('is-open');
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const masthead = document.getElementById('masthead');
    if (!masthead) return;


    const rcl24Container = document.querySelector("#rcl24-brand-container")
    const mobileLogo = document.getElementById('rcl24-mobile-logo'); // <-- your logo link
    // If you haven't added it yet, do so in handheld-nav-bar.php:
    // <a id="rcl24-mobile-logo" href="...">...</a>

    // Find the real scrolling element (not window if you're using an overlay)
    const candidates = [
        document.querySelector('#page'),
        document.querySelector('.site'),
        document.querySelector('main'),
        document.querySelector('#content'),
        document.scrollingElement
    ].filter(Boolean);

    let scroller = window;

    for (const el of candidates) {
        const style = getComputedStyle(el);
        const canScroll = el.scrollHeight > el.clientHeight + 5;
        const overflowY = style.overflowY;
        if (canScroll && (overflowY === 'auto' || overflowY === 'scroll')) {
            scroller = el;
            break;
        }
    }

    const getTop = () => (scroller === window)
        ? (window.scrollY || window.pageYOffset || 0)
        : (scroller.scrollTop || 0);

    // --- CONFIG (tweak if needed) ---
    const TOP_SHOW_AT = 24;     // always show logo when near top
    const HIDE_AFTER = 80;     // don't hide immediately
    const MIN_DELTA = 6;      // ignore micro scroll jitter

    let lastTop = getTop();
    let ticking = false;

    let lockUntil = 0;

    const lock = () => {
        lockUntil = Date.now() + 350; // match your CSS transition (~.25s)
    };


    const tick = () => {
        ticking = false;

        const top = getTop();

        // existing behavior
        masthead.classList.toggle('is-scrolled', top > 2);

        // logo behavior
        if (mobileLogo) {
            const delta = top - lastTop;

            // Always show near top
            if (top <= TOP_SHOW_AT) {
                rcl24Container.classList.remove('is-collapsed')
                lock();
                mobileLogo.classList.remove('is-hidden');
                lastTop = top;
                return;
            }

            // ignore tiny movement
            if (Math.abs(delta) < MIN_DELTA) return;


            // scroll down => hide (after a bit)
            if (delta > 0 && top > HIDE_AFTER) {
                rcl24Container.classList.add('is-collapsed')
                lock();
                mobileLogo.classList.add('is-hidden');
            }
            // scroll up => show
            else if (delta < 0) {
                rcl24Container.classList.remove('is-collapsed')
                lock();
                mobileLogo.classList.remove('is-hidden');
            }

            lastTop = top;
        }
    };

    // initial
    tick();

    const target = (scroller === window) ? window : scroller;
    target.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(tick);
            ticking = true;
        }
    }, { passive: true });
});

document.addEventListener('DOMContentLoaded', function () {
    const ribbon = document.getElementById('rcl24-ribbon') || document.querySelector('.rcl24-ribbon');
    if (!ribbon) return;

    // --- Find the real scrolling element (same idea as your masthead script) ---
    const candidates = [
        document.querySelector('#page'),
        document.querySelector('.site'),
        document.querySelector('main'),
        document.querySelector('#content'),
        document.scrollingElement
    ].filter(Boolean);

    let scroller = window;

    for (const el of candidates) {
        const style = getComputedStyle(el);
        const canScroll = el.scrollHeight > el.clientHeight + 5;
        const overflowY = style.overflowY;
        if (canScroll && (overflowY === 'auto' || overflowY === 'scroll')) {
            scroller = el;
            break;
        }
    }

    const getTop = () => (scroller === window)
        ? (window.scrollY || window.pageYOffset || 0)
        : (scroller.scrollTop || 0);

    // Show ONLY at top
    const tick = () => {
        const top = getTop();
        ribbon.classList.toggle('is-collapsed', top > 2);
    };

    tick();
    (scroller === window ? window : scroller).addEventListener('scroll', tick, { passive: true });
});