 <?php
 require_once __DIR__ . '/../repo/vendor/autoload.php';

class SGR {

    protected $files = [];

    public function initWWWApi() {
        $base = [
            'swagger' => '2.0',
            'info' => [
                'title' => '前端API',
                'description' => 'API适用于移动端和PC端  '.PHP_EOL
                .'文档接口参数类型 [string, integer, array, boolean, object, number]  '.PHP_EOL
                .'APP请求时请带上header头 [sv, av, vv, av] pl平台 mm 手机型号 av app版本 sv 系统版本  uuid 唯一标识  '.PHP_EOL
                .'请求类型Content-type: application/json  '.PHP_EOL
                .'需要权限验证的接口请带上Authorization头, 接口状态返回401时请跳转登录  '.PHP_EOL,
                'version' => '1.0.0',
            ],
            'host' => 'api-www.bag.cc',
             'basePath' => "",
            'schemes' => [
                "http"
            ]
        ];

        $project = __DIR__ . '/../api.www/src/entries/v1';
        $this->showdir($project);
         //print_r($this->files);die;
        $this->fetch($base);
        return $base;
    }

    public function initAdminApi() {
        $base = [
            'swagger' => '2.0',
            'info' => [
                'title' => '后端API',
                'description' => 'API适用于后端  '.PHP_EOL
                .'文档接口参数类型 [string, integer, array, boolean, object, number]  '.PHP_EOL
                .'APP请求时请带上header头 [sv, av, vv, av] pl平台 mm 手机型号 av app版本 sv 系统版本  uuid 唯一标识  '.PHP_EOL
                .'请求类型Content-type: application/json  '.PHP_EOL
                .'需要权限验证的接口请带上Authorization头, 接口状态返回401时请跳转登录  '.PHP_EOL,
                'version' => '1.0.0',
            ],
             'basePath' => "",
            'host' => 'api-admin.bag.cc',
            'schemes' => [
                "http"
            ]
        ];

        $project = __DIR__ . '/../api.admin/src/entries/v1';
        $this->showdir($project);
        $this->fetch($base);
        return $base;
    }

    /**
     * 遍历目录生成接口文档数组
     * @param array $base
     */
    public function fetch(&$base) {
        $tags = [];
        foreach ($this->files as $file) {
            //$file = 'D:\www\bag-api\doc/../api.admin/src/entries/v1/active/get.php';
           // echo '1-'.$file.PHP_EOL;
            $params = explode('/',$file);
            if(in_array('v2',$params)){
                $url = explode('/v2/', $file)[1];
            }else{
                $url = explode('/v1/', $file)[1];
            }
            $method = explode('.', basename($url))[0];
            $suffix = explode('.', $url)[1];
            if($suffix != 'php') {
                continue;
            }

            $url = dirname($url);
            $v = require $file;
            $refl = new ReflectionClass($v);
            $data = $refl->getConstants();
            //定义HIDDEN属性不生成接口文档
            $hidden = isset($data['HIDDEN']) ? $data['HIDDEN'] : '';
            if(true === $hidden){
                continue;
            }
            $url = '/'.$url;
            $pathStruct = $this->struct(
                            $url,
                            $method,
                        isset($data['TITLE']) ? $data['TITLE'] : '',
                  isset($data['DESCRIPTION']) ? $data['DESCRIPTION'] : '',
                        isset($data['TYPE']) ? $data['TYPE'] : '',
                    isset($data['FORMDATAS']) ? $data['FORMDATAS'] : '',
                      isset($data['PARAMS']) ? $data['PARAMS'] : '',
                        isset($data['QUERY']) ? $data['QUERY'] : '',
                     isset($data['PATHS']) ? $data['PATHS'] : '',
                    isset($data['SCHEMAS']) ? $data['SCHEMAS'] : '',
                      isset($data['HEADERS']) ? $data['HEADERS'] : '',
                        isset($data['TOKEN']) ? $data['TOKEN'] : '',
                                isset($data['TAGS']) ? $data['TAGS'] : ''
                );
            if (!isset($base['paths'][$url])) {
                $base['paths'][$url] = [];
            }
            $base['paths'][$url][$method] = $pathStruct[$method];
            if(isset($data['TAGS']) && $data['TAGS']){
                $tags[] = $data['TAGS'];
            }
            // break;
            //echo '2-'.$file.PHP_EOL;
        }
        if($tags){
            $tags = array_unique($tags);
            sort($tags);
            foreach($tags as $tag){
                $base['tags'][] = [
                    'name' => $tag
                ];
            }
        }
    }

