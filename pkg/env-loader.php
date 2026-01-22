<?php
if (!defined('ABSPATH')) exit;

/**
 * Helper để đọc file .env
 */
function cf7_get_env($key, $default = null) {
    // Path to .env file (plugin root)
    // File này nằm ở /pkg/env-loader.php, nên .env nằm ở ../.env
    $env_path = plugin_dir_path(__FILE__) . '../.env';
    
    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;
            
            // Parse key=value
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                
                if ($name === $key) {
                    return $value;
                }
            }
        }
    }
    return $default;
}
