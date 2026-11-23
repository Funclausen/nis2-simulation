<?php
declare(strict_types=1);

// Copy to config.local.php on the server and fill values there.
// Never commit production credentials.
return [
    'dsn' => 'mysql:host=localhost;dbname=CHANGE_ME;charset=utf8mb4',
    'user' => 'CHANGE_ME',
    'password' => 'CHANGE_ME',
];
