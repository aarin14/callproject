<?php
// Custom error logging function
function write_log($message) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    
    // Try to write to log file, but don't fail if we can't
    @file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Also output to PHP error log as backup
    error_log($message);
}
?> 