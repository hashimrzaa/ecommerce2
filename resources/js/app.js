import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { startMotion, stopMotion, refreshMotion } from './motion';

Alpine.plugin(collapse);

const toast = (message, error = false) => window.dispatchEvent(new CustomEvent('toast', { detail: { message, error } }));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ==========================================================================
   Cart: lives in a slide-over drawer and is updated in the background, so
   adding to the cart never interrupts browsing.
   ========================================================================== */

Alpine.store('cart', {
    open: false,
    html: '',
    count: 0,
    subtotal: '',
    busy: false,
    fresh: false,

    /** Each rendered page reports the server's numbers; the drawer body is fetched on demand. */
    sync(count, subtotal) {
        this.count = count;
        this.subtotal = subtotal;
        this.fresh = false;
    },
    apply(data) {
        if (data.html !== undefined) this.html = data.html;
        this.count = data.count;
        this.subtotal = data.subtotal;
        this.fresh = true;
    },
    async show() {
        this.open = true;
        if (!this.fresh) await this.refresh();
    },
    async refresh() {
        this.busy = true;
        try {
            const res = await fetch('/cart/drawer', { headers: { Accept: 'application/json' } });
            this.apply(await res.json());
        } catch {
            toast('Could not load your cart. Please try again.', true);
        } finally {
            this.busy = false;
        }
    },
    async send(form, submitter) {
        if (this.busy) return;
        this.busy = true;
        submitter?.setAttribute('aria-busy', 'true');
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form, submitter),
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.status === 419) return window.location.reload();
            const data = await res.json();
            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                toast(data.error || first || data.message || 'Something went wrong. Please try again.', true);
                if (data.count !== undefined) this.apply(data);
                return;
            }
            this.apply(data);
            if (!form.hasAttribute('data-cart-quiet')) this.open = true;
            // The cart and checkout pages show the same data in the page itself: keep them in step.
            if (/^\/(cart|checkout)$/.test(window.location.pathname)) Turbo.visit(window.location.href, { action: 'replace' });
        } catch {
            toast('Could not update your cart. Check your connection and try again.', true);
        } finally {
            this.busy = false;
            submitter?.removeAttribute('aria-busy');
        }
    },
});

// Any form marked data-cart-form is sent in the background (Buy Now still goes straight to checkout).
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-cart-form')) return;
    if (event.submitter?.name === 'buy_now') return;
    event.preventDefault();
    event.stopPropagation();
    Alpine.store('cart').send(form, event.submitter);
}, true);

/* ==========================================================================
   Search: recent searches, trending terms and live results with photos.
   ========================================================================== */

const RECENT_KEY = 'recent-searches';
const readRecent = () => {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').filter((v) => typeof v === 'string').slice(0, 6);
    } catch {
        return [];
    }
};

Alpine.data('searchBox', (endpoint, initial = '') => ({
    q: initial,
    open: false,
    loading: false,
    products: [],
    categories: [],
    recent: [],
    active: -1,
    timer: null,
    controller: null,

    init() {
        this.recent = readRecent();
    },
    get typing() {
        return this.q.trim().length > 1;
    },
    get items() {
        return [...this.categories.map((c) => c.url), ...this.products.map((p) => p.url)];
    },
    focus() {
        this.open = true;
        if (this.typing && !this.products.length && !this.categories.length) this.fetch();
    },
    onInput() {
        clearTimeout(this.timer);
        this.active = -1;
        this.open = true;
        if (!this.typing) {
            this.products = [];
            this.categories = [];
            this.loading = false;
            return;
        }
        this.loading = true;
        this.timer = setTimeout(() => this.fetch(), 200);
    },
    async fetch() {
        this.controller?.abort();
        this.controller = new AbortController();
        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(this.q.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const data = await res.json();
            this.products = data.products;
            this.categories = data.categories;
        } catch (e) {
            if (e.name !== 'AbortError') this.products = this.categories = [];
        } finally {
            this.loading = false;
        }
    },
    move(step) {
        if (!this.open || !this.items.length) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
    },
    remember(term = this.q) {
        term = term.trim();
        if (term.length < 2) return;
        this.recent = [term, ...this.recent.filter((v) => v.toLowerCase() !== term.toLowerCase())].slice(0, 6);
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
        } catch {
            /* private mode */
        }
    },
    forget(term) {
        this.recent = this.recent.filter((v) => v !== term);
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
        } catch {
            /* private mode */
        }
    },
    submit(event) {
        if (this.open && this.active >= 0) {
            event.preventDefault();
            Turbo.visit(this.items[this.active]);
            return;
        }
        if (!this.q.trim()) {
            event.preventDefault();
            return;
        }
        this.remember();
        this.open = false;
    },
}));

