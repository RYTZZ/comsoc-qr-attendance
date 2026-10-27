/**
 * COMSOC Public Website — Motion & Scroll Reveal System
 * Lightweight, accessible, performant editorial animation controller
 */

export function initMotionSystem() {
    // 1. Accessibility Check: prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        document.documentElement.classList.add('reduced-motion');
        revealAllDirectly();
        return;
    }

    // 2. Mark motion as enabled for styling
    document.documentElement.classList.add('motion-ready');

    // 3. Initial Hero Entrance Animation Sequence
    initHeroSequence();

    // 4. Scroll-Triggered Section & Item Reveals via IntersectionObserver
    initScrollReveals();

    // 5. Header Scroll Interaction
    initNavigationScroll();

    // 6. Subtle Ambient Parallax for Decorative Grid
    initSubtleParallax();
}

/**
 * Fallback to guarantee no content remains hidden
 */
function revealAllDirectly() {
    document.querySelectorAll('[data-hero-seq], [data-reveal], [data-reveal-group], [data-reveal-item]').forEach(el => {
        el.classList.add('is-revealed');
    });
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
        const delay = Math.max(50, order * 90); // 90ms stagger
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
        revealAllDirectly();
        return;
    }

    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    // Observer for individual elements (Section headings, descriptions, eyebrow badges)
    const elementObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,
        rootMargin: '0px 0px -20px 0px',
        threshold: 0.05
    });

    document.querySelectorAll('[data-reveal]:not(.is-revealed)').forEach(el => {
        const rect = el.getBoundingClientRect();
        // If element is already in or above the visible viewport on initial load, reveal immediately
        if (rect.top <= viewportHeight * 0.9 && rect.bottom >= 0) {
            el.classList.add('is-revealed');
        } else {
            elementObserver.observe(el);
        }
    });

    // Observer for staggered content groups (Executive Board, Pillars, Activities, Achievements, Gallery, Partners)
    const groupObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const group = entry.target;
                const items = group.querySelectorAll('[data-reveal-item]:not(.is-revealed)');

                // Sequence cards/items with a quick 80-100ms cadence (capped so users never wait)
                items.forEach((item, index) => {
                    const delay = Math.min(index * 90, 800);
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
        rootMargin: '0px 0px -20px 0px',
        threshold: 0.05
    });

    document.querySelectorAll('[data-reveal-group]:not(.is-revealed)').forEach(group => {
        const rect = group.getBoundingClientRect();
        // If group is already visible upon initial render
        if (rect.top <= viewportHeight * 0.9 && rect.bottom >= 0) {
            const items = group.querySelectorAll('[data-reveal-item]:not(.is-revealed)');
            items.forEach((item, index) => {
                const delay = Math.min(index * 80, 600);
                setTimeout(() => {
                    item.classList.add('is-revealed');
                }, delay + 60);
            });
            group.classList.add('is-revealed');
        } else {
            groupObserver.observe(group);
        }
    });

    // Safety fallback: after 2.5 seconds, ensure everything is guaranteed revealed
    setTimeout(() => {
        document.querySelectorAll('[data-reveal]:not(.is-revealed), [data-reveal-item]:not(.is-revealed)').forEach(el => {
            el.classList.add('is-revealed');
        });
    }, 2500);
}

/**
 * Optional decorative parallax: subtle, non-intrusive, throttled via requestAnimationFrame
 */
function initSubtleParallax() {
    if (!window.matchMedia('(pointer: fine)').matches) return;

    const bgGrid = document.querySelector('.auth-bg-grid');
    if (!bgGrid) return;

    let ticking = false;
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const translateY = Math.min(lastScrollY * 0.06, 28);
                bgGrid.style.transform = `translate3d(0, ${translateY}px, 0)`;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

/**
 * Navigation Micro-Interactions: Smooth header transition on scroll
 */
function initNavigationScroll() {
    const header = document.querySelector('.public-nav-header');
    if (!header) return;

    let isScrolled = false;
    const updateHeader = () => {
        const scrolled = window.scrollY > 20;
        if (scrolled !== isScrolled) {
            isScrolled = scrolled;
            if (isScrolled) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        }
    };

    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
}
