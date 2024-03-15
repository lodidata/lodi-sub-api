<?php
return new class() extends \Logic\Admin\BaseController
{
    const TITLE = '游戏洗码百分比设置';


    //前置方法
   protected $beforeActionList = [
       'verifyToken', 'authorize'
   ];

    public function run($id = '')
    {
        $this->checkID($id);
        $ximaSingle = DB::table('xima_config')->find($id);
        if(!$ximaSingle)
            return $this->lang->lang->set(10015);
        (new \lib\validate\BaseValidate(
            [
                'percent'=>'require|between:0,2000'
            ],
            [],
            [
                'percent'=>'洗码百分比'
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

       $res = DB::table('xima_config')->where('id',$id)->update(['percent'=>$params['percent']]);
       if($res === false)
           return $this->lang->set(-2);
       return $this->lang->set(0);
    }

};