/* Saved product ids for this visitor; product cards read it so their HTML can be shared between visitors. */
Alpine.store('wish', { ids: [] });

/* Wishlist heart — toggles without a page reload and keeps the header count in sync. */
Alpine.data('wishlistButton', (url, id) => ({
    active: Alpine.store('wish').ids.includes(id),
    busy: false,
    async toggle() {
        if (this.busy) return;
        this.busy = true;
        this.active = !this.active;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            this.active = data.active;
            const wish = Alpine.store('wish');
            wish.ids = data.active ? [...wish.ids, id] : wish.ids.filter((v) => v !== id);
            window.dispatchEvent(new CustomEvent('wishlist-count', { detail: data.count }));
            toast(data.active ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
        } catch {
            this.active = !this.active;
            toast('Could not update your wishlist. Please try again.', true);
        } finally {
            this.busy = false;
        }
    },
}));

/* Toast stack — confirms every action so the shopper is never left guessing. */
Alpine.data('toasts', (initial = []) => ({
    list: [],
    init() {
        initial.forEach((t) => this.push(t));
    },
    push(item) {
        const id = Date.now() + Math.random();
        this.list.push({ id, ...item });
        setTimeout(() => this.dismiss(id), item.error ? 7000 : 4500);
    },
    dismiss(id) {
        this.list = this.list.filter((t) => t.id !== id);
    },
}));

/* ==========================================================================
   Homepage showcase: a 3D carousel of the best products that plays by itself,
   can be swiped, and updates the product details beside it.
   ========================================================================== */

Alpine.data('showcase', (total) => ({
    active: 0,
    total,
    paused: false,
    timer: null,
    startX: null,

    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || total < 2) return;
        this.timer = setInterval(() => {
            if (!this.paused && document.visibilityState === 'visible') this.next();
        }, 4200);
    },
    destroy() {
        clearInterval(this.timer);
    },
    next() {
        this.active = (this.active + 1) % this.total;
    },
    prev() {
        this.active = (this.active - 1 + this.total) % this.total;
    },
    offset(i) {
        let d = i - this.active;
        if (d > this.total / 2) d -= this.total;
        if (d < -this.total / 2) d += this.total;
        return d;
    },
    card(i) {
        const d = this.offset(i);
        const a = Math.abs(d);
        return {
            transform: `translateX(calc(${d} * var(--step))) translateZ(calc(${a} * var(--depth))) rotateY(${d * -30}deg)`,
            opacity: a > 2 ? 0 : 1 - a * 0.22,
            zIndex: 10 - a,
            pointerEvents: a > 2 ? 'none' : 'auto',
        };
    },
    pick(i, event) {
        if (i === this.active) return;
        event.preventDefault();
        this.active = i;
    },
    down(event) {
        this.startX = event.clientX;
    },
    up(event) {
        if (this.startX === null) return;
        const dx = event.clientX - this.startX;
        this.startX = null;
        if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
    },
}));

/*
 | Product gallery: hover magnifier and a full-screen viewer (zoom, drag, pinch).
 */
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

