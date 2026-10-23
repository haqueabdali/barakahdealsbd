/* Barakah Deals BD — single-page storefront (no build step, no dependencies). */
(function () {
  "use strict";

  const S = window.STORE;

  /*
   * Safety net for hosts that deliver css/style.css with the wrong content type (or block it):
   * if the stylesheet didn't apply, load it through PHP, and as a last resort inject it directly.
   */
  (function ensureStyles() {
    const applied = () => getComputedStyle(document.documentElement).getPropertyValue("--em-800").trim() !== "";
    if (applied()) return;
    const inject = () => {
      if (applied()) return;
      fetch("css/style.css", { cache: "no-store" })
        .then((r) => (r.ok ? r.text() : Promise.reject(r.status)))
        .then((css) => {
          if (css.indexOf(":root") < 0) return;
          const style = document.createElement("style");
          style.textContent = css;
          document.head.appendChild(style);
        })
        .catch(() => {});
    };
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "api/css.php?v=20261023";
    link.onload = inject;
    link.onerror = inject;
    document.head.appendChild(link);
  })();
  const PRODUCTS = window.PRODUCTS;
  const CATEGORIES = window.CATEGORIES;
  const app = document.getElementById("app");

  const DISTRICTS = [
    "Dhaka", "Bagerhat", "Bandarban", "Barguna", "Barishal", "Bhola", "Bogura", "Brahmanbaria", "Chandpur",
    "Chapai Nawabganj", "Chattogram", "Chuadanga", "Cox's Bazar", "Cumilla", "Dinajpur", "Faridpur", "Feni",
    "Gaibandha", "Gazipur", "Gopalganj", "Habiganj", "Jamalpur", "Jashore", "Jhalokati", "Jhenaidah",
    "Joypurhat", "Khagrachhari", "Khulna", "Kishoreganj", "Kurigram", "Kushtia", "Lakshmipur", "Lalmonirhat",
    "Madaripur", "Magura", "Manikganj", "Meherpur", "Moulvibazar", "Munshiganj", "Mymensingh", "Naogaon",
    "Narail", "Narayanganj", "Narsingdi", "Natore", "Netrokona", "Nilphamari", "Noakhali", "Pabna",
    "Panchagarh", "Patuakhali", "Pirojpur", "Rajbari", "Rajshahi", "Rangamati", "Rangpur", "Satkhira",
    "Shariatpur", "Sherpur", "Sirajganj", "Sunamganj", "Sylhet", "Tangail", "Thakurgaon"
  ];

  /* ---------- Storage helpers ---------- */
  const store = {
    get(key, fallback) {
      try {
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : fallback;
      } catch (e) {
        return fallback;
      }
    },
    set(key, value) {
      try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* storage unavailable */ }
    }
  };

  let cart = store.get("bd_cart", []);          // [{ id, qty }]
  let wishlist = store.get("bd_wish", []);      // [id]
  let coupon = store.get("bd_coupon", null);    // code string

  /* ---------- Utilities ---------- */
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const money = (n) => S.currency + Math.round(n).toLocaleString("en-IN");
  const byId = (id) => PRODUCTS.find((p) => p.id === id);
  const catName = (id) => (CATEGORIES.find((c) => c.id === id) || {}).name || id;
  const discount = (p) => (p.oldPrice > p.price ? Math.round((1 - p.price / p.oldPrice) * 100) : 0);
  const stars = (r) => "★".repeat(Math.round(r)) + "☆".repeat(5 - Math.round(r));
  const waLink = (text) => `https://wa.me/${S.whatsapp}?text=${encodeURIComponent(text)}`;
  const msLink = () => `https://m.me/${S.messenger}`;
  const chatButtons = (text) => `<div class="hero-cta">
      <a class="btn btn-ms" target="_blank" rel="noopener" href="${msLink()}">${ICONS.messenger} Inbox on Messenger</a>
      <a class="btn btn-wa" target="_blank" rel="noopener" href="${waLink(text)}">${ICONS.whatsapp} WhatsApp</a>
    </div>`;
  /* Payment methods (managed in Admin → Settings & Payments) */
  const PAY_INFO = {
    cod: { name: "Cash on Delivery", icon: "💵" },
    bkash: { name: "bKash", icon: "🩷" },
    nagad: { name: "Nagad", icon: "🧡" },
    rocket: { name: "Rocket", icon: "💜" }
  };
  function enabledPayments() {
    const p = S.payments || {};
    return Object.keys(PAY_INFO).filter((k) => {
      const cfg = p[k];
      if (k === "cod") return !cfg || cfg.enabled !== false;
      return cfg && (typeof cfg === "string" ? cfg : cfg.enabled && cfg.number);
    }).map((k) => {
      const cfg = typeof p[k] === "string" ? { number: p[k], type: "Personal" } : (p[k] || {});
      return { key: k, ...PAY_INFO[k], number: cfg.number || "", type: cfg.type || "Personal" };
    });
  }
  const payNamesList = () => enabledPayments().map((m) => m.name);

  /* Brand icons (inline SVG, inherit the text colour) */
  const ICONS = {
    facebook: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.6 1.6-1.6h1.7V4.4c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.3H7.4V14h2.8v8h3.3z"/></svg>',
    instagram: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>',
    youtube: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5.2 3L10 15z"/></svg>',
    tiktok: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.7a5.8 5.8 0 1 0 5 5.7V9.1a7.4 7.4 0 0 0 4.3 1.4V7.4a4.3 4.3 0 0 1-3.3-1.6z"/></svg>',
    messenger: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 2C6.4 2 2 6.1 2 11.7c0 2.9 1.2 5.5 3.2 7.2v3.1l3-1.6c.9.2 1.8.4 2.8.4 5.6 0 10-4.1 10-9.7S17.6 2 12 2zm1 13.1-2.6-2.7-5 2.7 5.5-5.8 2.6 2.7 4.9-2.7-5.4 5.8z"/></svg>',
    whatsapp: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.5-.6-2.6-1.1-4.3-3.8-4.5-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.1.1.3 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 1.9 1.1.9 2 1.2 2.3 1.4.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg>'
  };
  const SOCIAL_NAMES = { facebook: "Facebook", instagram: "Instagram", youtube: "YouTube", tiktok: "TikTok" };
  const socialEntries = () => Object.entries(S.social || {}).filter(([k, url]) => url && ICONS[k]);
  const isValidPhone = (v) => /^(?:\+?88)?01[3-9]\d{8}$/.test(v.replace(/[\s-]/g, ""));

  function toast(msg, type = "ok") {
    const el = document.createElement("div");
    el.className = "toast " + type;
    el.textContent = msg;
    document.getElementById("toasts").appendChild(el);
    setTimeout(() => el.remove(), 2600);
  }

  function thumb(p, extraClass = "") {
    const inner = p.image
      ? `<img src="${esc(p.image)}" alt="${esc(p.name)}" loading="lazy">`
      : `<span aria-hidden="true">${p.icon || "🛍️"}</span>`;
    return `<div class="thumb c-${esc(p.category)} ${p.image ? "has-img" : ""} ${extraClass}">${inner}</div>`;
  }

  /* ---------- Cart logic ---------- */
  function saveCart() {
    store.set("bd_cart", cart);
    updateBadges();
  }

  function addToCart(id, qty = 1) {
    const p = byId(id);
    if (!p) return;
    if (p.stock <= 0) { toast("Sorry, this item is out of stock", "err"); return; }
    const line = cart.find((l) => l.id === id);
    const current = line ? line.qty : 0;
    const next = Math.min(current + qty, p.stock);
    if (next === current) { toast(`Only ${p.stock} in stock`, "err"); return; }
    if (line) line.qty = next; else cart.push({ id, qty: next });
    saveCart();
    toast(`Added to cart: ${p.name}`);
  }

  function setQty(id, qty) {
    const p = byId(id);
    const line = cart.find((l) => l.id === id);
    if (!line || !p) return;
    line.qty = Math.max(1, Math.min(qty, p.stock));
    saveCart();
  }

  function removeFromCart(id) {
    cart = cart.filter((l) => l.id !== id);
    saveCart();
  }

  function cartLines() {
    // Drop lines whose product no longer exists in the catalog
    return cart.map((l) => ({ ...l, product: byId(l.id) })).filter((l) => l.product);
  }

  function couponDiscount(subtotal, code) {
    const c = code && S.coupons[code];
    if (!c || subtotal < c.minOrder) return 0;
    return c.type === "percent" ? Math.round(subtotal * c.value / 100) : Math.min(c.value, subtotal);
  }

  function totals(area) {
    const lines = cartLines();
    const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);
    const disc = couponDiscount(subtotal, coupon);
    const freeShip = S.delivery.freeAbove > 0 && subtotal >= S.delivery.freeAbove;
    const shipping = !lines.length || freeShip ? 0 : (area === "outside" ? S.delivery.outsideDhaka : S.delivery.insideDhaka);
    return { lines, subtotal, disc, shipping, freeShip, total: subtotal - disc + shipping, count: lines.reduce((s, l) => s + l.qty, 0) };
  }

  function toggleWish(id) {
    const p = byId(id);
    if (wishlist.includes(id)) {
      wishlist = wishlist.filter((w) => w !== id);
      toast("Removed from wishlist");
    } else {
      wishlist.push(id);
      toast(`Saved to wishlist: ${p ? p.name : ""}`);
    }
    store.set("bd_wish", wishlist);
    updateBadges();
  }

  function updateBadges() {
    const count = cart.reduce((s, l) => s + l.qty, 0);
    const cc = document.getElementById("cartCount");
    const wc = document.getElementById("wishCount");
    cc.textContent = count; cc.dataset.zero = count === 0;
    wc.textContent = wishlist.length; wc.dataset.zero = wishlist.length === 0;
  }

  /* ---------- Shared components ---------- */
  function productCard(p) {
    const d = discount(p);
    const wished = wishlist.includes(p.id);
    return `
      <article class="card">
        ${p.stock <= 0 ? `<span class="tag oos">Out of stock</span>` : d ? `<span class="tag">-${d}%</span>` : ""}
        <button class="wish-btn" data-wish="${esc(p.id)}" aria-label="Toggle wishlist">${wished ? "♥" : "♡"}</button>
        <a href="#/product/${esc(p.id)}">${thumb(p)}</a>
        <div class="card-body">
          <a href="#/product/${esc(p.id)}" class="card-title">${esc(p.name)}</a>
          <div class="rating">${stars(p.rating)} <span>(${p.reviews})</span></div>
          <div class="price"><b>${money(p.price)}</b>${p.oldPrice > p.price ? `<s>${money(p.oldPrice)}</s>` : ""}</div>
          <button class="btn btn-primary" data-add="${esc(p.id)}" ${p.stock <= 0 ? "disabled" : ""}>
            ${p.stock <= 0 ? "Sold out" : "🛒 Add to cart"}
          </button>
        </div>
      </article>`;
  }

  const grid = (list) => `<div class="grid">${list.map(productCard).join("")}</div>`;

  function emptyState(icon, title, text, cta = `<a href="#/shop" class="btn btn-primary">Start shopping</a>`) {
    return `<div class="empty"><div class="big">${icon}</div><h2>${title}</h2><p class="muted">${text}</p>${cta}</div>`;
  }

  function pageTitle(title, crumb) {
    return `<div class="container page-title"><div class="crumbs"><a href="#/">Home</a> / ${crumb || esc(title)}</div><h1>${esc(title)}</h1></div>`;
  }

  /* ---------- Flash sale countdown ---------- */
  let countdownTimer = null;
  function startCountdown() {
    clearInterval(countdownTimer);
    const tick = () => {
      const el = document.getElementById("countdown");
      if (!el) { clearInterval(countdownTimer); return; }
      const now = new Date();
      const end = new Date(now);
      end.setHours(S.flashSaleEndHour, 59, 59, 999);
      if (end <= now) end.setDate(end.getDate() + 1);
      let diff = Math.floor((end - now) / 1000);
      const h = Math.floor(diff / 3600); diff %= 3600;
      const m = Math.floor(diff / 60); const s = diff % 60;
      const pad = (n) => String(n).padStart(2, "0");
      el.innerHTML = `<div><b>${pad(h)}</b><small>Hours</small></div><div><b>${pad(m)}</b><small>Mins</small></div><div><b>${pad(s)}</b><small>Secs</small></div>`;
    };
    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  /* ---------- Pages ---------- */
  function pageHome() {
    const flash = PRODUCTS.filter((p) => p.tags.includes("flash"));
    const featured = PRODUCTS.filter((p) => p.tags.includes("featured"));
    const fresh = PRODUCTS.filter((p) => p.tags.includes("new"));
    const firstCoupon = Object.keys(S.coupons)[0];
    const fc = firstCoupon && S.coupons[firstCoupon];

    app.innerHTML = `
      <section class="hero">
        <div class="container hero-inner">
          <div>
            <div class="hero-brand">
              <img src="images/logo.png" alt="" width="74" height="74">
              <div><span class="eyebrow">${esc(S.tagline.toUpperCase())}</span><span class="bn">${esc(S.taglineBn)}</span></div>
            </div>
            <h1><span class="gold">BARAKAH</span><span class="cream">DEALS BD</span></h1>
            <ul class="checks">
              <li>Cosmetics • Shampoo • Lotion</li>
              <li>Baby Food &amp; Baby Care</li>
              <li>Rich Food &amp; Medical Equipment</li>
            </ul>
            <div class="hero-cta">
              <a href="#/shop" class="btn btn-gold">Shop Now →</a>
              <a href="${msLink()}" target="_blank" rel="noopener" class="btn btn-outline-gold">✓ Inbox to Order</a>
            </div>
          </div>
          <div class="hero-visual">
            <img src="images/hero-products.webp" alt="Cosmetics, baby food, royal jelly honey and a blood pressure monitor" width="900" height="570">
            <div class="flash-card">
              <b>🔥 FLASH SALE ENDS IN</b>
              <div class="countdown" id="countdown"></div>
            </div>
          </div>
        </div>
      </section>

      <div class="container">
        <div class="trust">
          <div class="trust-item"><span>🚚</span><div><b>Nationwide Delivery</b><small>All 64 districts</small></div></div>
          <div class="trust-item"><span>💵</span><div><b>Cash on Delivery</b><small>Pay when you receive</small></div></div>
          <div class="trust-item"><span>✅</span><div><b>100% Original</b><small>Imported &amp; quality checked</small></div></div>
          <div class="trust-item"><span>↩️</span><div><b>Easy Returns</b><small>7-day return policy</small></div></div>
        </div>

        <section class="section">
          <div class="section-head"><h2>Shop by Category<small>Find what you need, faster</small></h2></div>
          <div class="cat-grid">
            ${CATEGORIES.map((c) => {
              const n = PRODUCTS.filter((p) => p.category === c.id).length;
              return `
              <a class="cat-card" href="#/shop?cat=${esc(c.id)}">
                ${n ? "" : `<span class="soon">Coming soon</span>`}
                <div class="ico">${c.icon}</div>
                <b>${esc(c.name)}</b>
                <small>${n ? `${n} product${n === 1 ? "" : "s"}` : "Inbox to order"}</small>
              </a>`;
            }).join("")}
          </div>
        </section>

        <section class="section">
          <div class="section-head"><h2>🔥 Flash Deals<small>Limited time, limited stock</small></h2><a class="link-more" href="#/shop?tag=flash">View all →</a></div>
          ${grid(flash.slice(0, 5))}
        </section>

        ${fc ? `
        <div class="promo">
          <div><h3>Extra ${fc.type === "percent" ? fc.value + "%" : money(fc.value)} OFF your order</h3>
          <div>Use code <code>${esc(firstCoupon)}</code> at checkout on orders over ${money(fc.minOrder)}.</div></div>
          <a href="#/shop" class="btn btn-gold">Grab the deal</a>
        </div>` : ""}

        <section class="section">
          <div class="section-head"><h2>⭐ Top Picks<small>Our customers' favourites</small></h2><a class="link-more" href="#/shop?tag=featured">View all →</a></div>
          ${grid(featured.slice(0, 10))}
        </section>

        <section class="section">
          <div class="section-head"><h2>🆕 New Arrivals<small>Freshly added to the store</small></h2><a class="link-more" href="#/shop?tag=new">View all →</a></div>
          ${grid(fresh.slice(0, 5))}
        </section>

        <div class="promo inbox">
          <div><h3>Can't find what you need?</h3>
          <div>Inbox us — we source original cosmetics, baby care, rich food and medical equipment on request. Follow <b>${esc(S.handle)}</b> for daily deals.</div></div>
          ${chatButtons("Assalamu Alaikum! I'm looking for a product.")}
        </div>
      </div>`;
    startCountdown();
  }

  function pageShop(params) {
    const q = (params.get("q") || "").trim();
    const cat = params.get("cat") || "";
    const tag = params.get("tag") || "";
    const sort = params.get("sort") || "popular";
    const min = Number(params.get("min")) || 0;
    const max = Number(params.get("max")) || 0;
    const instock = params.get("instock") === "1";

    let list = PRODUCTS.filter((p) => {
      if (cat && p.category !== cat) return false;
      if (tag && !p.tags.includes(tag)) return false;
      if (min && p.price < min) return false;
      if (max && p.price > max) return false;
      if (instock && p.stock <= 0) return false;
      if (q) {
        const hay = `${p.name} ${p.description} ${catName(p.category)}`.toLowerCase();
        if (!q.toLowerCase().split(/\s+/).every((w) => hay.includes(w))) return false;
      }
      return true;
    });

    const sorters = {
      popular: (a, b) => b.reviews - a.reviews,
      "price-asc": (a, b) => a.price - b.price,
      "price-desc": (a, b) => b.price - a.price,
      rating: (a, b) => b.rating - a.rating,
      discount: (a, b) => discount(b) - discount(a)
    };
    list = list.slice().sort(sorters[sort] || sorters.popular);

    const tagNames = { flash: "Flash Deals", featured: "Top Picks", new: "New Arrivals" };
    const title = q ? `Results for “${q}”` : cat ? catName(cat) : tag ? tagNames[tag] || "Shop" : "All Products";
    const chips = [];
    if (q) chips.push(["q", `Search: ${q}`]);
    if (cat) chips.push(["cat", catName(cat)]);
    if (tag) chips.push(["tag", tagNames[tag] || tag]);
    if (min || max) chips.push(["price", `${money(min)} – ${max ? money(max) : "any"}`]);
    if (instock) chips.push(["instock", "In stock"]);

    app.innerHTML = `
      ${pageTitle(title, `<a href="#/shop">Shop</a>`)}
      <div class="container shop">
        <aside class="filters" id="filters">
          <form id="filterForm">
            <div class="group">
              <h4>Category</h4>
              <label><input type="radio" name="cat" value="" ${!cat ? "checked" : ""}> All categories</label>
              ${CATEGORIES.map((c) => `<label><input type="radio" name="cat" value="${esc(c.id)}" ${cat === c.id ? "checked" : ""}> ${c.icon} ${esc(c.name)}</label>`).join("")}
            </div>
            <div class="group">
              <h4>Collections</h4>
              <label><input type="radio" name="tag" value="" ${!tag ? "checked" : ""}> Everything</label>
              ${Object.entries(tagNames).map(([k, v]) => `<label><input type="radio" name="tag" value="${k}" ${tag === k ? "checked" : ""}> ${v}</label>`).join("")}
            </div>
            <div class="group">
              <h4>Price (${S.currency})</h4>
              <div class="price-row">
                <input type="number" name="min" min="0" placeholder="Min" value="${min || ""}">
                <input type="number" name="max" min="0" placeholder="Max" value="${max || ""}">
              </div>
            </div>
            <div class="group"><label><input type="checkbox" name="instock" value="1" ${instock ? "checked" : ""}> In stock only</label></div>
            <input type="hidden" name="q" value="${esc(q)}">
            <input type="hidden" name="sort" value="${esc(sort)}">
            <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
            <a href="#/shop" class="btn btn-ghost btn-block">Reset</a>
          </form>
        </aside>
        <div>
          <div class="toolbar">
            <div><b>${list.length}</b> <span class="muted">product${list.length === 1 ? "" : "s"} found</span></div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-outline filter-toggle" id="filterToggle" type="button">⚙ Filters</button>
              <select id="sortSelect" aria-label="Sort products">
                <option value="popular">Most popular</option>
                <option value="price-asc">Price: low to high</option>
                <option value="price-desc">Price: high to low</option>
                <option value="rating">Top rated</option>
                <option value="discount">Biggest discount</option>
              </select>
            </div>
          </div>
          ${chips.length ? `<div class="chips" style="margin-bottom:14px">${chips.map(([k, v]) => `<button class="chip" data-clear="${k}">${esc(v)} ✕</button>`).join("")}</div>` : ""}
          ${list.length ? grid(list)
            : cat && !PRODUCTS.some((p) => p.category === cat)
              ? emptyState(CATEGORIES.find((c) => c.id === cat)?.icon || "🛍️", `${esc(catName(cat))} — coming soon`, "We're adding products to this category. Inbox us and we'll arrange what you need.", chatButtons(`Assalamu Alaikum! I'm looking for ${catName(cat)} products.`))
              : emptyState("🔎", "No products found", "Try a different search or clear some filters.", `<a href="#/shop" class="btn btn-primary">Clear filters</a>`)}
        </div>
      </div>`;

    const buildUrl = (p) => {
      [...p.keys()].forEach((k) => { if (!p.get(k)) p.delete(k); });
      const s = p.toString();
      return "#/shop" + (s ? "?" + s : "");
    };

    document.getElementById("sortSelect").value = sort;
    document.getElementById("sortSelect").addEventListener("change", (e) => {
      params.set("sort", e.target.value);
      location.hash = buildUrl(params);
    });
    document.getElementById("filterForm").addEventListener("submit", (e) => {
      e.preventDefault();
      location.hash = buildUrl(new URLSearchParams(new FormData(e.target)));
    });
    document.getElementById("filterToggle").addEventListener("click", () => {
      document.getElementById("filters").classList.toggle("open");
    });
    app.querySelectorAll("[data-clear]").forEach((b) => b.addEventListener("click", () => {
      const k = b.dataset.clear;
      if (k === "price") { params.delete("min"); params.delete("max"); } else params.delete(k);
      location.hash = buildUrl(params);
    }));
  }

  function pageProduct(id) {
    const p = byId(id);
    if (!p) { pageNotFound(); return; }
    const d = discount(p);
    const related = PRODUCTS.filter((x) => x.category === p.category && x.id !== p.id).slice(0, 5);
    const wished = wishlist.includes(p.id);
    const inStock = p.stock > 0;

    app.innerHTML = `
      ${pageTitle(p.name, `<a href="#/shop">Shop</a> / <a href="#/shop?cat=${esc(p.category)}">${esc(catName(p.category))}</a>`).replace(/<h1>.*<\/h1>/, "")}
      <div class="container">
        <div class="pdp">
          <div style="position:relative">${d ? `<span class="tag">-${d}%</span>` : ""}${thumb(p)}</div>
          <div>
            <h1>${esc(p.name)}</h1>
            <div class="rating" style="font-size:15px">${stars(p.rating)} <span>${p.rating} · ${p.reviews} reviews</span></div>
            <div class="price" style="margin:14px 0">
              <b>${money(p.price)}</b>
              ${p.oldPrice > p.price ? `<s>${money(p.oldPrice)}</s><span class="save">Save ${money(p.oldPrice - p.price)}</span>` : ""}
            </div>
            <div>${inStock ? `<span class="stock-ok">● In stock</span> <span class="muted">(${p.stock} available)</span>` : `<span class="stock-no">● Out of stock</span>`}</div>
            <p>${esc(p.description)}</p>
            <ul class="features">${p.features.map((f) => `<li>${esc(f)}</li>`).join("")}</ul>
            <div class="pdp-actions">
              <div class="qty">
                <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                <input type="number" id="qty" value="1" min="1" max="${p.stock}" aria-label="Quantity" ${inStock ? "" : "disabled"}>
                <button type="button" data-step="1" aria-label="Increase quantity">+</button>
              </div>
              <button class="btn btn-primary" id="addBtn" ${inStock ? "" : "disabled"}>🛒 Add to cart</button>
              <button class="btn btn-gold" id="buyBtn" ${inStock ? "" : "disabled"}>⚡ Buy now</button>
            </div>
            <div class="pdp-actions" style="margin-top:0">
              <a class="btn btn-wa" target="_blank" rel="noopener" href="${waLink(`Assalamu Alaikum! I want to order:\n${p.name}\nPrice: ${money(p.price)}\nLink: ${location.href}`)}">${ICONS.whatsapp} WhatsApp</a>
              <a class="btn btn-ms" target="_blank" rel="noopener" href="${msLink()}">${ICONS.messenger} Inbox to order</a>
              <button class="btn btn-outline" data-wish="${esc(p.id)}">${wished ? "♥ Saved" : "♡ Add to wishlist"}</button>
            </div>
            <div class="pdp-meta">
              <div>📦 Category: <a class="link-more" href="#/shop?cat=${esc(p.category)}">${esc(catName(p.category))}</a></div>
              <div>🚚 Delivery: ${money(S.delivery.insideDhaka)} inside Dhaka · ${money(S.delivery.outsideDhaka)} outside Dhaka</div>
              <div>💳 Payment: ${esc(payNamesList().join(" · "))}</div>
              <div>↩️ 7-day easy return if the product is damaged or incorrect</div>
            </div>
          </div>
        </div>
        ${related.length ? `<section class="section"><div class="section-head"><h2>You may also like</h2></div>${grid(related)}</section>` : ""}
      </div>`;

    const qtyInput = document.getElementById("qty");
    const readQty = () => Math.max(1, Math.min(parseInt(qtyInput.value, 10) || 1, p.stock));
    app.querySelectorAll("[data-step]").forEach((b) => b.addEventListener("click", () => {
      qtyInput.value = Math.max(1, Math.min(readQty() + Number(b.dataset.step), p.stock));
    }));
    qtyInput.addEventListener("change", () => { qtyInput.value = readQty(); });
    document.getElementById("addBtn").addEventListener("click", () => addToCart(p.id, readQty()));
    document.getElementById("buyBtn").addEventListener("click", () => {
      const line = cart.find((l) => l.id === p.id);
      if (!line) addToCart(p.id, readQty());
      location.hash = "#/checkout";
    });
  }

  function summaryHtml(t, showCoupon) {
    const remaining = S.delivery.freeAbove - t.subtotal;
    return `
      <div class="sum-row"><span>Subtotal (${t.count} item${t.count === 1 ? "" : "s"})</span><b>${money(t.subtotal)}</b></div>
      ${t.disc ? `<div class="sum-row" style="color:var(--em-600)"><span>Discount (${esc(coupon)})</span><b>−${money(t.disc)}</b></div>` : ""}
      <div class="sum-row"><span>Delivery</span><b id="shipAmt">${t.freeShip ? "FREE" : money(t.shipping)}</b></div>
      <div class="sum-row total"><span>Total</span><span id="totalAmt">${money(t.total)}</span></div>
      ${S.delivery.freeAbove > 0 && !t.freeShip ? `
        <div class="progress"><div style="width:${Math.min(100, (t.subtotal / S.delivery.freeAbove) * 100)}%"></div></div>
        <small class="muted">Add ${money(remaining)} more for FREE delivery</small>` : ""}
      ${showCoupon ? `
        <form class="coupon" id="couponForm">
          <input name="code" placeholder="Coupon code" value="${esc(coupon || "")}" aria-label="Coupon code">
          <button class="btn btn-outline" type="submit">${coupon ? "Update" : "Apply"}</button>
        </form>
        ${coupon ? `<button class="btn btn-ghost" id="removeCoupon" style="padding:0">Remove coupon</button>` : ""}` : ""}`;
  }

  function bindCoupon(rerender) {
    const form = document.getElementById("couponForm");
    if (!form) return;
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const code = form.code.value.trim().toUpperCase();
      const c = S.coupons[code];
      const { subtotal } = totals();
      if (!c) { toast("Invalid coupon code", "err"); return; }
      if (subtotal < c.minOrder) { toast(`This coupon needs a minimum order of ${money(c.minOrder)}`, "err"); return; }
      coupon = code; store.set("bd_coupon", coupon);
      toast(`Coupon ${code} applied!`);
      rerender();
    });
    const rm = document.getElementById("removeCoupon");
    if (rm) rm.addEventListener("click", () => { coupon = null; store.set("bd_coupon", null); rerender(); });
  }

  function pageCart() {
    const t = totals();
    if (!t.lines.length) {
      app.innerHTML = `${pageTitle("Shopping Cart")}<div class="container">${emptyState("🛒", "Your cart is empty", "Looks like you haven't added anything yet.")}</div>`;
      return;
    }
    app.innerHTML = `
      ${pageTitle("Shopping Cart")}
      <div class="container cart-layout">
        <div class="panel">
          ${t.lines.map(({ product: p, qty }) => `
            <div class="cart-item">
              <a href="#/product/${esc(p.id)}">${thumb(p)}</a>
              <div>
                <a href="#/product/${esc(p.id)}" class="cart-item-name">${esc(p.name)}</a>
                <div class="muted" style="font-size:13px">${money(p.price)} each</div>
              </div>
              <div class="cart-item-right">
                <div class="qty">
                  <button data-qty="${esc(p.id)}" data-step="-1" aria-label="Decrease">−</button>
                  <input type="number" value="${qty}" min="1" max="${p.stock}" data-qty-input="${esc(p.id)}" aria-label="Quantity">
                  <button data-qty="${esc(p.id)}" data-step="1" aria-label="Increase">+</button>
                </div>
                <b>${money(p.price * qty)}</b>
                <button class="btn btn-ghost" data-remove="${esc(p.id)}">🗑 Remove</button>
              </div>
            </div>`).join("")}
          <div style="display:flex;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px">
            <a href="#/shop" class="btn btn-outline">← Continue shopping</a>
            <button class="btn btn-ghost" id="clearCart">Clear cart</button>
          </div>
        </div>
        <div class="panel">
          <h3>Order Summary</h3>
          ${summaryHtml(t, true)}
          <a href="#/checkout" class="btn btn-gold btn-block" style="margin-top:14px">Proceed to checkout →</a>
          <p class="note" style="margin-top:14px">Delivery charge is calculated at checkout: ${money(S.delivery.insideDhaka)} inside Dhaka, ${money(S.delivery.outsideDhaka)} outside Dhaka.</p>
        </div>
      </div>`;

    app.querySelectorAll("[data-qty]").forEach((b) => b.addEventListener("click", () => {
      const line = cart.find((l) => l.id === b.dataset.qty);
      if (line) { setQty(line.id, line.qty + Number(b.dataset.step)); pageCart(); }
    }));
    app.querySelectorAll("[data-qty-input]").forEach((inp) => inp.addEventListener("change", () => {
      setQty(inp.dataset.qtyInput, parseInt(inp.value, 10) || 1); pageCart();
    }));
    app.querySelectorAll("[data-remove]").forEach((b) => b.addEventListener("click", () => {
      removeFromCart(b.dataset.remove); toast("Item removed"); pageCart();
    }));
    document.getElementById("clearCart").addEventListener("click", () => {
      if (confirm("Remove all items from your cart?")) { cart = []; saveCart(); pageCart(); }
    });
    bindCoupon(pageCart);
  }

  function pageCheckout() {
    const draft = store.get("bd_customer", {});
    const t = totals(draft.district && draft.district !== "Dhaka" ? "outside" : "inside");
    if (!t.lines.length) {
      app.innerHTML = `${pageTitle("Checkout")}<div class="container">${emptyState("🛒", "Your cart is empty", "Add some products before checking out.")}</div>`;
      return;
    }

    app.innerHTML = `
      ${pageTitle("Checkout", `<a href="#/cart">Cart</a> / Checkout`)}
      <div class="container cart-layout">
        <form class="panel" id="checkoutForm" novalidate>
          <h3>1. Delivery information</h3>
          <div class="form-grid">
            <div class="field"><label for="name">Full name <span class="req">*</span></label><input id="name" name="name" autocomplete="name" value="${esc(draft.name)}"><span class="err"></span></div>
            <div class="field"><label for="phone">Mobile number <span class="req">*</span></label><input id="phone" name="phone" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" autocomplete="tel" value="${esc(draft.phone)}"><span class="err"></span></div>
            <div class="field"><label for="email">Email (optional)</label><input id="email" name="email" type="email" autocomplete="email" value="${esc(draft.email)}"><span class="err"></span></div>
            <div class="field"><label for="district">District <span class="req">*</span></label>
              <select id="district" name="district">
                <option value="">Select district</option>
                ${DISTRICTS.map((d) => `<option ${draft.district === d ? "selected" : ""}>${d}</option>`).join("")}
              </select><span class="err"></span></div>
            <div class="field full"><label for="address">Full address <span class="req">*</span></label><textarea id="address" name="address" rows="2" placeholder="House, road, area, thana">${esc(draft.address)}</textarea><span class="err"></span></div>
            <div class="field full"><label for="notes">Order notes (optional)</label><textarea id="notes" name="notes" rows="2" placeholder="e.g. size, colour, preferred delivery time"></textarea></div>
          </div>

          <h3 style="margin-top:22px">2. Payment method</h3>
          <div class="pay-options">
            ${enabledPayments().map((m, i) => `
            <label class="pay-opt pay-${m.key}"><input type="radio" name="payment" value="${m.key}" ${i === 0 ? "checked" : ""}>
              <div><b>${m.icon} ${esc(m.name)}</b>
                <small>${m.key === "cod" ? "Pay in cash when you receive your order."
                  : `${m.type === "Merchant" ? "Make <b>Payment</b>" : "<b>Send Money</b>"} of your total to our ${esc(m.name)} ${esc(m.type.toLowerCase())} number <b class="pay-num">${esc(m.number)}</b>, then enter your number and the Transaction ID below.`}</small>
              </div></label>`).join("")}
          </div>
          <div class="pay-extra" id="payExtra">
            <div class="form-grid">
              <div class="field"><label for="payFrom">Sender number <span class="req">*</span></label><input id="payFrom" name="payFrom" type="tel" placeholder="01XXXXXXXXX"><span class="err"></span></div>
              <div class="field"><label for="trxId">Transaction ID <span class="req">*</span></label><input id="trxId" name="trxId" placeholder="e.g. 8N7A6B5C4D"><span class="err"></span></div>
            </div>
          </div>
          <label style="display:flex;gap:8px;margin-top:18px;font-size:14px;align-items:flex-start">
            <input type="checkbox" name="agree" id="agree" style="margin-top:4px;accent-color:var(--em-700)">
            <span>I agree to the <a class="link-more" href="#/policy/terms" target="_blank">terms</a> and <a class="link-more" href="#/policy/returns" target="_blank">return policy</a>.</span>
          </label>
          <div class="err" id="agreeErr" style="color:var(--red);font-size:12px"></div>
          <div class="hp" aria-hidden="true"><label>Leave this empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
          <button class="btn btn-gold btn-block" type="submit" id="placeOrder" style="margin-top:14px;font-size:16px">✓ Confirm order</button>
        </form>

        <div class="panel">
          <h3>Your order</h3>
          <div class="mini-items">
            ${t.lines.map(({ product: p, qty }) => `<div class="mini-item"><span>${esc(p.name)} × ${qty}</span><b>${money(p.price * qty)}</b></div>`).join("")}
          </div>
          <hr style="border:0;border-top:1px solid var(--line)">
          <div id="checkoutSummary">${summaryHtml(t, true)}</div>
        </div>
      </div>`;

    const form = document.getElementById("checkoutForm");
    const area = () => (form.district.value && form.district.value !== "Dhaka" ? "outside" : "inside");
    const refreshSummary = () => {
      document.getElementById("checkoutSummary").innerHTML = summaryHtml(totals(area()), true);
      bindCoupon(refreshSummary);
    };
    bindCoupon(refreshSummary);

    form.district.addEventListener("change", refreshSummary);
    const syncPay = () => document.getElementById("payExtra").classList.toggle("show", !!form.payment.value && form.payment.value !== "cod");
    form.querySelectorAll("input[name=payment]").forEach((r) => r.addEventListener("change", syncPay));
    syncPay();
    // Remember customer details as they type, so a refresh doesn't lose them
    form.addEventListener("input", () => {
      store.set("bd_customer", { name: form.name.value, phone: form.phone.value, email: form.email.value, district: form.district.value, address: form.address.value });
    });

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const errors = {};
      const v = (n) => form[n].value.trim();
      if (v("name").length < 3) errors.name = "Please enter your full name";
      if (!isValidPhone(v("phone"))) errors.phone = "Enter a valid Bangladeshi mobile number (01XXXXXXXXX)";
      if (v("email") && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v("email"))) errors.email = "Enter a valid email address";
      if (!v("district")) errors.district = "Please select your district";
      if (v("address").length < 8) errors.address = "Please enter your full delivery address";
      const payment = form.payment.value;
      if (!payment) { toast("Please choose a payment method", "err"); return; }
      if (payment !== "cod") {
        if (!isValidPhone(v("payFrom"))) errors.payFrom = "Enter the number you paid from";
        if (!/^[A-Za-z0-9]{8,12}$/.test(v("trxId"))) errors.trxId = "Enter a valid transaction ID";
      }

      form.querySelectorAll(".field").forEach((f) => { f.classList.remove("invalid"); const er = f.querySelector(".err"); if (er) er.textContent = ""; });
      Object.entries(errors).forEach(([name, msg]) => {
        const field = form[name].closest(".field");
        field.classList.add("invalid");
        field.querySelector(".err").textContent = msg;
      });
      const agreeErr = document.getElementById("agreeErr");
      agreeErr.textContent = form.agree.checked ? "" : "Please accept the terms to continue";

      if (Object.keys(errors).length || !form.agree.checked) {
        const first = form.querySelector(".invalid input, .invalid select, .invalid textarea") || form.agree;
        first.focus();
        toast("Please fix the highlighted fields", "err");
        return;
      }

      const t2 = totals(area());
      const payload = {
        customer: { name: v("name"), phone: v("phone"), email: v("email"), district: v("district"), address: v("address") },
        notes: v("notes"),
        payment: { method: payment, from: v("payFrom"), trxId: v("trxId") },
        items: t2.lines.map(({ product: p, qty }) => ({ id: p.id, qty })),
        coupon: t2.disc ? coupon : null,
        website: form.website.value
      };
      const btn = document.getElementById("placeOrder");
      btn.disabled = true;
      btn.textContent = "Placing your order…";

      submitOrder(payload, t2).then((order) => {
        const orders = store.get("bd_orders", []);
        orders.unshift(order);
        store.set("bd_orders", orders.slice(0, 20));
        cart = []; saveCart();
        coupon = null; store.set("bd_coupon", null);
        location.hash = "#/success/" + order.id;
      }).catch((err) => {
        btn.disabled = false;
        btn.textContent = "✓ Confirm order";
        toast(err.message || "Could not place the order. Please try again.", "err");
      });
    });
  }

  /*
   * Sends the order to the server (api/order.php on cPanel). The server re-checks prices,
   * saves the order and emails the shop. If the server can't be reached (e.g. the site is
   * hosted without PHP), the order is kept on this device and the customer is asked to send
   * it on WhatsApp instead.
   */
  async function submitOrder(payload, t) {
    const local = () => ({
      id: "BD" + Date.now().toString(36).toUpperCase().slice(-6) + Math.floor(Math.random() * 90 + 10),
      date: new Date().toISOString(),
      status: "Pending",
      offline: true,
      customer: payload.customer, notes: payload.notes, payment: payload.payment,
      items: t.lines.map(({ product: p, qty }) => ({ id: p.id, name: p.name, price: p.price, qty })),
      subtotal: t.subtotal, discount: t.disc, coupon: payload.coupon, shipping: t.shipping, total: t.total
    });
    if (!S.orderApi) return local();

    let res;
    try {
      res = await fetch(S.orderApi, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
    } catch (e) {
      return local(); // network error / no server
    }
    let data = null;
    try { data = await res.json(); } catch (e) { /* not JSON: PHP not available */ }
    if (!data) return local();
    if (!data.ok) throw new Error(data.error || "Could not place the order");
    return data.order;
  }

  function orderMessage(o) {
    const payNames = Object.fromEntries(Object.entries(PAY_INFO).map(([k, v]) => [k, v.name]));
    return [
      `Assalamu Alaikum! New order from ${S.name}`,
      `Order ID: ${o.id}`,
      "",
      ...o.items.map((i) => `• ${i.name} × ${i.qty} = ${money(i.price * i.qty)}`),
      "",
      `Subtotal: ${money(o.subtotal)}`,
      o.discount ? `Discount (${o.coupon}): -${money(o.discount)}` : null,
      `Delivery: ${o.shipping ? money(o.shipping) : "FREE"}`,
      `Total: ${money(o.total)}`,
      `Payment: ${payNames[o.payment.method]}${o.payment.trxId ? ` (TrxID: ${o.payment.trxId}, from ${o.payment.from})` : ""}`,
      "",
      `Name: ${o.customer.name}`,
      `Phone: ${o.customer.phone}`,
      `Address: ${o.customer.address}, ${o.customer.district}`,
      o.notes ? `Notes: ${o.notes}` : null
    ].filter((x) => x !== null).join("\n");
  }

  function findOrder(id) {
    return store.get("bd_orders", []).find((o) => o.id.toUpperCase() === String(id).trim().toUpperCase());
  }

  function pageSuccess(id) {
    const o = findOrder(id);
    if (!o) { pageNotFound(); return; }
    app.innerHTML = `
      <div class="container">
        <div class="panel success">
          <div class="big">🎉</div>
          <h1 style="margin:0">JazakAllah Khair, ${esc(o.customer.name.split(" ")[0])}!</h1>
          <p class="muted">${o.offline ? "Your order is ready. Please save your order ID:" : "Your order has been received successfully. Please save your order ID:"}</p>
          <div class="order-id">${esc(o.id)}</div>
          <p>Total: <b>${money(o.total)}</b> · ${o.payment.method === "cod" ? "Pay cash on delivery" : "We'll verify your payment shortly"}</p>
          ${o.offline
            ? `<div class="note" style="text-align:left">📲 <b>Important:</b> Please tap <b>Send order on WhatsApp</b> below so we receive your order. Our team will then call you at <b>${esc(o.customer.phone)}</b> to confirm.</div>`
            : `<div class="note" style="text-align:left">✓ We've received your order. Our team will call you at <b>${esc(o.customer.phone)}</b> to confirm it before delivery. Want a faster reply? Send it to us on WhatsApp too.</div>`}
          <div class="hero-cta" style="justify-content:center">
            <a class="btn btn-wa" target="_blank" rel="noopener" href="${waLink(orderMessage(o))}">💬 Send order on WhatsApp</a>
            <a class="btn btn-outline" href="#/track?id=${esc(o.id)}">📦 Track order</a>
            <a class="btn btn-primary" href="#/shop">Continue shopping</a>
          </div>
        </div>
      </div>`;
  }

  function orderStatusHtml(o) {
    const steps = ["Pending", "Confirmed", "Shipped", "Delivered"];
    const cancelled = o.status === "Cancelled";
    const idx = cancelled ? -1 : Math.max(0, steps.indexOf(o.status));
    return `
      <div class="order-card">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <div><b>Order ${esc(o.id)}</b><div class="muted" style="font-size:13px">${new Date(o.date).toLocaleString("en-GB")}</div></div>
          <div><span class="status-pill ${cancelled ? "cancelled" : ""}">${esc(o.status)}</span></div>
        </div>
        <div class="steps">${steps.map((s, i) => `<div class="step ${i <= idx ? "done" : ""}"><i>${i <= idx ? "✓" : i + 1}</i>${s}</div>`).join("")}</div>
        ${o.items.map((i) => `<div class="mini-item"><span>${esc(i.name)} × ${i.qty}</span><b>${money(i.price * i.qty)}</b></div>`).join("")}
        <div class="sum-row total"><span>Total</span><span>${money(o.total)}</span></div>
        ${o.customer ? `<div class="muted" style="font-size:13px">Deliver to: ${esc(o.customer.name)}${o.customer.address ? ", " + esc(o.customer.address) : ""}, ${esc(o.customer.district)}</div>` : ""}
      </div>`;
  }

  function pageTrack(params) {
    const id = (params.get("id") || "").trim();
    const local = id ? findOrder(id) : null;
    const phone = params.get("phone") || (local && local.customer.phone) || "";
    const recent = store.get("bd_orders", []).slice(0, 5);
    app.innerHTML = `
      ${pageTitle("Track Your Order")}
      <div class="container" style="max-width:760px">
        <div class="panel">
          <form id="trackForm" class="form-grid">
            <div class="field"><label for="tid">Order ID</label><input id="tid" name="id" placeholder="e.g. BDXXXXXX" value="${esc(id)}" required></div>
            <div class="field"><label for="tphone">Mobile number used for the order</label><input id="tphone" name="phone" type="tel" placeholder="01XXXXXXXXX" value="${esc(phone)}" required></div>
            <div class="field full"><button class="btn btn-primary" type="submit">📦 Track order</button></div>
          </form>
          <div id="trackResult">${id ? `<p class="muted">Checking…</p>` : ""}</div>
        </div>
        ${!id && recent.length ? `<h3 class="serif" style="color:var(--em-800)">Your recent orders</h3>${recent.map(orderStatusHtml).join("")}` : ""}
      </div>`;
    document.getElementById("trackForm").addEventListener("submit", (e) => {
      e.preventDefault();
      const q = new URLSearchParams({ id: e.target.id.value.trim(), phone: e.target.phone.value.trim() });
      location.hash = "#/track?" + q.toString();
    });
    if (!id) return;

    const out = document.getElementById("trackResult");
    const notFound = () => {
      out.innerHTML = `<div class="note">No order found with ID <b>${esc(id)}</b> and this phone number. <a class="link-more" target="_blank" rel="noopener" href="${waLink(`Assalamu Alaikum, I want to know the status of order ${id}`)}">Ask us on WhatsApp →</a></div>`;
    };
    const showLocal = () => { if (local) out.innerHTML = orderStatusHtml(local); else notFound(); };
    if (!S.trackApi || !phone) { showLocal(); return; }

    fetch(`${S.trackApi}?${new URLSearchParams({ id, phone })}`)
      .then((r) => r.json())
      .then((data) => {
        if (data && data.ok) {
          out.innerHTML = orderStatusHtml(data.order);
          // Keep the locally saved copy's status in sync with the shop's updates
          const orders = store.get("bd_orders", []);
          const mine = orders.find((o) => o.id === data.order.id);
          if (mine) { mine.status = data.order.status; store.set("bd_orders", orders); }
        } else showLocal();
      })
      .catch(showLocal);
  }

  function pageWishlist() {
    const list = wishlist.map(byId).filter(Boolean);
    app.innerHTML = `
      ${pageTitle("My Wishlist")}
      <div class="container">${list.length ? grid(list) : emptyState("♡", "Your wishlist is empty", "Tap the heart on any product to save it for later.")}</div>`;
  }

  function pageAbout() {
    app.innerHTML = `
      ${pageTitle("About Us")}
      <div class="container">
        <div class="prose">
          <div class="about-hero">
            <img src="images/logo.png" alt="${esc(S.name)} logo" width="220" height="220">
            <div>
              <h2 style="margin-top:0">Assalamu Alaikum, welcome to ${esc(S.name)}</h2>
              <p class="bn" style="font-size:20px;color:var(--gold-dark);margin:0 0 10px">“${esc(S.taglineBn)}”</p>
              <p>${esc(S.name)} is a trusted online shop in Bangladesh built on one simple idea: <b>honest products at honest prices, delivered with barakah</b>. We bring you original cosmetics, shampoo and lotion, baby food and baby care, rich food and medical equipment — delivered to your doorstep in all 64 districts.</p>
            </div>
          </div>
          <p>Every product is quality-checked before it ships. We believe in transparent pricing, no hidden charges and real customer support — on phone, WhatsApp and Facebook. Inbox to order, follow <b>${esc(S.handle)}</b> for daily deals.</p>
          <div class="about-grid">
            <div class="panel"><span>🤝</span><h3>Honest Trade</h3><p class="muted">Clear prices, accurate descriptions and no misleading offers.</p></div>
            <div class="panel"><span>✅</span><h3>Authentic Products</h3><p class="muted">Sourced from trusted suppliers and checked before dispatch.</p></div>
            <div class="panel"><span>🚚</span><h3>Fast Delivery</h3><p class="muted">1–2 days inside Dhaka and 2–4 days across Bangladesh.</p></div>
          </div>
          <a href="#/shop" class="btn btn-primary">Explore our products</a>
        </div>
      </div>`;
  }

  const FAQS = [
    ["How do I place an order?", "Add products to your cart, go to checkout, fill in your name, phone number and address, choose a payment method and confirm. You can also order directly via WhatsApp from any product page."],
    ["What are the delivery charges?", `Delivery is ${money(S.delivery.insideDhaka)} inside Dhaka and ${money(S.delivery.outsideDhaka)} outside Dhaka.${S.delivery.freeAbove ? ` Orders above ${money(S.delivery.freeAbove)} get FREE delivery.` : ""}`],
    ["How long does delivery take?", "Usually 1–2 working days inside Dhaka and 2–4 working days outside Dhaka."],
    ["Which payment methods do you accept?", `We accept ${payNamesList().join(", ")}. For mobile banking, send the amount to our number shown at checkout and enter the Transaction ID.`],
    ["Can I check the product before paying?", "Yes. With Cash on Delivery you can check the product in front of the delivery person before paying."],
    ["What is your return policy?", "If you receive a damaged, defective or wrong product, inform us within 7 days and we'll replace it or refund you. See our Return & Refund policy for details."],
    ["Are your products original?", "Yes. We sell only original imported products. Where a product has more than one origin (for example Veet UK and Veet UAE), the origin is shown in the product name."],
    ["Is packaging discreet?", "Yes. All orders, including sexual-wellness products, are delivered in plain packaging with no product details on the outside."],
    ["How can I track my order?", "Use the Track Order page with your order ID, or message us on WhatsApp."],
    ["How do I use a coupon code?", "Enter the code in the coupon box in your cart or at checkout. Each coupon has a minimum order amount."]
  ];

  function pageFaq() {
    app.innerHTML = `
      ${pageTitle("Frequently Asked Questions")}
      <div class="container" style="max-width:860px;margin-bottom:40px">
        ${FAQS.map(([q, a], i) => `<details ${i === 0 ? "open" : ""}><summary>${esc(q)}</summary><p style="margin:0">${esc(a)}</p></details>`).join("")}
        <div class="note" style="margin-top:20px">Still have questions? <a class="link-more" href="#/contact">Contact us</a> — we're happy to help.</div>
      </div>`;
  }

  function pageContact() {
    app.innerHTML = `
      ${pageTitle("Contact Us")}
      <div class="container contact-grid">
        <div class="panel">
          <h3>Get in touch</h3>
          <div class="contact-card"><span>📞</span><div><b>Phone</b><div><a href="tel:${esc(S.phone.replace(/[^\d+]/g, ""))}">${esc(S.phone)}</a></div></div></div>
          <div class="contact-card"><span class="ic-wa">${ICONS.whatsapp}</span><div><b>WhatsApp</b><div><a target="_blank" rel="noopener" href="${waLink("Assalamu Alaikum!")}">${esc(S.phone)}</a></div></div></div>
          <div class="contact-card"><span class="ic-ms">${ICONS.messenger}</span><div><b>Messenger</b><div><a target="_blank" rel="noopener" href="${msLink()}">Inbox to order</a></div></div></div>
          ${socialEntries().map(([k, url]) => `<div class="contact-card"><span class="ic-${k}">${ICONS[k]}</span><div><b>${SOCIAL_NAMES[k]}</b><div><a target="_blank" rel="noopener" href="${esc(url)}">${esc(S.handle || url.replace(/^https?:\/\/(www\.)?/, ""))}</a></div></div></div>`).join("")}
          <div class="contact-card"><span>✉️</span><div><b>Email</b><div><a href="mailto:${esc(S.email)}">${esc(S.email)}</a></div></div></div>
          <div class="contact-card"><span>📍</span><div><b>Address</b><div>${esc(S.address)}</div></div></div>
          <div class="contact-card" style="border:0"><span>🕘</span><div><b>Hours</b><div>${esc(S.hours)}</div></div></div>
        </div>
        <form class="panel" id="contactForm" novalidate>
          <h3>Send us a message</h3>
          <div class="form-grid">
            <div class="field"><label for="cname">Name <span class="req">*</span></label><input id="cname" name="name"><span class="err"></span></div>
            <div class="field"><label for="cphone">Phone <span class="req">*</span></label><input id="cphone" name="phone" type="tel" placeholder="01XXXXXXXXX"><span class="err"></span></div>
            <div class="field full"><label for="csubject">Subject</label>
              <select id="csubject" name="subject"><option>General question</option><option>Order status</option><option>Return / exchange</option><option>Wholesale inquiry</option><option>Feedback</option></select></div>
            <div class="field full"><label for="cmsg">Message <span class="req">*</span></label><textarea id="cmsg" name="message" rows="5"></textarea><span class="err"></span></div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            <button class="btn btn-wa" type="submit" name="via" value="wa">💬 Send via WhatsApp</button>
            <button class="btn btn-outline" type="submit" name="via" value="email">✉️ Send via Email</button>
          </div>
        </form>
      </div>`;

    const form = document.getElementById("contactForm");
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const via = e.submitter ? e.submitter.value : "wa";
      const errors = {};
      if (form.name.value.trim().length < 2) errors.name = "Please enter your name";
      if (!isValidPhone(form.phone.value.trim())) errors.phone = "Enter a valid mobile number";
      if (form.message.value.trim().length < 5) errors.message = "Please write your message";
      form.querySelectorAll(".field").forEach((f) => { f.classList.remove("invalid"); const er = f.querySelector(".err"); if (er) er.textContent = ""; });
      Object.entries(errors).forEach(([n, m]) => { const f = form[n].closest(".field"); f.classList.add("invalid"); f.querySelector(".err").textContent = m; });
      if (Object.keys(errors).length) return;

      const body = `Name: ${form.name.value.trim()}\nPhone: ${form.phone.value.trim()}\nSubject: ${form.subject.value}\n\n${form.message.value.trim()}`;
      if (via === "email") {
        location.href = `mailto:${S.email}?subject=${encodeURIComponent(form.subject.value + " — " + S.name)}&body=${encodeURIComponent(body)}`;
      } else {
        window.open(waLink(body), "_blank", "noopener");
      }
      toast("Thank you! We'll get back to you soon.");
      form.reset();
    });
  }

  const POLICIES = {
    shipping: ["Shipping Policy", `
      <p>We deliver to all 64 districts of Bangladesh through trusted courier partners.</p>
      <ul>
        <li><b>Inside Dhaka:</b> ${money(S.delivery.insideDhaka)} — delivered within 1–2 working days.</li>
        <li><b>Outside Dhaka:</b> ${money(S.delivery.outsideDhaka)} — delivered within 2–4 working days.</li>
        ${S.delivery.freeAbove ? `<li><b>Free delivery</b> on orders above ${money(S.delivery.freeAbove)}.</li>` : ""}
      </ul>
      <p>All orders — especially sexual-wellness products — are shipped in plain, discreet packaging. Our team will call you to confirm each order before dispatch. Delivery times may be longer during Eid, hartals or natural disasters.</p>`],
    returns: ["Return & Refund Policy", `
      <p>Your satisfaction matters to us. If something isn't right, we'll make it right, in sha Allah.</p>
      <ul>
        <li>Please check your product in front of the delivery person.</li>
        <li>Report damaged, defective or wrong items within <b>7 days</b> of delivery with photos/video.</li>
        <li>Products must be unused and in original packaging.</li>
        <li>For hygiene reasons, opened personal-care, intimate-care and food items can only be returned if damaged or wrong.</li>
        <li>Refunds are sent via mobile banking (bKash/Nagad) within 3–5 working days after we receive the returned item.</li>
      </ul>`],
    privacy: ["Privacy Policy", `
      <p>We collect only the information needed to process and deliver your order: your name, phone number, address and (optionally) email.</p>
      <ul>
        <li>We never sell or share your personal data with third parties except our delivery partners.</li>
        <li>Your cart, wishlist and recent orders are stored in your own browser on this device.</li>
        <li>You may contact us at any time to update or delete your information.</li>
      </ul>`],
    terms: ["Terms & Conditions", `
      <ul>
        <li>All prices are in Bangladeshi Taka (BDT) and include VAT where applicable.</li>
        <li>Orders are confirmed only after our team verifies them by phone.</li>
        <li>We reserve the right to cancel orders in case of pricing errors, stock shortage or suspected fraud.</li>
        <li>Product colours may vary slightly from the photos due to screen settings.</li>
        <li>Coupons cannot be combined and are subject to a minimum order amount.</li>
      </ul>`]
  };

  function pagePolicy(slug) {
    const p = POLICIES[slug];
    if (!p) { pageNotFound(); return; }
    app.innerHTML = `${pageTitle(p[0])}<div class="container" style="max-width:860px"><div class="prose">${p[1]}</div></div>`;
  }

  function pageNotFound() {
    app.innerHTML = `<div class="container" style="padding-top:30px">${emptyState("🧭", "Page not found", "The page you're looking for doesn't exist.", `<a href="#/" class="btn btn-primary">Go home</a>`)}</div>`;
  }

  /* ---------- Router ---------- */
  function route() {
    const hash = location.hash.replace(/^#/, "") || "/";
    const [path, query] = hash.split("?");
    const params = new URLSearchParams(query || "");
    const parts = path.split("/").filter(Boolean);

    document.getElementById("nav").classList.remove("open");
    document.querySelectorAll(".nav a").forEach((a) => {
      const href = a.getAttribute("href").replace(/^#/, "");
      a.classList.toggle("active", href === hash || (href === "/shop" && parts[0] === "shop" && !params.get("tag")));
    });
    document.getElementById("searchInput").value = parts[0] === "shop" ? params.get("q") || "" : "";

    const routes = {
      "": pageHome,
      shop: () => pageShop(params),
      product: () => pageProduct(decodeURIComponent(parts[1] || "")),
      cart: pageCart,
      checkout: pageCheckout,
      success: () => pageSuccess(parts[1] || ""),
      track: () => pageTrack(params),
      wishlist: pageWishlist,
      about: pageAbout,
      faq: pageFaq,
      contact: pageContact,
      policy: () => pagePolicy(parts[1] || "")
    };
    (routes[parts[0] || ""] || pageNotFound)();

    const titles = { shop: "Shop", cart: "Cart", checkout: "Checkout", track: "Track Order", wishlist: "Wishlist", about: "About Us", faq: "FAQ", contact: "Contact" };
    const p = parts[0] === "product" ? byId(decodeURIComponent(parts[1] || "")) : null;
    const t = p ? p.name : titles[parts[0]];
    document.title = t ? `${t} — ${S.name}` : `${S.name} — Trusted Online Shopping in Bangladesh`;
    window.scrollTo(0, 0);
  }

  /* ---------- Global bindings ---------- */
  function fillStoreInfo() {
    document.querySelectorAll("[data-store]").forEach((el) => {
      const k = el.dataset.store;
      if (k === "handle") { el.textContent = S.handle; }
      else if (k === "phone-link") { el.href = "tel:" + S.phone.replace(/[^\d+]/g, ""); el.textContent = S.phone; }
      else if (k === "email-link") { el.href = "mailto:" + S.email; el.textContent = S.email; }
      else el.textContent = S[k] || "";
    });
    document.getElementById("year").textContent = new Date().getFullYear();
    document.getElementById("waFloat").href = waLink("Assalamu Alaikum! I have a question.");
    document.getElementById("msFloat").href = msLink();
    document.getElementById("footerCats").innerHTML = CATEGORIES.map((c) => `<li><a href="#/shop?cat=${esc(c.id)}">${esc(c.name)}</a></li>`).join("");
    const socialHtml = socialEntries()
      .map(([k, url]) => `<a href="${esc(url)}" target="_blank" rel="noopener" aria-label="${SOCIAL_NAMES[k]}" title="${SOCIAL_NAMES[k]}">${ICONS[k]}</a>`).join("");
    document.querySelectorAll("[data-social]").forEach((el) => { el.innerHTML = socialHtml; });
    document.getElementById("payBadges").innerHTML = enabledPayments().map((m) => `<span class="pb-${m.key}">${esc(m.name)}</span>`).join("");
    document.getElementById("waFloat").innerHTML = ICONS.whatsapp;
    document.getElementById("msFloat").innerHTML = ICONS.messenger;
  }

  // Event delegation for buttons rendered inside product cards and pages
  document.addEventListener("click", (e) => {
    const add = e.target.closest("[data-add]");
    if (add) { addToCart(add.dataset.add); return; }
    const wish = e.target.closest("[data-wish]");
    if (wish) {
      toggleWish(wish.dataset.wish);
      const on = wishlist.includes(wish.dataset.wish);
      if (wish.classList.contains("wish-btn")) wish.textContent = on ? "♥" : "♡";
      else wish.textContent = on ? "♥ Saved" : "♡ Add to wishlist";
      if (location.hash.startsWith("#/wishlist")) pageWishlist();
    }
  });

  document.getElementById("searchForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const q = document.getElementById("searchInput").value.trim();
    location.hash = q ? "#/shop?q=" + encodeURIComponent(q) : "#/shop";
  });

  document.getElementById("menuToggle").addEventListener("click", () => {
    document.getElementById("nav").classList.toggle("open");
  });

  // Keep cart/wishlist in sync across browser tabs
  window.addEventListener("storage", (e) => {
    if (e.key === "bd_cart") cart = store.get("bd_cart", []);
    if (e.key === "bd_wish") wishlist = store.get("bd_wish", []);
    updateBadges();
  });

  window.addEventListener("hashchange", route);
  fillStoreInfo();
  updateBadges();
  route();
})();
