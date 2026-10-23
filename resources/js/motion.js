import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';

gsap.registerPlugin(ScrollTrigger);

/*
 | Storefront motion layer: smooth scrolling, scroll reveals and pointer tilt.
 | Pages are swapped without a reload (Turbo), so everything created here is torn down
 | before the next page renders and set up again afterwards.
 | It is progressive enhancement: visitors who ask for reduced motion get the static layout.
 */

const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
let lenis = null;
let media = null;
let extras = [];

export function startMotion() {
    stopMotion();
    document.documentElement.classList.add('motion-ready');
    window.__motionReady = true;
    if (!document.body.hasAttribute('data-motion')) return;

    smoothScroll();
    media = gsap.matchMedia();
    media.add('(prefers-reduced-motion: no-preference)', () => {
        header();
        progressBar();
        splitHeadings(document);
        reveals(document);
        counters();
        marquee();
        parallax();
        if (finePointer) return tilt(document);
    });
    lenis?.resize();
    ScrollTrigger.refresh();
}

export function stopMotion() {
    extras.forEach((ctx) => ctx.revert());
    extras = [];
    media?.revert();
    media = null;
}

/** Content swapped in place (filtered results, more products) gets the same entrance. */
export function refreshMotion(root) {
    if (reduced.matches || !document.body.hasAttribute('data-motion')) return;
    let cleanup;
    const ctx = gsap.context(() => {
        splitHeadings(root);
        reveals(root);
        if (finePointer) cleanup = tilt(root);
    });
    ctx.add(() => cleanup);
    extras.push({ revert: () => { cleanup?.(); ctx.revert(); } });
    lenis?.resize();
    ScrollTrigger.refresh();
}

/* ---------- Smooth scroll (created once, survives page swaps) ---------- */

function smoothScroll() {
    // Touch devices already scroll smoothly; hijacking them only adds latency.
    if (lenis || !finePointer || reduced.matches) return;

    lenis = new Lenis({
        lerp: 0.12,
        anchors: { offset: -24 },
        // Leave scrolling to open drawers, sheets, dialogs and dropdown panels.
        prevent: (node) => !!node.closest('[data-lenis-prevent], [role="dialog"], [role="listbox"]'),
    });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
    window.__lenis = lenis;
}

/* ---------- Header: leaves on the way down, slides back the moment its own space comes into view ---------- */

function header() {
    const el = document.querySelector('[data-header]');
    if (!el) return;
    ScrollTrigger.create({
        start: 0,
        end: 'max',
        onUpdate(self) {
            const y = self.scroll();
            const busy = el.querySelector('[aria-expanded="true"]') || el.contains(document.activeElement);
            // Its slot at the top of the page is as tall as the header: never leave that slot showing empty.
            const slot = el.offsetHeight;
            if (y <= slot || busy) el.classList.remove('is-hidden');
            else if (self.direction === 1 && y > slot + 60) el.classList.add('is-hidden');
        },
    });
    return () => el.classList.remove('is-hidden');
}

function progressBar() {
    const bar = document.querySelector('[data-scroll-progress]');
    if (!bar) return;
    gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: 0.3 } });
}

/* ---------- Headings rise out of a mask, word by word ---------- */

function splitWords(el) {
    if (el.dataset.splitDone) return [...el.querySelectorAll('.word > span')];
    const text = el.textContent.trim().replace(/\s+/g, ' ');
    el.setAttribute('aria-label', text);
    el.textContent = '';
    const inner = [];
    text.split(' ').forEach((word, i, all) => {
        const mask = document.createElement('span');
        mask.className = 'word';
        mask.setAttribute('aria-hidden', 'true');
        const span = document.createElement('span');
        span.textContent = word;
        mask.append(span);
        el.append(mask);
        if (i < all.length - 1) el.append(' ');
        inner.push(span);
    });
    el.dataset.splitDone = '1';
    return inner;
}

/*
 | Content is never hidden while the page loads: whatever is already on screen when the motion layer
 | starts simply stays put, and only what is still below the fold makes an entrance as it scrolls in.
 */
const belowFold = (el) => el.getBoundingClientRect().top > window.innerHeight;

function splitHeadings(root) {
    gsap.utils.toArray('[data-split]', root).filter(belowFold).forEach((el) => {
        const words = splitWords(el);
        gsap.set(el, { autoAlpha: 1 });
        gsap.from(words, {
            yPercent: 115,
            rotate: 4,
            duration: 0.8,
            ease: 'power4.out',
            stagger: 0.04,
            delay: Number(el.dataset.delay || 0),
            scrollTrigger: { trigger: el, start: 'top 94%', once: true },
        });
    });
}

/* ---------- Reveals ---------- */

