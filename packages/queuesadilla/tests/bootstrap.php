<?php
error_reporting(-1);
date_default_timezone_set('UTC');

if (!defined('SIGQUIT')) {
    define('SIGQUIT', 3);
}
if (!defined('SIGTERM')) {
    define('SIGTERM', 15);
}
if (!defined('SIGINT')) {
    define('SIGINT', 2);
}
if (!defined('SIGUSR1')) {
    define('SIGUSR1', 10);
}

foreach ([
    'NULL_URL' => 'null://?timeout=1&database=false',
    'MEMORY_URL' => 'memory://',
    'SYNCHRONOUS_URL' => 'sync://',
] as $variable => $default) {
    if (!getenv($variable)) {
        putenv($variable . '=' . $default);
    }
}

$mongoTestUrl = getenv('MONGO_TEST_URL');
if ($mongoTestUrl && !getenv('MONGO_URL')) {
    putenv('MONGO_URL=' . $mongoTestUrl);
}

/**
 * Path trickery ensures test suite will always run, standalone or within
 * another composer package. Designed to find composer autoloader and require
 */
$vendorPos = strpos(__DIR__, 'vendor/josegonzalez/queuesadilla');
if ($vendorPos !== false) {
    $vendorDir = substr(__DIR__, 0, $vendorPos) . 'vendor/';
    require $vendorDir . 'autoload.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
}