Alpine.data('gallery', (images) => ({
    images,
    slide: 0,
    // hover magnifier
    zoomed: false,
    ox: 50,
    oy: 50,
    // full-screen viewer
    box: false,
    scale: 1,
    x: 0,
    y: 0,
    drag: null,
    pinch: null,
    pointers: new Map(),

    init() {
        this.$watch('box', (open) => document.body.classList.toggle('overflow-hidden', open));
    },
    destroy() {
        document.body.classList.remove('overflow-hidden');
    },
    get current() {
        return typeof this.slide === 'number' ? this.images[this.slide] ?? null : null;
    },

    hover(event) {
        if (!finePointer || !this.current) return;
        const r = event.currentTarget.getBoundingClientRect();
        this.ox = (((event.clientX - r.left) / r.width) * 100).toFixed(1);
        this.oy = (((event.clientY - r.top) / r.height) * 100).toFixed(1);
        this.zoomed = true;
    },

    open() {
        if (!this.current) return;
        this.zoomed = false;
        this.reset();
        this.box = true;
    },
    reset() {
        this.scale = 1;
        this.x = 0;
        this.y = 0;
    },
    zoom(factor) {
        this.scale = clamp(this.scale * factor, 1, 5);
        if (this.scale === 1) this.x = this.y = 0;
    },
    step(n) {
        this.slide = ((typeof this.slide === 'number' ? this.slide : 0) + n + this.images.length) % this.images.length;
        this.reset();
    },
    spread() {
        const [a, b] = [...this.pointers.values()];
        return Math.hypot(a.x - b.x, a.y - b.y);
    },
    pdown(event) {
        event.currentTarget.setPointerCapture(event.pointerId);
        this.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        if (this.pointers.size === 2) this.pinch = { distance: this.spread(), scale: this.scale };
        else this.drag = { x: event.clientX - this.x, y: event.clientY - this.y };
    },
    pmove(event) {
        if (!this.pointers.has(event.pointerId)) return;
        this.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        if (this.pointers.size === 2 && this.pinch) {
            this.scale = clamp(this.pinch.scale * (this.spread() / this.pinch.distance), 1, 5);
        } else if (this.drag && this.scale > 1) {
            this.x = event.clientX - this.drag.x;
            this.y = event.clientY - this.drag.y;
        }
    },
    pup(event) {
        this.pointers.delete(event.pointerId);
        this.pinch = null;
        if (!this.pointers.size) this.drag = null;
        if (this.scale === 1) this.x = this.y = 0;
    },

}));

/*
 | Find my size. The shopper's height, weight and preferred fit are kept on their own device,
 | so every later product can show "Your size here" without asking again.
 */
const FIT_KEY = 'fit-profile';
const savedFit = (() => {
    try {
        return JSON.parse(localStorage.getItem(FIT_KEY) || 'null');
    } catch {
        return null;
    }
})();

Alpine.store('fit', {
    height: savedFit?.height ?? 165,
    weight: savedFit?.weight ?? 62,
    pref: savedFit?.pref ?? 'regular',
    age: savedFit?.age ?? 6,
    kidHeight: savedFit?.kidHeight ?? 115,
    saved: !!savedFit,
    save() {
        this.saved = true;
        try {
            localStorage.setItem(FIT_KEY, JSON.stringify({ height: this.height, weight: this.weight, pref: this.pref, age: this.age, kidHeight: this.kidHeight }));
        } catch {
            /* private mode */
        }
    },
});

const ADULT_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
const KID_SIZES = ['0–2 Years', '3–5 Years', '6–8 Years', '9–12 Years', '13–16 Years'];
// Upper weight (kg, for someone 165 cm tall) that still fits each size.
const WEIGHT_LIMITS = { women: [47, 55, 63, 72, 82, 93], men: [54, 63, 73, 83, 94, 106] };
// Tallest child (cm) each age size is cut for.
const KID_HEIGHTS = [92, 110, 128, 152, 170];

