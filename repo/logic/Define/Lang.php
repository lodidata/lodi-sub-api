<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 14:16
 */

namespace Logic\Define;
use Slim\Container;

/**
 * Class Lang
 * 统一文本返回类
 * @package Logic\Define
 */
class Lang {

    protected $ci;

    protected $state;

    protected $defaultHttpCode = 200;

    protected $defaultErrorState = 21;

    protected $stateParams = [];

    protected $data;

    protected $define;

    protected $attributes;

    /**
     * Accept-Language转义为对应语言包名称
     * @var array $acceptLanguage
     */
    protected $acceptLanguage = [
                'zh-hans-cn'    => 'zh-cn',
                'zh-tw'         => 'zh-cn',
                'zh'            => 'zh-cn',
                'en'            =>  'en-us',
            ];

    /**
     * 当前语言
     * @var string
     */
    private $range = 'en-us';
    /**
     * 语言文件存放目录
     * @var string $langDirPath
     */
    protected $langDirPath = "/../../../config/lang/";

    public function __construct(Container $ci) {
        $this->ci = $ci;
        $this->detect();
        $this->define = $this->loadLangFiles();
    }

    /**
     * 赋值
     * @param       $state
     * @param array $stateParams
     * @param array $data
     *
     * @return $this
     */
    public function set($state, $stateParams = [], $data = [], $attributes = null) {
        $this->state = intval($state);
        $this->stateParams = $stateParams;
        $this->data = $data;
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * 取值
     * @return array [http状态码，state值, msg, data]
     * @throws \Exception
     */
    public function get() {
        if (!isset($this->define[$this->state])) {
            $this->state = $this->defaultErrorState;
            if (!isset($this->define[$this->state])) {
                throw new \Exception('缺失定义');
            }
        }

        $res = explode('|', $this->define[$this->state]);
        if (!empty($this->stateParams)) {
            $res[0] = call_user_func_array('sprintf', array_merge([$res[0]], $this->stateParams));
        }

        if (!isset($res[1])) {
            $res[1] = $this->defaultHttpCode;
        }

        return [intval($res[1]), intval($this->state), $res[0], $this->data, $this->attributes];
    }

    /**
     * 取数据
     * @return [type] [description]
     */
    public function getData() {
        return $this->get()[3];
    }

    /**
     * 获取状态
     * @return mixed
     * @throws \Exception
     */
    public function getState() {
        return $this->get()[1];
    }

    /**
     * 判断是否可以继续下一步
     * @return bool
     */
    /*public function allowNext() {
        return $this->get()[1] == 0 ? true : false;
    }*/

    public function allowNext() {
        return $this->get()[0] == 200 ? true : false;
    }

    public function __get($field) {
        if(isset($this->$field)) {
            return $this->$field;
        }
        return $this->ci->$field;
    }

    /**
     * 获取当前语言
     * @access public
     * @return string
     */
    public function getLangSet()
    {
        return $this->range;
    }

    /**
     * 自动侦测设置获取语言选择
     * @access public
     * @return string
     */
    public function detect(): string
    {
        // 自动侦测设置获取语言选择
        $langSet = '';
        //Header中设置了语言变量
        if($this->ci->request->getHeaderLine('lang')){
            $langSet = strtolower($this->ci->request->getHeaderLine('lang'));
        }
        //Cookie中设置了语言变量
        elseif($this->ci->request->getCookieParam('lang'))
        {
            $langSet = strtolower($this->ci->request->getCookieParam('lang'));
        }
        // 自动侦测浏览器语言
        elseif ($this->ci->request->getServerParam('HTTP_ACCEPT_LANGUAGE')){
            $match = preg_match('/^([a-z\d\-]+)/i', $this->ci->request->getServerParam('HTTP_ACCEPT_LANGUAGE'), $matches);
            if ($match) {
                $langSet = strtolower($matches[1]);
                if (isset($this->acceptLanguage[$langSet])) {
                    $langSet = $this->acceptLanguage[$langSet];
                }
            }
        }else{
            //回调接口不传语言默认系统语言
            $site_type = $website = $this->ci->get('settings')['website']['site_type'];
            if($site_type == 'ncg'){
                $langSet = 'th';
            }elseif($site_type == 'es-mx'){
                $langSet = 'es-mx';
            }else{
                $langSet = 'en-us';
            }
        }
        //查当前语言是否在语言目录中
        if (in_array($langSet, $this->getLangDir())) {
            // 合法的语言
            $this->range = $langSet;
        }
        return $this->range;
    }

    /**
     * 获取语言包所有目录
     * @return array
     */
    public function getLangDir() : array
    {
        $langDirArr = scandir(__DIR__.$this->langDirPath);
        unset($langDirArr[0],$langDirArr[1]);
        sort($langDirArr);
        $this->ci->redis->set(CacheKey::$perfix['language_dir'], implode(',', $langDirArr));

        return $langDirArr;
    }

    /**
     * 加载语言包
     * @return array|mixed
     */
    public function loadLangFiles()
    {
        $filesDir = __DIR__.$this->langDirPath.$this->range.DIRECTORY_SEPARATOR;
        $files = scandir($filesDir);
        $result = [];
        foreach ($files as $file){
            if(is_file($filesDir.$file) && $file !='validator.php'){
                $result += require $filesDir.$file;
            }
        }
        return $result;
    }

    /**
     * 语言包文字翻译
     * @param string $key 语言包中键
     * @param array $params 键中%s要替换的文字，可以多个%s
     * @return int|mixed|string
     */
    public function text($key, $params = [])
    {
        if (!isset($this->define[$key])) {
            $res = $key;
        }else{
            $res = $this->define[$key];
        }
        if (!empty($params)) {
            $res = call_user_func_array('sprintf', array_merge([$res], $params));
        }
        return $res;
    }
}