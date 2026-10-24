/**
 * COMSOC Public Website — Motion & Scroll Reveal System
 * Lightweight, accessible, performant editorial animation controller
 */

export function initMotionSystem() {
    // 1. Accessibility Check: prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        document.documentElement.classList.add('reduced-motion');
        return;
    }

    // 2. Mark motion as enabled for styling
    document.documentElement.classList.add('motion-ready');

    // 3. Initial Hero Entrance Animation Sequence
    initHeroSequence();

    // 4. Scroll-Triggered Section & Item Reveals via IntersectionObserver
    initScrollReveals();

    // 5. Subtle Ambient Parallax for Decorative Grid
    initSubtleParallax();
}

/**
 * Orchestrates initial hero entrance on load
 */
function initHeroSequence() {
    const heroSection = document.getElementById('hero');
    if (!heroSection) return;

    // Elements inside hero tagged with data-hero-seq
    const heroElements = heroSection.querySelectorAll('[data-hero-seq]');
    if (!heroElements.length) return;

    // Sequence elements sequentially with smooth stagger
    heroElements.forEach((el) => {
        const order = parseInt(el.getAttribute('data-hero-seq') || '0', 10);
        const delay = Math.max(40, order * 90); // 90ms stagger
        setTimeout(() => {
            el.classList.add('is-revealed');
        }, delay);
    });
}

/**
 * Initializes IntersectionObserver for scroll-triggered reveals
 */
function initScrollReveals() {
    if (!('IntersectionObserver' in window)) {
        // Fallback: reveal all immediately if IntersectionObserver unsupported
        document.querySelectorAll('[data-reveal], [data-reveal-group]').forEach(el => {
            el.classList.add('is-revealed');
        });
        return;
    }

    // Observer for individual elements
    const elementObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.12
    });

    document.querySelectorAll('[data-reveal]').forEach(el => {
        // If element is already in viewport on load, reveal promptly
        elementObserver.observe(el);
    });

    // Observer for staggered content groups (pillars, officers, activities, gallery, partners)
    const groupObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const group = entry.target;
                const items = group.querySelectorAll('[data-reveal-item]');
                items.forEach((item, index) => {
                    const delay = Math.min(index * 75, 450); // 75ms stagger, max 450ms
                    setTimeout(() => {
                        item.classList.add('is-revealed');
                    }, delay);
                });
                group.classList.add('is-revealed');
                observer.unobserve(group);
            }
        });
    }, {
        root: null,
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.1
    });

    document.querySelectorAll('[data-reveal-group]').forEach(group => {
        groupObserver.observe(group);
    });
}

/**
 * Optional decorative parallax: subtle, non-intrusive, throttled via requestAnimationFrame
 */
function initSubtleParallax() {
    // Only apply on non-touch devices with fine pointers
    if (!window.matchMedia('(pointer: fine)').matches) return;

    const bgGrid = document.querySelector('.auth-bg-grid');
    if (!bgGrid) return;

    let ticking = false;
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            window.requestAnimationFrame(() => {
                // Restrained vertical translation (max 18px shift across the entire scroll)
                const translateY = Math.min(lastScrollY * 0.04, 18);
                bgGrid.style.transform = `translate3d(0, ${translateY}px, 0)`;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}
