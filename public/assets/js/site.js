/* ==========================================================================
   NONXSTUDIOS — site interactions
   Progressive enhancement: every feature degrades gracefully without JS.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    root.classList.add('js');

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer  = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    /* --- Loader --------------------------------------------------------- */
    function initLoader() {
        var loader = document.querySelector('.loader');
        if (!loader) return;
        var done = function () {
            loader.classList.add('is-done');
            window.setTimeout(function () { loader.style.display = 'none'; }, 700);
        };
        if (document.readyState === 'complete') {
            window.setTimeout(done, 250);
        } else {
            window.addEventListener('load', function () { window.setTimeout(done, 250); });
            // Safety net so a slow asset never blocks the page.
            window.setTimeout(done, 2600);
        }
    }

    /* --- Sticky nav ----------------------------------------------------- */
    function initNav() {
        var nav = document.querySelector('[data-nav]');
        if (!nav) return;
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 40);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* --- Mobile menu ---------------------------------------------------- */
    function initMenu() {
        var toggle = document.querySelector('[data-menu-toggle]');
        var menu   = document.querySelector('[data-menu]');
        if (!toggle || !menu) return;

        var lastFocused = null;

        function open() {
            lastFocused = document.activeElement;
            menu.hidden = false;
            // Force a reflow so the transition runs from the hidden state.
            void menu.offsetWidth;
            menu.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            var first = menu.querySelector('a, button');
            if (first) first.focus();
        }

        function close() {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            window.setTimeout(function () { menu.hidden = true; }, 400);
            if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
        }

        toggle.addEventListener('click', function () {
            toggle.getAttribute('aria-expanded') === 'true' ? close() : open();
        });

        menu.addEventListener('click', function (e) {
            if (e.target.closest('a')) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') close();

            if (e.key === 'Tab' && toggle.getAttribute('aria-expanded') === 'true') {
                var focusables = menu.querySelectorAll('a[href], button:not([disabled])');
                if (!focusables.length) return;
                var first = focusables[0];
                var last  = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault(); last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault(); first.focus();
                }
            }
        });

        // Close the menu if the viewport grows past the mobile breakpoint.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 1080 && toggle.getAttribute('aria-expanded') === 'true') close();
        });
    }

    /* --- Custom cursor (desktop, fine pointer only) --------------------- */
    function initCursor() {
        if (!finePointer || reduceMotion) return;
        var cursor = document.querySelector('.cursor');
        if (!cursor) return;

        var x = window.innerWidth / 2, y = window.innerHeight / 2;
        var cx = x, cy = y, active = false;

        window.addEventListener('mousemove', function (e) {
            x = e.clientX; y = e.clientY;
            if (!active) { cursor.classList.add('is-active'); active = true; }
        }, { passive: true });

        document.addEventListener('mouseleave', function () { cursor.classList.remove('is-active'); active = false; });

        function tick() {
            cx += (x - cx) * 0.18;
            cy += (y - cy) * 0.18;
            cursor.style.transform = 'translate3d(' + cx + 'px,' + cy + 'px,0)';
            requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);

        var hoverTargets = 'a, button, .card, .filter, input, textarea, select, [data-cursor-hover]';
        document.addEventListener('mouseover', function (e) {
            if (e.target.closest(hoverTargets)) cursor.classList.add('is-hover');
        });
        document.addEventListener('mouseout', function (e) {
            if (e.target.closest(hoverTargets)) cursor.classList.remove('is-hover');
        });
    }

    /* --- Scroll reveals (GSAP + ScrollTrigger when available) ----------- */
    function initReveals() {
        var revealEls = document.querySelectorAll('[data-reveal]');
        var maskEls   = document.querySelectorAll('.reveal-mask');

        if (reduceMotion || typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') {
            // Fallback: IntersectionObserver, or just show everything.
            if (!('IntersectionObserver' in window) || reduceMotion) {
                revealEls.forEach(function (el) { el.classList.add('is-in'); });
                maskEls.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            document.querySelectorAll('[data-reveal], .reveal-mask').forEach(function (el) { io.observe(el); });
            return;
        }

        gsap.registerPlugin(ScrollTrigger);

        // Generic fade/slide reveals.
        revealEls.forEach(function (el) {
            var y = parseFloat(el.getAttribute('data-reveal-y') || '32');
            gsap.fromTo(el,
                { opacity: 0, y: y },
                {
                    opacity: 1, y: 0,
                    duration: 0.9,
                    ease: 'power3.out',
                    delay: parseFloat(el.getAttribute('data-reveal-delay') || '0'),
                    scrollTrigger: { trigger: el, start: 'top 88%', once: true }
                }
            );
        });

        // Clip-path image reveals.
        maskEls.forEach(function (el) {
            gsap.to(el, {
                clipPath: 'inset(0 0 0% 0)',
                duration: 1.1,
                ease: 'power4.out',
                scrollTrigger: { trigger: el, start: 'top 85%', once: true }
            });
        });

        // Staggered card entrances.
        var grids = document.querySelectorAll('[data-stagger]');
        grids.forEach(function (grid) {
            var items = grid.children;
            if (!items.length) return;
            gsap.fromTo(items,
                { opacity: 0, y: 40 },
                {
                    opacity: 1, y: 0, duration: 0.8, ease: 'power3.out', stagger: 0.08,
                    scrollTrigger: { trigger: grid, start: 'top 82%', once: true }
                }
            );
        });

        // Process steps.
        document.querySelectorAll('.step').forEach(function (step, i) {
            ScrollTrigger.create({
                trigger: step,
                start: 'top 80%',
                once: true,
                onEnter: function () { step.classList.add('is-in'); }
            });
            gsap.fromTo(step,
                { opacity: 0, y: 30 },
                { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out', delay: i * 0.05,
                  scrollTrigger: { trigger: step, start: 'top 85%', once: true } }
            );
        });

        // Hero entrance (plays immediately, not scroll-bound).
        var heroLines = document.querySelectorAll('.hero__title .line > span');
        if (heroLines.length) {
            gsap.fromTo(heroLines,
                { yPercent: 110 },
                { yPercent: 0, duration: 1.15, ease: 'power4.out', stagger: 0.1, delay: 0.35 }
            );
        }
        var heroBits = document.querySelectorAll('[data-hero-fade]');
        if (heroBits.length) {
            gsap.fromTo(heroBits,
                { opacity: 0, y: 24 },
                { opacity: 1, y: 0, duration: 1, ease: 'power3.out', stagger: 0.12, delay: 0.75 }
            );
        }

        // Subtle parallax on marked elements.
        document.querySelectorAll('[data-parallax]').forEach(function (el) {
            var strength = parseFloat(el.getAttribute('data-parallax') || '0.12');
            gsap.to(el, {
                yPercent: strength * 100,
                ease: 'none',
                scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true }
            });
        });
    }

    /* --- Work filters (client-side, no reload) -------------------------- */
    function initFilters() {
        var bar = document.querySelector('[data-filters]');
        var grid = document.querySelector('[data-work-grid]');
        if (!bar || !grid) return;

        var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-category]'));
        var live = document.querySelector('[data-filter-status]');

        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.filter');
            if (!btn) return;
            var cat = btn.getAttribute('data-filter');

            bar.querySelectorAll('.filter').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            var shown = 0;
            cards.forEach(function (card) {
                var match = cat === 'all' || card.getAttribute('data-category') === cat;
                card.classList.toggle('is-hidden', !match);
                if (match) {
                    shown++;
                    if (typeof window.gsap !== 'undefined' && !reduceMotion) {
                        gsap.fromTo(card, { opacity: 0, y: 18 },
                            { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' });
                    }
                }
            });

            grid.classList.toggle('is-filtered', cat !== 'all');

            if (live) {
                var label = btn.textContent.trim();
                live.textContent = shown + (shown === 1 ? ' project' : ' projects') + ' shown in ' + label + '.';
            }
        });
    }

    /* --- Lazy image fade-in --------------------------------------------- */
    function initLazyImages() {
        var imgs = document.querySelectorAll('img[loading="lazy"]');
        imgs.forEach(function (img) {
            if (img.complete) { img.classList.add('is-loaded'); return; }
            img.addEventListener('load', function () { img.classList.add('is-loaded'); });
            img.addEventListener('error', function () { img.classList.add('is-loaded'); });
        });
    }

    /* --- Current year in any [data-year] -------------------------------- */
    function initYear() {
        var els = document.querySelectorAll('[data-year]');
        var y = new Date().getFullYear();
        els.forEach(function (el) { el.textContent = y; });
    }

    /* --- Boot ----------------------------------------------------------- */
    function boot() {
        initLoader();
        initNav();
        initMenu();
        initCursor();
        initReveals();
        initFilters();
        initLazyImages();
        initYear();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
