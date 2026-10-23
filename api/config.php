<?php
/*
 * Server settings for Barakah Deals BD (cPanel).
 * This file is never sent to visitors.
 * Store details, payment methods, social links, products etc. are managed in the Admin Panel (/admin/).
 */
return [
    // First-login password for the admin panel at /admin/.
    // After logging in, set your own password in Admin → Password (that one is stored securely and replaces this).
    'admin_password' => 'ChangeMe@Barakah2026',

    // Default address for new-order emails (can be changed in Admin → Settings)
    'shop_email' => 'info@barakahdealsbd.com',

    // "From" address for emails. Use an address on your own domain so emails aren't marked as spam.
    'from_email' => 'no-reply@barakahdealsbd.com',
    'from_name'  => 'Barakah Deals BD',

    // Send a confirmation email to customers who enter an email address at checkout
    'email_customer' => true,

    // Basic spam protection: max orders per visitor IP address per hour
    'max_orders_per_hour' => 10,

    // Timezone used for order dates
    'timezone' => 'Asia/Dhaka',
];
