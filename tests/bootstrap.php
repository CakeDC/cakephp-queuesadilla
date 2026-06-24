<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\Utility\Security;
use function Cake\Core\env;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
define('APP_DIR', 'TestApp');
define('WEBROOT_DIR', 'webroot');

define('TMP', sys_get_temp_dir() . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('SESSIONS', TMP . 'sessions' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('CORE_TESTS', CORE_PATH . 'tests' . DS);
define('CORE_TEST_CASES', CORE_TESTS . 'TestCase');
define('TEST_APP', ROOT . DS . 'tests' . DS);

define('APP', TEST_APP . 'App' . DS);
define('WWW_ROOT', TEST_APP . 'webroot' . DS);
define('CONFIG', TEST_APP . 'config' . DS);

require ROOT . '/vendor/autoload.php';
require ROOT . '/vendor/cakephp/cakephp/src/functions.php';
require_once CORE_PATH . 'config/bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

foreach ([TMP, LOGS, CACHE, TMP . 'cache' . DS . 'models', TMP . 'cache' . DS . 'persistent', TMP . 'cache' . DS . 'views'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

Configure::write('debug', filter_var(env('DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN));
Configure::write('App', [
    'namespace' => 'Josegonzalez\CakeQueuesadilla\Test\App',
    'encoding' => 'UTF-8',
    'defaultLocale' => I18n::getDefaultLocale(),
    'paths' => [
        'plugins' => [dirname(APP) . DS . 'plugins' . DS],
        'templates' => [APP . 'Template' . DS],
    ],
]);

Cache::setConfig([
    'default' => [
        'engine' => 'File',
    ],
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_queuesadilla_cake_core_',
        'serialize' => true,
    ],
    '_cake_model_' => [
        'engine' => 'File',
        'prefix' => 'cake_queuesadilla_cake_model_',
        'serialize' => true,
    ],
]);

Configure::write('Session', [
    'defaults' => 'php',
]);

Security::setSalt('queuesadilla-test-salt-not-for-production-use-only');
Configure::write('App.fullBaseUrl', '');

if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
    'url' => getenv('db_dsn'),
    'timezone' => 'UTC',
]);

if (env('FIXTURE_SCHEMA_METADATA')) {
    $loader = new SchemaLoader();
    $loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}
