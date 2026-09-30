<?php
// Copy to config/database.php and fill in real values (never commit it).
return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'dbname'   => getenv('DB_NAME') ?: 'your_db_name',
    'username' => getenv('DB_USER') ?: 'your_db_user',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4'
];
