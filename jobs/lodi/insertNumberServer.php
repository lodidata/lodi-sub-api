<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'lodiInsertNumberServer';

\Workerman\Worker::$logFile = LOG_PATH.'/php/insertNumberServer.log';
$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = $alias;

// 防多开配置
// if ($app->getContainer()->redis->get(\Logic\Define\CacheKey::$perfix['prizeServer'])) {
//     echo 'prizeServer服务已启动，如果已关闭, 请等待5秒再启动', PHP_EOL;
//     exit;
// }

$worker->onWorkerStart = function ($worker) {
    global $app,$logger;

    /**********************config start*******************/
    $settings = require __DIR__ . '/../../config/settings.php';
    if(defined('ENCRYPTMODE') && ENCRYPTMODE) {
        $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'],false);
    }
    $app = new \Slim\App($settings);
    // Set up dependencies
    require_once __DIR__ . '/../src/dependencies.php';

    // Register middleware
    require_once __DIR__ . '/../src/middleware.php';

    require_once __DIR__ . '/../src/common.php';

    $app->run();
    $app->getContainer()->db->getConnection('default');
    $logger = $app->getContainer()->logger;
    /**********************config end*******************/


    $proccId = 0;
    if ($worker->id === $proccId) {
        $interval = 1;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\Lottery\OpenPrize::randomInsertNumber();
        });
    }

};
\Workerman\Worker::runAll();