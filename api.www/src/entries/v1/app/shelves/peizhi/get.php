<?php

use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "获取IOS包信息";
    const QUERY = [
        'boundID' => 'string(, 30)',
        'ver' => 'string(required, 150) #版本',
   ];
    const SCHEMAS = [];

    public function run()
    {
//        peizhi 作url  字段 shebei : ma==(不过) mq== (过)
        $bound = $this->request->getParam('boundID','');
        $ver = $this->request->getParam('ver','');
        $result =  array(
            'shebei'=>'ma==',
        );
        if($bound) {
            $ios = \Model\AppBag::where('bound_id', '=', $bound)->where('type', '=', 1)
                ->where('delete', '=', 0)->where('url', '=', $ver)->first();
            if($ios) {
                $result['shebei'] = $ios->status ? 'mq==' : 'ma==';
            }
        }
        return $result;
    }
};