    /**
     * 遍历目录下所有子目录及文件生成文件路径
     * /a/b/c.php
     * @param string $path 遍历目录
     * @return mixed
     */
    public function showdir($path)
    {
        $dh = opendir($path);//打开目录
        while(($d = readdir($dh)) != false) {
            //逐个文件读取，添加!=false条件，是为避免有文件或目录的名称为0
            if($d=='.' || $d == '..') {//判断是否为.或..，默认都会有
                continue;
            }

            $file = $path.'/'.$d;
            if (is_file($file)) {
                $this->files[] =  $file;
            }
            if(is_dir($path.'/'.$d)) {//如果为目录
                $this->showdir($path.'/'.$d);//继续读取该目录下的目录或文件
            }
        }
    }

    /**
     * 解析接口文件中定义常量
     * @param string $url 生成接口URL URL/a/b/c
     * @param string $method 接口传参模式 POST GET DELETE PATCH PUT
     * @param string $title 接口名称
     * @param string $description 接口文档描述
     * @param string $type 特殊传参类型 上传文件类型multipart/form-data 表单类型application/x-www-form-urlencoded
     * @param array $formDatas 表单传参
     * @param array $params JSON传参
     * @param array $query QUERY传参
     * @param array $paths PATH传参
     * @param array $responses 返回的结果参数
     * @param array $headers HEADER传参
     * @param boolean $token 是否需要登录TOKEN权限验证 默认为false 在header中生成Authorization 默认值{{token}}变量参数在接口全局变量中设置
     * @return array 返回组合后的参数数组
     */
    public function struct(
        $url,
        $method,
        $title,
        $description,
        $type,
        $formDatas,
        $params,
        $query,
        $paths,
        $responses,
        $headers,
        $token,
        $tags
    ) {
        $tag = (explode('/', $url))[1] ?? '';
        $pathStruct = [
            $method => [
                'summary' => strval($title),
                'description' => strval($description),
                'tags' => [
                    0 => $tags?:$tag,
                ],
                'produces' => [
                    0 => 'application/json',
                ],
                "parameters" => [
                    [
                        "name"      => "lang",
                        "in"        => "header",
                        "required"  => true,
                        "type"      => "string",
                        "description"=> "请求访问语言 默认为th泰语",
                        "default"   => "th"
                    ],
                    [
                        "name"      => "pl",
                        "in"        => "header",
                        "required"  => true,
                        "type"      => "string",
                        "description"=> "请求平台 pc,h5,android,ios 默认为pc",
                        "default"   => "pc"
                    ]
                ],
                "responses" => [
                    "200" => [
                        "description" => "操作成功",
                        "schema" => $this->schema($responses)
                    ],
                ],
            ],
        ];

        //特殊传参类型 上传文件类型multipart/form-data 表单类型application/x-www-form-urlencoded
        if(!empty($type)){
            $pathStruct[$method]['consumes'][] = $type;
        }
        //formData传参
        if (!empty($formDatas)) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], $this->parameters($formDatas, 'formData'));
        }
        //Path传参
        if (!empty($paths)) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], $this->parameters($paths, 'path'));
        }
        //Query传参
        if (!empty($query)) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], $this->parameters($query, 'query'));
        }
        //json传参
        if (!empty($params)) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], $this->parameters($params, 'body'));
        }
        //Headers传参
        if (!empty($headers)) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], $this->parameters($headers, 'header'));
        }
        //Authorization
        if (true === $token) {
            $pathStruct[$method]['parameters'] = array_merge($pathStruct[$method]['parameters'], [
                    [
                        "name"      => "Authorization",
                        "in"        => "header",
                        "required"  => true,
                        "type"      => "string",
                        "description"=> "登录权限TOKEN",
                        "default"   => "{{token}}"
                    ]
            ]);
        }

        if (empty($pathStruct[$method]['parameters'])) {
            unset($pathStruct[$method]['parameters']);
        }
        return $pathStruct;
    }

    /**
     * 解析response中schema分解生成对应格式
     * @param array $data
     * @return array
     */
    public function schema($data)
    {
        $output = ['type' => 'object'];
        if (empty($data) || !is_array($data)) {
            return $output;
        }
        if (isset($data[0])) {
            $res = $this->schema($data[0]);
            $output = ['type' => 'array', 'items' => $res];
        } else {
            $output['properties'] = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $res = $this->schema($value);
                    $output['properties'][$key] = $res;
                    continue;
                }
                list($type, $format, $required, $description, $default, $enums) = $this->formatType($value);
                $output['properties'][$key] = [
                    'type'          => $type,
                    //'description'   => $description,
                    //'format'        => $format,
                    'required'      => $required
                ];
                if($description){
                    $output['properties'][$key]['description'] = $description;
                }
                if($format){
                    $output['properties'][$key]['format'] = $format;
                }
                if(true === $required){
                    $output['required'][] = $key;
                }

            }
        }
        return $output;
    }
    /**
     * 解析parameters参数分解生成对应格式
     * @param array $data 参数对象
     * @example [
     *  'id' => 'int(required) #id',
     *  'title' => 'string() #标题',
     * ]
     * @param string $in 参数类型 [formData,path,query,body,header]
     * @return array
     * [
        [
            "name" => "lang",
            "in" => "header",
            "required" => true,
            "type" => "string",
            "description" => "请求访问语言 默认为th泰语",
            "default" => "th"
        ],
        [
            "name" => "start_time",
            "in" => "query",
            "required" => true,
            "type" => "string",
            "description" => "开始时间 2021-8-19 17  =>38  =>31",
            "format" => "date-time"
        ],
        [
            "name" => "end_date",
            "in" => "query",
            "required" => true,
            "type" => "string",
            "description" => "结束日期 2021-8-19",
            "format"  => "date"
        ],
        [
            "name" => "status",
            "in" => "query",
            "required" => true,
            "type" => "string",
            "description" => "状态值(pass  =>通过, rejected  =>拒绝, pending  =>待处理, undetermined  =>未解决的)",
            "default" => "pending",
            "enum" => [
                "pass",
                "rejected",
                "pending",
                "undetermined"
            ]
        ]
    ]
     *
     */
    public function parameters(array $data, string $in = 'body')
    {
        $data = isset($data[0]) ? $data[0] : $data;
        $output = $outBody = [];
        if($in == 'body'){
            $outBody = [
                'name'          => 'body',
                'in'            => 'body',
                'required'      => 'true',
                "schema"        =>[
                    'type'      => 'object'
                ]
            ];
        }
        if (!empty($data) && is_array($data)) {
            $i = 0;
            foreach ($data as $key => $value)
            {
                //TODO 数组、对象未处理
                if (is_array($value)) {
                    continue;
                }
                list($type, $format, $required, $description, $default, $enums) = $this->formatType($value);

                //JSON传参数
                if($in == 'body'){
                    $outBody['schema']['properties'][$key] = [
                            'description'   => $description,
                            'type'          => $type,
                    ];
                    if($format){
                        $outBody['schema']['properties'][$key]['format'] = $format;
                    }
                    if($default){
                        $outBody['schema']['properties'][$key]['default'] = $default;
                    }
                }else{
                    $output[$i] = [
                        'name'          => $key,
                        'in'            => $in,
                        'required'      => $required,
                        'description'   => $description,
                        'type'          => $type,
                        //'format'        => $format,
                        //'default'       => $default,
                    ];
                    if($format){
                        $output[$i]['format'] = $format;
                        if($format == 'enum' && !empty($enums)){
                            $output[$i]['enum'] = $enums;
                        }
                    }
                    if($default){
                        $output[$i]['default'] = $default;
                    }
                }
                $i++;
            }

            if($in == 'body'){
                $output[] = $outBody;
            }
        }
        return $output;
    }

    /**
     * 类型转化成postman接口文档能识别的类型及数据格式化
     * @example [
     *  'id'     => "int(required) #ID",
     *  'title'  => "string() #标题",
     *  'money'  => "float() #金额 1.00",
     *  'status' => 'enum[all,paid,pending,canceled](required,all) #交易状态 all 全部，paid(已存款), pending(未处理), canceled(已取消)'
     * ]
     * @param string $value 接口中的定义参数值
     * @return array [type:数据类型，format:数据格式化类型, required:是否必填，description:描述，default:默认值，enums:枚举类型]
     */
    public function formatType($value):array
    {
        $type = $new_type = '';//字段数据类型
        $required = false; //是否必填
        $default = ''; //默认值
        $format = ''; //数据格式化类型
        $description = ''; //字段描述
        $enums = [];

        if(is_null($value)){
            return ['string', $format, $required, $description, $default, $enums];
        }

        //“#”为类型与描述分隔符
        $vs = explode('#', $value);
        //判断类型中是否有'(' , 小括号为如："enum[1,2,3](required,1) #性别 (1:男,2:女,3:保密)"
        $types = explode('(', trim($vs[0]));
        if (isset($types[1])){
            //判断括号()中是否有逗号','分隔内容(是否必填,默认值)
            $leftParents = trim(rtrim($types[1], ')'));
            //判断是否为空括号
            if($leftParents){
                $parenthesis = explode(',', $leftParents);
                $required = 'required' == strtolower(trim($parenthesis[0])) ? true : false;
                $default = trim($parenthesis[1]);
            }

        }

        $description = trim(isset($vs[1]) ? $vs[1] : $value);

        //判断类型中是否带中括号[]，内容为枚举类型值
        $orgin_types = explode('[', trim($types[0]));
        if(isset($orgin_types[1])){
            $enums = explode(',', trim(rtrim($orgin_types[1], ']')));
        }
        //类型
        $type = strtolower(trim($orgin_types[0]));
        switch ($type)
        {
            case 'int':
            case 'integer':
                $new_type = "integer";
                $format = "int32";
                break;
            case 'float':
            case 'double':
                $format = $type;
                $new_type = 'number';
                break;
            case 'bool':
            case 'boolean':
                $new_type = 'boolean';
                $format = '';
                break;
            case 'byte': //base64
            case 'string':
            case 'binary':
            case 'date':
            case 'dateTime':
            case 'password':
            case 'enum':
                $format = $type;
                $new_type = 'string';
                break;
            default:
                $new_type = 'string';
                $format = '';
        }
        if($new_type == 'dateTime'){
            $format = "date-time";
        }

        return [$new_type, $format, $required, $description, $default, $enums];
    }
}

try{
    echo 'swagger start'.PHP_EOL;
    $sgr = new SGR();
    $data = $sgr->initWWWApi();
    $content = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    file_put_contents(__DIR__.'/www-api.swagger.json', $content);
    echo 'swagger api.www end'.PHP_EOL;

    $sgr = new SGR();
    $data = $sgr->initAdminApi();
    $content = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    file_put_contents(__DIR__.'/admin-api.swagger.json', $content);
    echo 'swagger api.admin end'.PHP_EOL;
}catch (\Exception $e){
    print_r($e->getMessage());
}