/** Returns { ideal, pick, exact, scale } for a product's size list, or null when it cannot be worked out. */
window.fitSize = (sizes, kind, p) => {
    if (kind === 'kids') {
        const scale = KID_SIZES.filter((s) => sizes.includes(s));
        if (!scale.length) return null;
        let i = p.age <= 2 ? 0 : p.age <= 5 ? 1 : p.age <= 8 ? 2 : p.age <= 12 ? 3 : 4;
        // A child taller than the age band is cut for moves up one size.
        if (p.kidHeight > KID_HEIGHTS[i] && i < 4) i++;
        const ideal = KID_SIZES[i];
        const pick = scale.reduce((best, s) => (Math.abs(KID_SIZES.indexOf(s) - i) < Math.abs(KID_SIZES.indexOf(best) - i) ? s : best), scale[0]);
        return { ideal, pick, exact: pick === ideal, scale };
    }
    const scale = ADULT_SIZES.filter((s) => sizes.includes(s));
    if (!scale.length) return null;
    // Taller people carry the same weight on a longer frame, so height shifts the weight bands.
    const adjusted = p.weight - (p.height - 165) * 0.45;
    let i = WEIGHT_LIMITS[kind === 'men' ? 'men' : 'women'].findIndex((limit) => adjusted <= limit);
    if (i === -1) i = 6;
    if (p.pref === 'relaxed') i = Math.min(i + 1, 6);
    if (p.pref === 'fitted' && adjusted <= (WEIGHT_LIMITS[kind === 'men' ? 'men' : 'women'][i] ?? 999) - 4) i = Math.max(i - 1, 0);
    const ideal = ADULT_SIZES[i];
    const pick = scale.reduce((best, s) => (Math.abs(ADULT_SIZES.indexOf(s) - i) < Math.abs(ADULT_SIZES.indexOf(best) - i) ? s : best), scale[0]);
    return { ideal, pick, exact: pick === ideal, scale };
};

/* Deal explorer on the homepage: tabs that rotate by themselves until the shopper takes over. */
Alpine.data('dealTabs', (total) => ({
    tab: 0,
    total,
    paused: false,
    timer: null,

    init() {
        this.play();
    },
    destroy() {
        clearInterval(this.timer);
    },
    play() {
        clearInterval(this.timer);
        if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        this.timer = setInterval(() => {
            if (!this.paused && document.visibilityState === 'visible') this.tab = (this.tab + 1) % this.total;
        }, 5000);
    },
    pick(i) {
        if (i === this.tab) return;
        this.tab = i;
        this.play();
    },
}));

/*
 | Catalogue filters. The filter panel is not re-rendered when results refresh, so the open
 | sections, the scroll position and what the shopper ticked all stay exactly as they were.
 */
Alpine.data('catalogFilters', (total, active) => ({
    sheet: false,
    total,
    active,
    timer: null,
    onLoad: null,

    init() {
        this.onLoad = (event) => {
            if (event.target.id !== 'results') return;
            const meta = event.target.querySelector('[data-total]');
            if (!meta) return;
            this.total = Number(meta.dataset.total);
            this.active = Number(meta.dataset.active);
        };
        document.addEventListener('turbo:frame-load', this.onLoad);
        this.$watch('sheet', (open) => document.body.classList.toggle('overflow-hidden', open));
    },
    destroy() {
        document.removeEventListener('turbo:frame-load', this.onLoad);
        document.body.classList.remove('overflow-hidden');
    },
    submit() {
        clearTimeout(this.timer);
        this.$refs.form.requestSubmit();
    },
    /** A short pause groups quick successive clicks into one request. */
    queue() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.submit(), 140);
    },
    remove(field, value) {
        if (field === 'price') {
            window.dispatchEvent(new CustomEvent('price-reset'));
        } else {
            [...this.$refs.form.elements].forEach((el) => {
                if (el.name === field && (value === null || el.value === value)) el.checked = false;
            });
        }
        this.$nextTick(() => this.submit());
    },
    clear() {
        [...this.$refs.form.elements].forEach((el) => {
            if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
        });
        window.dispatchEvent(new CustomEvent('price-reset'));
        this.$nextTick(() => this.submit());
    },
}));

/* Two-thumb price slider used by the catalogue filters. */
Alpine.data('priceRange', (floor, ceil, min, max) => ({
    floor,
    ceil,
    lo: min ?? floor,
    hi: max ?? ceil,
    get changed() {
        return this.lo > this.floor || this.hi < this.ceil;
    },
    get left() {
        return ((this.lo - this.floor) / Math.max(this.ceil - this.floor, 1)) * 100;
    },
    get right() {
        return 100 - ((this.hi - this.floor) / Math.max(this.ceil - this.floor, 1)) * 100;
    },
    clamp(which) {
        const gap = Math.max(Math.round((this.ceil - this.floor) / 40), 1);
        this.lo = Number(this.lo);
        this.hi = Number(this.hi);
        if (which === 'lo' && this.lo > this.hi - gap) this.lo = this.hi - gap;
        if (which === 'hi' && this.hi < this.lo + gap) this.hi = this.lo + gap;
    },
}));

