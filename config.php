<?php
return [
    // Database connection details
    'db_host'   => 'localhost',
    'db_name'   => 'your_database_name',
    'db_user'   => 'your_database_user',
    'db_pass'   => 'your_database_password',

    // Security token for log viewer + cron job access
    'log_token' => 'SEcreTKey2025', // Used as ?key=... and ?token=...

    // Site branding
    'site_name' => 'YOURSITE',
    'site_url'  => 'https://YOURSITE.com', // No trailing slash

    // Flarum table names (customize prefix)
    'users_table'    => 'flarum_users',
    'settings_table' => 'flarum_settings',

    // Testing mode
    'test_mode'  => false,                   // Set true to send only to test_email
    'test_email' => 'support@yoursite.com'   // Address for test runs
];