function reveals(root) {
    gsap.utils.toArray('[data-reveal]', root).filter(belowFold).forEach((el) => {
        gsap.fromTo(el, { autoAlpha: 0, y: 28 }, {
            autoAlpha: 1, y: 0, duration: 0.8, ease: 'power3.out', delay: Number(el.dataset.delay || 0),
            scrollTrigger: { trigger: el, start: 'top 94%', once: true },
        });
    });

    // Product cards tip up into place in rows, wherever they appear.
    const cards = gsap.utils.toArray('.product-card', root).filter(belowFold);
    if (cards.length) {
        gsap.set(cards, { autoAlpha: 0, y: 40, rotateX: -12, transformPerspective: 900, transformOrigin: '50% 100%' });
        ScrollTrigger.batch(cards, {
            start: 'top 97%',
            once: true,
            onEnter: (batch) => gsap.to(batch, {
                autoAlpha: 1, y: 0, rotateX: 0, duration: 0.7, ease: 'power3.out', stagger: 0.05, overwrite: true,
                clearProps: 'transform,transformPerspective',
            }),
        });
    }

    gsap.utils.toArray('[data-reveal-group]', root).filter(belowFold).forEach((group) => {
        const items = [...group.children];
        gsap.set(items, { autoAlpha: 0, y: 26 });
        ScrollTrigger.create({
            trigger: group,
            start: 'top 94%',
            once: true,
            onEnter: () => gsap.to(items, { autoAlpha: 1, y: 0, duration: 0.7, ease: 'power3.out', stagger: 0.06 }),
        });
    });
}

function counters() {
    gsap.utils.toArray('[data-count]').forEach((el) => {
        const end = Number(el.dataset.count);
        const state = { value: 0 };
        gsap.to(state, {
            value: end, duration: 1.6, ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 95%', once: true },
            onUpdate: () => (el.textContent = Math.round(state.value).toLocaleString('en-PK')),
        });
    });
}

/* ---------- Marquee that leans into the scroll direction ---------- */

function marquee() {
    gsap.utils.toArray('[data-marquee]').forEach((el) => {
        const track = el.firstElementChild;
        if (!track || track.dataset.cloned) return;
        track.dataset.cloned = '1';
        track.append(...[...track.children].map((node) => {
            const copy = node.cloneNode(true);
            copy.setAttribute('aria-hidden', 'true');
            return copy;
        }));
        const loop = gsap.to(track, { xPercent: -50, duration: 34, ease: 'none', repeat: -1 });
        const skew = gsap.quickTo(track, 'skewX', { duration: 0.5, ease: 'power3.out' });
        ScrollTrigger.create({
            trigger: el,
            start: 'top bottom',
            end: 'bottom top',
            onUpdate(self) {
                const v = gsap.utils.clamp(-1, 1, self.getVelocity() / 1800);
                loop.timeScale(1 + Math.abs(v) * 3.5);
                skew(v * -8);
            },
            onToggle: (self) => (self.isActive ? loop.play() : loop.pause()),
        });
    });
}

function parallax() {
    gsap.utils.toArray('[data-parallax]').forEach((el) => {
        const amount = Number(el.dataset.parallax) || 12;
        gsap.fromTo(el, { yPercent: amount }, {
            yPercent: -amount, ease: 'none',
            scrollTrigger: { trigger: el.closest('section') || el, start: 'top bottom', end: 'bottom top', scrub: true },
        });
    });
}

/* ---------- Pointer tilt with a moving highlight ---------- */

function tilt(root) {
    const max = 8;
    const enter = (e) => {
        const el = e.currentTarget;
        el._rect = el.getBoundingClientRect();
    };
    const move = (e) => {
        const el = e.currentTarget;
        const r = el._rect || el.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;
        const py = (e.clientY - r.top) / r.height;
        gsap.to(el, { rotateY: (px - 0.5) * 2 * max, rotateX: (0.5 - py) * 2 * max, transformPerspective: 900, duration: 0.5, ease: 'power3.out' });
        el.style.setProperty('--gx', `${px * 100}%`);
        el.style.setProperty('--gy', `${py * 100}%`);
    };
    const leave = (e) => gsap.to(e.currentTarget, { rotateX: 0, rotateY: 0, duration: 0.7, ease: 'elastic.out(1, 0.6)' });

    const nodes = gsap.utils.toArray('[data-tilt]', root);
    nodes.forEach((el) => {
        el.addEventListener('pointerenter', enter);
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerleave', leave);
    });
    return () => nodes.forEach((el) => {
        el.removeEventListener('pointerenter', enter);
        el.removeEventListener('pointermove', move);
        el.removeEventListener('pointerleave', leave);
    });
}
