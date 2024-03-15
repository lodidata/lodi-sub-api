<?php

namespace Logic\GameApi;

/**
 * 控制
 */
class Format {
    /**
     * @var array 注册的依赖数组
     */
    protected static $registry = array();

    /**
     * 添加一个 resolve （匿名函数）到 registry 数组中
     *
     * @param string  $name    依赖标识
     * @param Closure $resolve 一个匿名函数，用来创建实例
     * @return void
     */
    public static function register($name) {
        static::$registry[$name] = __NAMESPACE__.'\Format\\'.$name;
    }

    /**
     * 返回一个实例
     *
     * @param string $name 依赖的标识
     * @return mixed
     * @throws \Exception
     */
    public static function resolve($name) {
        global $app;
        $ci = $app->getContainer();
        try {
            if (!static::registered($name)) {
                self::register($name);
            }
        }catch (\Exception $e) {
            throw new \Exception($ci->lang->text("Nothing registered with that name"));
        }
        if(!class_exists(static::$registry[$name]))  return (new Format());

        $name = static::$registry[$name];
        return (new $name($ci));
    }

    /**
     * 查询某个依赖实例是否存在
     *
     * @param string $name
     * @return bool
     */
    public static function registered($name) {
        return array_key_exists($name, static::$registry);
    }

    public function playFormat() {
        global $app;
        return $app->getContainer()->lang->text("There is no format class for this game");
    }
}


