<?php
return [
    // Database connection details
    'db_host'   => 'localhost',            // usually localhost
    'db_name'   => 'your_database_name',   // your Flarum/Newsletter DB name
    'db_user'   => 'your_database_user',   // your MySQL username
    'db_pass'   => 'your_database_pass',   // your MySQL password
    'log_token' => 'super-secret-token',    // change to a strong, private value
    'site_name' => 'MyCoolForum',            // Flarum Site Name to apear on newsletter and pages
    'site_url' => 'https://mycoolforum.com', // Flarum Domain for links
    'users_table' => 'flarum_users',        // User table PREFIX_users
    'settings_table' => 'flarum_settings'    // Settings table PREFIX_settings
];
