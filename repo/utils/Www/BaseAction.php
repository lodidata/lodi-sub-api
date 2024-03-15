<?php
/**
 * Created by PhpStorm.
 * User: 95684
 * Date: 2019/4/19
 * Time: 10:48
 */

namespace Utils\Www;
use DB;

class BaseAction extends Action
{
    public function init($ci){
        parent::init($ci);
    }

    public function addVtRequestLog(){
        $uri =  $this->request->getUri();
        $params = $this->request->getParams();
    }
}