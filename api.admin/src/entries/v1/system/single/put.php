<?php
return new class() extends \Logic\Admin\BaseController
{
    const TITLE = '开关设置';


    //前置方法
    protected $beforeActionList = [
        'verifyToken',
//        'authorize'
    ];

    public function run()
    {

        (new \lib\validate\BaseValidate(
            [
                'module'=>'require',
                'key'=>'require',
                'value'=>'require',
            ],
            [],
            [
                'module'=>'模块',
                'key'=>'设置',
                'value'=>'设置值',
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

       $res = DB::table('system_config')->where('module',$params['module'])->where('key',$params['key'])->update(['value'=>$params['value']]);
       if($res === false)
           return $this->lang->set(-2);
       return $this->lang->set(0);
    }

};
