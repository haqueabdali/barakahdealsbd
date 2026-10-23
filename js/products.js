/*
 * Product catalog. Add, remove or edit products here.
 *   price / oldPrice: selling price and the crossed-out "regular" price in BDT.
 *                     ⚠ The prices below are PLACEHOLDERS — replace them with your real prices.
 *   image:    path/URL to the product photo. When empty, the icon is shown instead.
 *   category: one of the ids in CATEGORIES. Categories with no products show "Coming soon".
 *   tags:     "flash" (Flash Deals), "featured" (Top Picks), "new" (New Arrivals)
 *   stock:    0 marks the product as out of stock
 */
window.CATEGORIES = [
  { id: "cosmetics", name: "Cosmetics & Skin Care", icon: "💄" },
  { id: "hair", name: "Shampoo & Hair Care", icon: "🧴" },
  { id: "baby", name: "Baby Food & Baby Care", icon: "👶" },
  { id: "personal", name: "Personal Care", icon: "🌸" },
  { id: "food", name: "Rich Food", icon: "🍯" },
  { id: "medical", name: "Medical Equipment", icon: "🩺" },
  { id: "snacks", name: "Chocolates & Snacks", icon: "🍫" },
  { id: "wellness", name: "Sexual Wellness", icon: "❤️" }
];

