<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'mxnGameOrderServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/gameOrderServer.log';
$worker = new \Workerman\Worker();
$worker->count = 16;
$worker->name = $alias;

// 防多开配置
// if ($app->getContainer()->redis->get(\Logic\Define\CacheKey::$perfix['prizeServer'])) {
//     echo 'prizeServer服务已启动，如果已关闭, 请等待5秒再启动', PHP_EOL;
//     exit;
// }

$worker->onWorkerStart = function ($worker) {
    global $app, $logger;
    /**********************config start*******************/
    $settings = require __DIR__ . '/../../config/settings.php';
    if (defined('ENCRYPTMODE') && ENCRYPTMODE) {
        $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'], false);
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
    // 拉取超管注单1 FC
    if ($worker->id === $proccId) {
        $interval = 30;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'FC']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验2 JDB
    if ($worker->id === $proccId) {
        $interval = 120;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JDB']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验3 JILI
    if ($worker->id === $proccId) {
        $interval = 10;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JILI']]);
        });
    }

    $proccId++;
    // 拉取超管注单4 PB
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PB']]);
        });
    }

    $proccId++;
    // 拉取超管注单5 PP
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PP']]);
        });
    }


    $proccId++;
    // 拉取超管注单6 MG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'MG']]);
        });
    }

    $proccId++;
    // 拉取超管注单7 BSG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'BSG']]);
        });
    }

    $proccId++;
    // 拉取超管注单8 VIVO
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'VIVO']]);
        });
    }

    $proccId++;
    // 拉取超管注单9 HB
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'HB']]);
        });
    }

    $proccId++;
    // 拉取超管注单10 ALLBET
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'ALLBET']]);
        });
    }

    $proccId++;
    // 拉取超管注单11 QT
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'QT']]);
        });
    }

    $proccId++;
    // 拉取超管注单12 BTI
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'BTI']]);
        });
    }

    $proccId++;
    // 拉取超管注单13 PG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PG']]);
        });
    }

    $proccId++;
    // 拉取超管注单14 IG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'IG']]);
        });
    }

    $proccId++;
    // 拉取超管注单15 EVOPLAY
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVOPLAY']]);
        });
    }

    $proccId++;
    // 拉取超管注单16 EVO
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVO']]);
        });
    }
};
\Workerman\Worker::runAll();