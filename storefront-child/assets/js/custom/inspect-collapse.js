(function () {
    function textOrFallback(v, fallback) {
        v = (v || "").trim();
        return v ? v : fallback;
    }

    function buildSummary(scope) {
        const form = scope.querySelector('form.inspect-home-search-form');
        if (!form) return '';

        const dateEl = form.querySelector('input.daterange');
        const dateVal = dateEl ? dateEl.value : '';

        const pickSel = form.querySelector('select[name="tex_pickup_location"]');
        const dropSel = form.querySelector('select[name="tex_return_location"]');

        const pickTxt = pickSel ? (pickSel.options[pickSel.selectedIndex]?.text || '') : '';
        const dropTxt = dropSel ? (dropSel.options[dropSel.selectedIndex]?.text || '') : '';

        const date = textOrFallback(dateVal, 'Dates');
        const pick = textOrFallback(pickTxt, 'Pickup');
        const drop = textOrFallback(dropTxt, 'Return');

        return `${date} • ${pick} → ${drop}`;
    }

    function setExpanded(scope, expanded) {
        const body = scope.querySelector('.header-body');
        const btn = scope.querySelector('.rc24-collapse-toggle');
        if (!body || !btn) return;

        if (expanded) {
            scope.classList.add('is-open');

            // animate from 0 -> content height
            body.style.maxHeight = body.scrollHeight + 'px';

            // after transition, allow growth
            window.setTimeout(function () {
                body.style.maxHeight = '10000px';
                window.dispatchEvent(new Event('resize'));
            }, 300);

            btn.setAttribute('aria-expanded', 'true');
            btn.textContent = 'Hide search';
        } else {
            // set to current height first so transition is smooth
            body.style.maxHeight = body.scrollHeight + 'px';

            requestAnimationFrame(function () {
                scope.classList.remove('is-open');
                body.style.maxHeight = '0px';
            });

            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = 'Edit search';
        }
    }

    function initOne(scope) {
        const header = scope.querySelector('.search-header');
        const body = scope.querySelector('.header-body');
        if (!header || !body) return;

        // Inject controls once
        if (header.querySelector('.rc24-collapse-controls')) return;

        const controls = document.createElement('div');
        controls.className = 'rc24-collapse-controls';

        const summary = document.createElement('div');
        summary.className = 'rc24-collapse-summary';
        summary.textContent = buildSummary(scope);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rc24-collapse-toggle';
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = 'Edit search';

        controls.appendChild(summary);
        controls.appendChild(btn);
        header.appendChild(controls);

        // Update summary when fields change
        scope.addEventListener('change', function () {
            summary.textContent = buildSummary(scope);
        });

        // ✅ FIXED: toggle using the actual button you created
        btn.addEventListener('click', function () {
            const isOpen = scope.classList.contains('is-open');
            setExpanded(scope, !isOpen);
        });

        // ✅ Default: collapsed everywhere (including cars page)
        setExpanded(scope, false);
    }

    function init() {
        document
            .querySelectorAll('.rc24-inspect-wrap .inspect-vertical-search-wrapper')
            .forEach(initOne);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();


(function () {
    function cleanUrl(url) {
        // keep querystring if you want, but remove hash
        return (url || window.location.href).split('#')[0];
    }

    function patchInspectTargets() {
        const target = cleanUrl(window.location.href);

        document
            .querySelectorAll('.rc24-inspect-wrap form.inspect-home-search-form')
            .forEach(function (form) {
                // The Inspect plugin uses data-url for where results should render
                form.setAttribute('data-url', target);

                // Also fix the normal form submit target
                form.setAttribute('action', target);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', patchInspectTargets);
    } else {
        patchInspectTargets();
    }
})();

(function () {
    function init() {
        const p = document.querySelector('.lc-carsHero__sub');
        if (!p) return;

        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) return;

        if (document.querySelector('.lc-carsHero__moreRow')) return;

        const row = document.createElement('div');
        row.className = 'lc-carsHero__moreRow';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'lc-carsHero__moreBtn';
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = 'Read more';

        btn.addEventListener('click', function () {
            const expanded = p.classList.toggle('is-expanded');
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            btn.textContent = expanded ? 'Read less' : 'Read more';
        });

        row.appendChild(btn);
        p.insertAdjacentElement('afterend', row);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

(function () {
    function sanitizeInspectForms() {
        document.querySelectorAll('form.inspect-home-search-form').forEach(function (form) {
            // 1) prevent URL query pollution from Inspect fields
            form.querySelectorAll('input[name="order_by"], input[name="view"], input[name="paged"]').forEach(function (el) {
                el.removeAttribute('name');
            });

            // Some versions use different names
            form.querySelectorAll('input[name="order-by"], input[name="product-order-by"]').forEach(function (el) {
                el.removeAttribute('name');
            });

            // 2) keep form action on current page (so it won't redirect weirdly)
            form.setAttribute('action', window.location.href.split('#')[0]);

            // 3) if Inspect works via ajax, POST is safer (no query strings)
            form.setAttribute('method', 'post');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sanitizeInspectForms);
    } else {
        sanitizeInspectForms();
    }
})();