<?php
return [
    "app_name" => "Stationery Ordering System",
    "company_name" => "Grantham College",

    // Email addresses of users who can access the admin panel
    // Comparison is case-insensitive
    "admins" => ["tteven@grantham.ac.uk"],

    // Full public URL of this app (with trailing slash) — used in Microsoft logout redirect
    "app_url" => "http://localhost:8000/",

    "currency_symbol" => "£",
    "order_subject_prefix" => "Stationery Order Request",

    // Shown in order emails as the footer sender name
    "system_name" => "Grantham College",

    // ── Developer mode ────────────────────────────────────────────────
    // When true, a "Dev Login" form appears on the login page so you can
    // sign in with any name/email without going through Microsoft OAuth.
    // Emails are NOT sent in dev mode — the order endpoint simulates success.
    // NEVER leave this true in production.
    "dev_mode" => false,
];
