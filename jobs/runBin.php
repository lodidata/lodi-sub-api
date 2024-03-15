<?php
require_once __DIR__ . '/../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../config/settings.php';

if(defined('ENCRYPTMODE') && ENCRYPTMODE) {
    $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'],false);
}
$app = new \Slim\App($settings);

// Set up dependencies
require_once __DIR__ . '/src/dependencies.php';

// Register middleware
require_once __DIR__ . '/src/middleware.php';

require_once __DIR__ . '/src/common.php';

$app->run();
$app->getContainer()->db->getConnection('default');
$logger = $app->getContainer()->logger;
$suffix = '.php';
// 打印sql
//if (isset($settings['settings']['website']['DBLog']) && $settings['settings']['website']['DBLog']) {
    $app->getContainer()->db->getConnection()->enableQueryLog();
//}
//print_r($argv);exit;
if (!isset($argv[1])) {
    echo '请输入执行的bin名称', PHP_EOL;
    return;
}

$file = __DIR__ . '/src/bin/' . $argv[1] . $suffix;
if (!is_file($file)) {
    echo 'bin脚本不存在:'.$file, PHP_EOL;
    return;
}
require_once $file;

// 写入sql
//if (isset($settings['settings']['website']['DBLog']) && $settings['settings']['website']['DBLog']) {

    foreach ($app->getContainer()->db->getConnection()->getQueryLog() ?? [] as $val) {
        $sql_format = str_replace('?', '%s', $val['query']);
        $sql = call_user_func_array('sprintf', array_merge((array)$sql_format,$val['bindings']));
        $logger->error('DBLog', $val);
    }
//}