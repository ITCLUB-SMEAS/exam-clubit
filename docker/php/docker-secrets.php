<?php

/**
 * Docker Secrets Bootstrap
 * 
 * Reads secrets from Docker secret files and sets them as environment variables.
 * Include this file early in bootstrap/app.php or create a custom entrypoint.
 */

$secretMappings = [
    'APP_KEY_FILE' => 'APP_KEY',
    'DB_PASSWORD_FILE' => 'DB_PASSWORD',
    'REDIS_PASSWORD_FILE' => 'REDIS_PASSWORD',
];

foreach ($secretMappings as $fileEnv => $targetEnv) {
    $filePath = getenv($fileEnv);
    
    if ($filePath && file_exists($filePath) && is_readable($filePath)) {
        $secret = trim(file_get_contents($filePath));
        if ($secret !== '') {
            putenv("{$targetEnv}={$secret}");
            $_ENV[$targetEnv] = $secret;
            $_SERVER[$targetEnv] = $secret;
        }
    }
}
