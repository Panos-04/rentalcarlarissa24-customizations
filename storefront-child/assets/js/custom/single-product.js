/**
 * Tiny gallery switcher (no carousel):
 * clicking a thumb swaps the main image src.
 */
(function () {
  const wrap = document.querySelector('.lc-product');
  if (!wrap) return;

  const mainImg = wrap.querySelector('.lc-gallery__mainImg');
  const thumbs = wrap.querySelectorAll('[data-lc-thumb]');
  if (!mainImg || !thumbs.length) return;

  thumbs.forEach(btn => {
    btn.addEventListener('click', () => {
      const img = btn.querySelector('img');
      if (!img) return;

      // Use the thumbnail's closest <img> srcset? easiest is swap by reloading attachment via data id not available here.
      // So we swap to the thumb image URL as fallback; you can later enhance to use large URLs.
      const src = img.getAttribute('src');
      if (src) mainImg.setAttribute('src', src);

      thumbs.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
    });
  });
})();

(function () {
  const drawer = document.querySelector('[data-lc-drawer]');
  const openBtn = document.querySelector('[data-lc-open]');
  const closeEls = document.querySelectorAll('[data-lc-close]');

  if (!drawer || !openBtn) return;

  const open = () => {
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('lc-lock');
  };

  const close = () => {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('lc-lock');
  };

  openBtn.addEventListener('click', open);
  closeEls.forEach(el => el.addEventListener('click', close));

  // ESC to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) close();
  });
})();

(function () {
  const main = document.querySelector('[data-lc-main]');
  const thumbs = Array.from(document.querySelectorAll('[data-lc-thumb]'));
  const viewport = document.querySelector('[data-lc-thumbs-viewport]');
  const prev = document.querySelector('[data-lc-thumbs-prev]');
  const next = document.querySelector('[data-lc-thumbs-next]');

  if (!main || !thumbs.length) return;

  function setActiveByIndex(idx) {
    if (idx < 0) idx = thumbs.length - 1;
    if (idx >= thumbs.length) idx = 0;

    const btn = thumbs[idx];

    thumbs.forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');

    const src = btn.getAttribute('data-lc-large');
    if (src) main.src = src;

    // Keep active thumb in view
    btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
  }

  function getActiveIndex() {
    const i = thumbs.findIndex(b => b.classList.contains('is-active'));
    return i === -1 ? 0 : i;
  }

  // Click thumb => change main image
  thumbs.forEach((btn, idx) => {
    btn.addEventListener('click', () => setActiveByIndex(idx));
  });

  // Arrow click => move active image + scroll thumbs
  if (prev) prev.addEventListener('click', () => {
    setActiveByIndex(getActiveIndex() - 1);
    if (viewport) {
      viewport.scrollBy({ left: -Math.round(viewport.clientWidth * 0.5), behavior: 'smooth' });
    }
  });

  if (next) next.addEventListener('click', () => {
    setActiveByIndex(getActiveIndex() + 1);
    if (viewport) {
      viewport.scrollBy({ left: Math.round(viewport.clientWidth * 0.5), behavior: 'smooth' });
    }
  });

  // Optional: keyboard support when focused on page
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') setActiveByIndex(getActiveIndex() - 1);
    if (e.key === 'ArrowRight') setActiveByIndex(getActiveIndex() + 1);
  });
})();




