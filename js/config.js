/*
 * Default store settings.
 *
 * On cPanel (with PHP) these are managed from the Admin Panel → Settings, which
 * overrides this file. This file is only used as the fallback when PHP isn't available.
 */
window.STORE = {
  name: "Barakah Deals BD",
  tagline: "Trusted Online Shop",
  taglineBn: "সুস্থতা, সৌন্দর্য আর বিশুদ্ধতায়",
  handle: "@barakahdealsbd",
  phone: "+880 1328-558888",        // shown on site
  whatsapp: "8801328558888",        // international format, digits only (used for wa.me links)
  messenger: "barakahdealsbd",      // Facebook page username (used for m.me "Inbox to order" links)
  email: "info@barakahdealsbd.com",
  address: "Dhaka, Bangladesh",
  hours: "Sat – Thu, 10:00 AM – 9:00 PM",
  social: {
    facebook: "https://www.facebook.com/barakahdealsbd",
    instagram: "https://www.instagram.com/barakahdealsbd",
    youtube: "",
    tiktok: ""
  },
  currency: "৳",
  delivery: {
    insideDhaka: 70,
    outsideDhaka: 130,
    freeAbove: 3000                  // order subtotal for free delivery (0 to disable)
  },
  // type: "Personal" (customer uses Send Money) or "Merchant" (customer uses Payment)
  payments: {
    cod: { enabled: true },
    bkash: { enabled: true, number: "01328-558888", type: "Personal" },
    nagad: { enabled: true, number: "01328-558888", type: "Personal" },
    rocket: { enabled: false, number: "", type: "Personal" }
  },
  // code: { type: "percent" | "flat", value, minOrder }
  coupons: {
    BARAKAH10: { type: "percent", value: 10, minOrder: 1000 },
    WELCOME100: { type: "flat", value: 100, minOrder: 1500 }
  },
  // Flash sale countdown resets daily at this hour (0–23)
  flashSaleEndHour: 23,
  // Server endpoints (PHP, cPanel). Set orderApi to "" to use WhatsApp-only ordering.
  orderApi: "api/order.php",
  trackApi: "api/track.php"
};
