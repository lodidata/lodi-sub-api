<?php

use Logic\Level\Level as Level;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '更新层级支付';
    const DESCRIPTION = '更新层级支付';
    
    const QUERY       = [
    ];
    const SCHEMAS     = [
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $levelId = $params['id'];
        $levelLogic = new Level($this->ci);
        $offlineData = [];
        $onlineData = [];
        foreach ($params['online'] ??[] as $k=>$v){
            $onlineData[] = ['level_id'=>$levelId,'pay_plat'=>$v];
        }
        foreach ($params['offline'] ??[] as $k=>$v){
            $offlineData[] = ['level_id'=>$levelId,'pay_id'=>$v];
        }
        if($levelLogic->onlineSet($levelId,$onlineData) &&   $levelLogic->offlineSet($levelId,$offlineData)){
            return [];
        }else{
            $this->lang->set(10404);
        }



    }

};
