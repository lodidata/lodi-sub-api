<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'ncgGameOrderServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/gameOrderServer.log';
$worker = new \Workerman\Worker();
$worker->count = 23;
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
    // 超管拉单1 JOKER
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JOKER']]);
        });
    }

    $proccId++;
    // 超管拉单2 JILI
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JILI']]);
        });
    }

    $proccId++;
    // 超管拉单3 PP
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PP']]);
        });
    }

    $proccId++;
    // 超管拉单4 EVO
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVO']]);
        });
    }

    $proccId++;
    // 超管拉单5 SEXYBCRT
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SEXYBCRT']]);
        });
    }

    $proccId++;
    // 超管拉单6 JDB
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JDB']]);
        });
    }

    $proccId++;
    // 超管拉单7 SBO
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SBO']]);
        });
    }

    $proccId++;
    // 超管拉单8 SA
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SA']]);
        });
    }

    $proccId++;
    // 超管拉单9 CQ9
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'CQ9']]);
        });
    }

    $proccId++;
    // 超管拉单10 PG
    if ($worker->id === $proccId) {
        $interval = 20; //2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PG']]);
        });
    }

    $proccId++;
    // 超管拉单11 KMQM
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'KMQM']]);
        });
    }

    $proccId++;
    // 超管拉单12 TF
    if ($worker->id === $proccId) {
        $interval = 20;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'TF']]);
        });
    }

    $proccId++;
    // 超管拉单13 SV388
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SV388']]);
        });
    }

    $proccId++;
    // 超管拉单14 RCB
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'RCB']]);
        });
    }

    $proccId++;
    // 超管拉单15 SGMK
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SGMK']]);
        });
    }

    $proccId++;
    // 超管拉单16 PNG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PNG']]);
        });
    }

    $proccId++;

    // 拉取超管注单17 DG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'DG']]);
        });
    }

    $proccId++;
    // 拉取超管注单18 PB
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PB']]);
        });
    }

    $proccId++;
    // 超管拉单19 BG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'BG']]);
        });
    }

    $proccId++;
    // 超管拉单20 FC
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'FC']]);
        });
    }

    $proccId++;
    // 超管拉单21 TCG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'TCG']]);
        });
    }

    $proccId++;
    // 超管拉单22 RSG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'RSG']]);
        });
    }

    $proccId++;
    // 拉取超管注单23 IG
    if ($worker->id === $proccId) {
        $interval = 20;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'IG']]);
        });
    }
};
\Workerman\Worker::runAll();