window.PRODUCTS = [
  /* ---------- Personal Care ---------- */
  {
    id: "veet-normal-100g-uk",
    name: "Veet Hair Removal Cream for Normal Skin - 100g (UK)",
    category: "personal",
    price: 650, oldPrice: 750,
    rating: 4.7, reviews: 128, stock: 30,
    icon: "🧴", image: "images/products/p01.jpg",
    tags: ["featured", "flash"],
    description: "Veet hair removal cream for normal skin, UK version. Removes hair from the root level in minutes for smooth skin that lasts longer than shaving.",
    features: ["For normal skin", "Net weight: 100g", "UK version", "Spatula included"]
  },
  {
    id: "veet-normal-100g-uae",
    name: "Veet Hair Removal Cream for Normal Skin - 100g (UAE)",
    category: "personal",
    price: 550, oldPrice: 650,
    rating: 4.6, reviews: 94, stock: 30,
    icon: "🧴", image: "images/products/p02.jpg",
    tags: [],
    description: "Veet hair removal cream for normal skin, UAE version. Quick, easy and pain-free hair removal at home.",
    features: ["For normal skin", "Net weight: 100g", "UAE version", "Spatula included"]
  },
  {
    id: "vwash-plus-100ml",
    name: "V-Wash Plus Expert Intimate Hygiene Wash 100ml",
    category: "personal",
    price: 380, oldPrice: 450,
    rating: 4.7, reviews: 76, stock: 40,
    icon: "🌸", image: "images/products/p03.jpg",
    tags: ["new"],
    description: "V-Wash Plus expert intimate hygiene wash with a mild, pH-balanced formula for everyday feminine care.",
    features: ["pH-balanced formula", "Suitable for daily use", "Volume: 100ml", "Discreet packaging on delivery"]
  },

  /* ---------- Baby Care ---------- */
  {
    id: "kodomo-powder-160g",
    name: "Kodomo Baby Powder 160gm",
    category: "baby",
    price: 280, oldPrice: 320,
    rating: 4.8, reviews: 152, stock: 50,
    icon: "👶", image: "images/products/p04.jpg",
    tags: ["featured"],
    description: "Kodomo baby powder keeps your baby's skin soft, dry and fresh. Gentle on delicate skin.",
    features: ["Gentle for baby skin", "Keeps skin dry & fresh", "Net weight: 160gm"]
  },
  {
    id: "kodomo-powder-extra-mild-180g",
    name: "Kodomo Baby Powder Extra Mild 180gm",
    category: "baby",
    price: 320, oldPrice: 360,
    rating: 4.8, reviews: 117, stock: 45,
    icon: "👶", image: "images/products/p05.jpg",
    tags: ["flash"],
    description: "Kodomo Extra Mild baby powder, specially made for sensitive baby skin. Soft, light and soothing.",
    features: ["Extra mild formula", "For sensitive skin", "Net weight: 180gm"]
  },
  {
    id: "kodomo-powder-50g",
    name: "Kodomo Baby Powder 50gm",
    category: "baby",
    price: 120, oldPrice: 140,
    rating: 4.6, reviews: 63, stock: 60,
    icon: "👶", image: "images/products/p06.jpg",
    tags: [],
    description: "Travel-size Kodomo baby powder — perfect for the diaper bag and outings.",
    features: ["Travel size", "Gentle for baby skin", "Net weight: 50gm"]
  },
  {
    id: "kodomo-lotion-200ml",
    name: "Kodomo Baby Lotion - 200ml",
    category: "baby",
    price: 420, oldPrice: 480,
    rating: 4.7, reviews: 88, stock: 35,
    icon: "🍼", image: "images/products/p07.jpg",
    tags: ["featured"],
    description: "Kodomo baby lotion moisturises and protects your baby's soft skin all day long.",
    features: ["Daily moisturiser", "Gentle formula", "Volume: 200ml"]
  },
  {
    id: "kodomo-lotion-180ml",
    name: "Kodomo Baby Lotion - 180ml",
    category: "baby",
    price: 380, oldPrice: 430,
    rating: 4.7, reviews: 71, stock: 35,
    icon: "🍼", image: "images/products/p08.jpg",
    tags: [],
    description: "Kodomo baby lotion for soft, smooth and moisturised baby skin.",
    features: ["Daily moisturiser", "Gentle formula", "Volume: 180ml"]
  },
  {
    id: "kodomo-lotion-powder-180ml",
    name: "Kodomo Baby Lotion Powder Age 0+ - 180ml",
    category: "baby",
    price: 350, oldPrice: 400,
    rating: 4.6, reviews: 42, stock: 30,
    icon: "🍼", image: "images/products/p25.jpg",
    tags: ["new"],
    description: "Kodomo lotion powder combines the softness of lotion with the freshness of powder — no mess, no dust. Suitable from birth (0+).",
    features: ["Lotion + powder in one", "No powder dust", "Age 0+", "Volume: 180ml"]
  },
  {
    id: "aveeno-baby-lotion-150ml",
    name: "Aveeno Baby Daily Care Baby Moisturising Lotion - 150ml",
    category: "baby",
    price: 1150, oldPrice: 1350,
    rating: 4.9, reviews: 58, stock: 20,
    icon: "🍼", image: "images/products/p19.jpg",
    tags: ["featured", "new"],
    description: "Aveeno Baby Daily Care moisturising lotion with natural oat. Nourishes and protects delicate baby skin. New look, now unscented.",
    features: ["With natural oat", "Unscented", "Daily care", "Volume: 150ml"]
  },
  {
    id: "aveeno-baby-soothing-cream-150ml",
    name: "Aveeno Baby Soothing Relief Emollient Cream 150ml",
    category: "baby",
    price: 1350, oldPrice: 1550,
    rating: 4.9, reviews: 47, stock: 15,
    icon: "🍼", image: "images/products/p20.jpg",
    tags: ["flash"],
    description: "Aveeno Baby Soothing Relief emollient cream with natural oat oil and vitamin E. Made for very dry and sensitive baby skin.",
    features: ["Natural oat oil & vitamin E", "For very dry skin", "Volume: 150ml"]
  },
  {
    id: "aveeno-baby-wash-250ml",
    name: "Aveeno Baby Daily Care Hair & Body Wash 250ml",
    category: "baby",
    price: 1250, oldPrice: 1450,
    rating: 4.8, reviews: 39, stock: 18,
    icon: "🛁", image: "images/products/p21.jpg",
    tags: [],
    description: "Aveeno Baby Daily Care 2-in-1 hair and body wash with natural oat extract. Gently cleanses without drying.",
    features: ["Hair & body 2-in-1", "With natural oat extract", "Gentle cleansing", "Volume: 250ml"]
  },

  /* ---------- Cosmetics, Skin & Hair Care ---------- */
  {
    id: "csk-vitamin-e-facial-90",
    name: "CSK Vitamin E Facial Essence 90 Capsules (Yellow)",
    category: "cosmetics",
    price: 450, oldPrice: 550,
    rating: 4.5, reviews: 84, stock: 40,
    icon: "✨", image: "images/products/p13.jpg",
    tags: ["flash"],
    description: "CSK vitamin E facial essence capsules for a nourished, glowing look. Twist open a capsule and apply the essence to face.",
    features: ["Vitamin E essence", "90 capsules", "Easy single-use capsules"]
  },
  {
    id: "csk-otiei-hair-60",
    name: "CSK Otiei Hair Soften Essence 60 (Vitamin E) Capsules (Red)",
    category: "hair",
    price: 400, oldPrice: 500,
    rating: 4.5, reviews: 61, stock: 40,
    icon: "💇", image: "images/products/p14.jpg",
    tags: [],
    description: "Otiei hair soften essence capsules with vitamin E — helps hair look smooth, soft and shiny.",
    features: ["Vitamin E hair serum", "60 capsules", "For soft, shiny hair"]
  },
  {
    id: "simple-moisturising-facial-wash-150ml",
    name: "Simple Moisturising Facial Wash - 150ml",
    category: "cosmetics",
    price: 650, oldPrice: 750,
    rating: 4.7, reviews: 96, stock: 30,
    icon: "🧼", image: "images/products/p22.jpg",
    tags: ["featured"],
    description: "Simple moisturising facial wash gently cleanses while keeping skin hydrated. Kind to skin, suitable for sensitive skin.",
    features: ["Gentle & moisturising", "Suitable for sensitive skin", "Volume: 150ml"]
  },
  {
    id: "cosrx-salicylic-cleanser-150ml",
    name: "COSRX Salicylic Acid Daily Gentle Cleanser 150ml",
    category: "cosmetics",
    price: 1150, oldPrice: 1350,
    rating: 4.8, reviews: 73, stock: 20,
    icon: "🧼", image: "images/products/p23.jpg",
    tags: ["featured", "flash"],
    description: "COSRX Salicylic Acid Daily Gentle Cleanser — a Korean skincare favourite for oily and acne-prone skin. Cleans pores gently every day.",
    features: ["With salicylic acid", "For oily & acne-prone skin", "Korean skincare", "Volume: 150ml"]
  },
  {
    id: "tresemme-keratin-smooth-380ml",
    name: "TRESemmé Keratin Smooth Shampoo 380ml",
    category: "hair",
    price: 750, oldPrice: 850,
    rating: 4.6, reviews: 82, stock: 25,
    icon: "💇", image: "images/products/p24.jpg",
    tags: ["new"],
    description: "TRESemmé Keratin Smooth shampoo helps control frizz for smooth, manageable, salon-style hair.",
    features: ["Keratin smooth formula", "Frizz control", "Pump bottle", "Volume: 380ml"]
  },

  /* ---------- Sexual Wellness ---------- */
  {
    id: "durex-play-warming-100ml",
    name: "Durex Play Warming Lube 100ml (Glycerin-based) | Orange",
    category: "wellness",
    price: 1250, oldPrice: 1450,
    rating: 4.6, reviews: 34, stock: 15,
    icon: "❤️", image: "images/products/p09.jpg",
    tags: [],
    description: "Durex Play Warming glycerin-based lubricant with a gentle warming sensation. 18+ only.",
    features: ["Glycerin-based", "Warming effect", "Volume: 100ml", "Discreet packaging on delivery"]
  },
  {
    id: "durex-play-classic-100ml",
    name: "Durex Play Lubricant Water Based Classic - 100ml | Blue",
    category: "wellness",
    price: 1150, oldPrice: 1350,
    rating: 4.7, reviews: 41, stock: 15,
    icon: "❤️", image: "images/products/p10.jpg",
    tags: ["featured"],
    description: "Durex Play Classic water-based lubricant. Smooth, non-sticky and easy to wash off. 18+ only.",
    features: ["Water-based", "Non-sticky", "Volume: 100ml", "Discreet packaging on delivery"]
  },
  {
    id: "durex-play-classic-50ml",
    name: "Durex Play Lubricant Water Based Classic - 50ml | Blue",
    category: "wellness",
    price: 750, oldPrice: 850,
    rating: 4.6, reviews: 29, stock: 20,
    icon: "❤️", image: "images/products/p11.jpg",
    tags: [],
    description: "Durex Play Feel / Classic water-based lubricant in a compact 50ml size. 18+ only.",
    features: ["Water-based", "Compact size", "Volume: 50ml", "Discreet packaging on delivery"]
  },
  {
    id: "durex-cherry-50ml",
    name: "Durex Lube Gel Water Based Lubricant Gel - 50ml | Cherry",
    category: "wellness",
    price: 750, oldPrice: 850,
    rating: 4.5, reviews: 22, stock: 20,
    icon: "🍒", image: "images/products/p12.jpg",
    tags: ["new"],
    description: "Durex cherry-flavoured water-based lubricant gel. 18+ only.",
    features: ["Water-based", "Cherry flavour", "Volume: 50ml", "Discreet packaging on delivery"]
  },

  /* ---------- Chocolates & Snacks ---------- */
  {
    id: "bbw-watermelon-lollipop-30",
    name: "BBW Happy Little Watermelon Lollipop 8gm x 30",
    category: "snacks",
    price: 450, oldPrice: 520,
    rating: 4.6, reviews: 55, stock: 25,
    icon: "🍭", image: "images/products/p15.jpg",
    tags: ["new"],
    description: "Fun watermelon-shaped lollipops kids love. A full display box of 30 pieces — great for parties and gifts.",
    features: ["30 pieces per box", "8gm each", "Watermelon shape"]
  },
  {
    id: "kitkat-2f-indian-42",
    name: "Kit Kat Chocolate Bar (2 Finger, Indian) 11.9gm x 42",
    category: "snacks",
    price: 850, oldPrice: 950,
    rating: 4.8, reviews: 103, stock: 20,
    icon: "🍫", image: "images/products/p16.jpg",
    tags: ["featured", "flash"],
    description: "Kit Kat 2-finger wafer chocolate bars, full box of 42. Have a break, have a Kit Kat!",
    features: ["42 bars per box", "11.9gm each", "Indian version"]
  },
  {
    id: "snickers-12g",
    name: "Snickers Chocolate Bar 12g",
    category: "snacks",
    price: 50, oldPrice: 60,
    rating: 4.7, reviews: 67, stock: 100,
    icon: "🍫", image: "images/products/p17.jpg",
    tags: [],
    description: "Snickers fun-size chocolate bar packed with peanuts, caramel and nougat.",
    features: ["Peanut, caramel & nougat", "Net weight: 12g"]
  },
  {
    id: "polo-gum-12g",
    name: "Polo Gum Pack 12g",
    category: "snacks",
    price: 60, oldPrice: 70,
    rating: 4.5, reviews: 38, stock: 100,
    icon: "🍬", image: "images/products/p18.jpg",
    tags: [],
    description: "Polo mints — the mint with the hole. Fresh breath on the go.",
    features: ["Fresh mint", "Net weight: 12g"]
  }
];
