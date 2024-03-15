<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Utils\Www\Controller;
// DIC configuration
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$container = $app->getContainer();

// view renderer
// $container['renderer'] = function ($c) {
//     $settings = $c->get('settings')['renderer'];
//     return new Slim\Views\PhpRenderer($settings['template_path']);
// };
$container['ci'] = function ($c) {
    return $c;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    if (isset($settings['type']) && $settings['type'] == 'file') {
//        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'], 0, $settings['level']));//每天生成一个日志
    }
    
    // if (isset($settings['type']) && $settings['type'] == 'mongodb') {
    //     $settings   = $c->get('settings')['mongodb'];
    //     $appId = $c->get('settings')['app']['tid'];
    //     if ($settings['user'] !== null && $settings['password'] !== null) {
    //         $auth = $settings['user'].':'.$settings['password'].'@';
    //     } else {
    //         $auth = '';
    //     }

    //     $host = isset($settings['port']) ? $settings['host'].':'.$settings['port'] : $settings['host'];
    //     $mongo = new MongoDB\Client("mongodb://{$auth}{$host}");
    //     $set = 'core_'.$appId;
    //     $mongodb = new Monolog\Handler\MongoDBHandler($mongo, $set, "www_logger"); 
    //     $logger->pushHandler($mongodb);
    // }

    //$logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::ERROR));
    return $logger;
};

$container['ckdb'] = function ($c) {
    $settings   = $c->get('settings')['ClickHouseDB'];
    if(!isset($settings)){
        return false;
    }
    $config = [
        'host' => $settings['host'],
        'port' => $settings['port'],
        'username' => $settings['username'],
        'password' => $settings['password'],
    ];
    if (isset($settings['auth_method'])) {
        $config['auth_method'] = $settings['auth_method'];
    }
    if (isset($settings['readonly'])) {
        $config['readonly'] = $settings['readonly'];
    }
    if (isset($settings['https'])) {
        $config['https'] = $settings['https'];
    }
    if (isset($settings['sslCA'])) {
        $config['sslCA'] = $settings['sslCA'];
    }
    $ckdb = new ClickHouseDB\Client($config, isset($settings['settings']) ? $settings['settings'] : []);
    $ckdb->database($settings['database']??'default');
    if(isset($settings['set_time_out'])){
        $ckdb->setTimeout($settings['set_time_out']);
    }
    if(isset($settings['connect_time_out'])){
        $ckdb->setConnectTimeOut($settings['connect_time_out']);
    }
    if(isset($settings['ping'])){
        $ckdb->ping($settings['ping']);
    }
    return $ckdb;
};

$container['db'] = function ($c) {

    $capsule = new \Illuminate\Database\Capsule\Manager;
    foreach ($c['settings']['db'] as $key => $v) {
        $capsule->addConnection($v, $key);
    }
    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};

$container['cache'] = function ($c) {
    $settings   = $c->get('settings')['cache'];
    $config     = [
        'scheme' => $settings['scheme'],
        'host' => $settings['host'],
        'port' => $settings['port'],
        'database' => $settings['database'],
    ];

    if (!empty($settings['password'])) {
        $config['password'] = $settings['password'];
    }
    if($config['scheme'] == 'tls'){
        $config['ssl'] = $settings['ssl'];
    }

    return new Predis\Client($config);
};

/*
$container['mongodb'] = function ($c) {
    // $settings   = $c->get('settings')['mongodb'];
    // $appId = $c->get('settings')['app']['tid'];
    // if ($settings['user'] !== null && $settings['password'] !== null) {
    //     $auth = $settings['user'].':'.$settings['password'].'@';
    // } else {
    //     $auth = '';
    // }

    // $host = isset($settings['port']) ? $settings['host'].':'.$settings['port'] : $settings['host'];
    // $m = new \MongoDB\Client("mongodb://{$auth}{$host}");
    // $db = $m->selectDatabase('core_'.$appId); // 选择一个数据库
    //return $db;
};*/

$container['redis'] = $container['cache'];

$container['Controller'] = function ($c) {
    return new Controller(__DIR__, $c);
};


$container['lang'] = function ($c) {
    return new \Logic\Define\Lang($c);
};
$langDefaultSet = $container['lang']->getLangSet();
define('LANG', $langDefaultSet);
$container['validator'] = function ($c) use ($langDefaultSet)  {
    return new Awurth\SlimValidation\Validator(true, require_once __DIR__ . '/../../config/lang/'.$langDefaultSet.'/validator.php');
};

$container['auth'] = function ($c) {
    return new \Logic\Auth\Auth($c);
};

$container['notFoundHandler'] = function ($c) {
    return function () use ($c) {
        $controller = new Controller(__DIR__, $c);
        return $controller->run();
    };
};

$container['phpErrorHandler'] = $container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $debug = [
                    'type' => get_class($exception),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString())
                ];
        $c->logger->error('程序异常', $debug);
        $data = [
                    'data' => null,
                    'attributes' => null,
                    'state' => -9999,
                    'message' => $c->lang->text('Program error')
                ];
        if (RUNMODE == 'dev') {
            $data['debug'] = $debug;
        }
        return $c['response']
            ->withStatus(500)
            // ->withHeader('Access-Control-Allow-Origin', '*')
            // ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            // ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Content-Type', 'application/json')
            ->withJson($data);
    };
};