/*
 | QR card: draws the product's QR code with its name and price onto a picture that can be
 | shared (phones) or downloaded and printed.
 */
window.qrCard = async (src, name, price, link, file, mode) => {
    try {
        const svg = await (await fetch(src)).text();
        const image = new Image();
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = reject;
            image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
        });
        const canvas = document.createElement('canvas');
        canvas.width = 900;
        canvas.height = 1120;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(image, 90, 70, 720, 720);
        ctx.fillStyle = '#0d1412';
        ctx.textAlign = 'center';
        ctx.font = '700 44px Inter, system-ui, sans-serif';
        const title = name.length > 34 ? name.slice(0, 33) + '…' : name;
        ctx.fillText(title, 450, 880);
        ctx.font = '700 52px Inter, system-ui, sans-serif';
        ctx.fillStyle = '#0b6b4f';
        ctx.fillText(price, 450, 960);
        ctx.font = '400 30px Inter, system-ui, sans-serif';
        ctx.fillStyle = '#68716d';
        ctx.fillText('Scan to view and order', 450, 1030);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        const picture = new File([blob], file, { type: 'image/png' });

        if (mode === 'share' && navigator.canShare?.({ files: [picture] })) {
            try {
                await navigator.share({ files: [picture], title: name, text: `${name} — ${link}` });
            } catch {
                /* dismissed */
            }
            return;
        }
        if (mode === 'share') {
            // No file sharing on this device: share or copy the link, and save the picture as well.
            await window.shareProduct(name, link);
        }
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = file;
        a.click();
        setTimeout(() => URL.revokeObjectURL(a.href), 2000);
        if (mode !== 'share') toast('QR code saved to your downloads.');
    } catch {
        toast('Could not create the QR picture. Please try again.', true);
    }
};

/* Share: native share sheet on phones, copy-link fallback on desktop. */
window.shareProduct = async (title, url) => {
    if (navigator.share) {
        try {
            await navigator.share({ title, url });
        } catch {
            /* dismissed */
        }
        return;
    }
    try {
        await navigator.clipboard.writeText(url);
        toast('Link copied to clipboard.');
    } catch {
        window.prompt('Copy this link', url);
    }
};

/* Keep filter and search addresses clean: empty fields are not sent. */
document.addEventListener('formdata', (event) => {
    if ((event.target.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
    for (const [key, value] of [...event.formData.entries()]) {
        if (value === '') event.formData.delete(key);
    }
});

/* Prevent double submits on slow connections. */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.dataset.once === undefined) return;
    if (form.dataset.submitted) {
        event.preventDefault();
        return;
    }
    form.dataset.submitted = '1';
    form.querySelectorAll('[type="submit"], button:not([type])').forEach((button) => button.setAttribute('aria-disabled', 'true'));
});

/* ==========================================================================
   Page changes without a full reload (Turbo): links and forms are fetched in
   the background and only the page body is swapped.
   ========================================================================== */

/* The showcase fills the screen under the header, so it needs the header's real height. */
const measureHeader = () => {
    const header = document.querySelector('[data-header]');
    if (header) document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
};
window.addEventListener('resize', measureHeader);

let started = false;
const boot = () => {
    started = true;
    measureHeader();
    startMotion();
};
document.addEventListener('turbo:render', boot);
// First page: start as soon as the document is parsed rather than waiting for every image to load.
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => started || boot());
else queueMicrotask(() => started || boot());
document.addEventListener('turbo:before-render', stopMotion);
document.addEventListener('turbo:before-visit', () => (Alpine.store('cart').open = false));
document.addEventListener('turbo:frame-load', (event) => refreshMotion(event.target));
// A failed background request (offline, server error) falls back to a normal page load.
document.addEventListener('turbo:fetch-request-error', (event) => {
    const url = event.detail?.request?.url;
    if (url && event.target === document.documentElement) window.location.href = url;
});

// Show the loading bar quickly so a click always gets an immediate response.
Turbo.config.drive.progressBarDelay = 120;

window.Alpine = Alpine;
window.Turbo = Turbo;
Alpine.start();
