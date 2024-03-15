<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "获取银行列表";
    const DESCRIPTION = "返回所有可用银行列表  \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const QUERY = [
        "name"  => "string() #银行名称",
        'page'  => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,20) #分页显示记录数 默认20条记录"
    ];
    
    const SCHEMAS = [
       [
           "id" => "int(required) #id",
           "name" => "string(required) #银行名称",
           "code" => "string(required) #银行代码",
           "logo" => "string(required) #银行logo图标",
           "online" => "enum[1,0](required,1) #是否线上（1：线上，0：线下）  为线上的时候，支行就不可以编辑",
           "shortname"   => 'string() #银行简称',
       ]
   ];

    
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $page      = $this->request->getQueryParam('page', 1);
        $pageSize = $this->request->getQueryParam('page_size', 20);

        $condition['type'] = 1;
        $condition['status'] = 'enabled';
        $condition['name'] = $this->request->getQueryParam('name');
        $data = \Model\Bank::getRecords('', $condition, $page, $pageSize);
        return $this->lang->set(0, [], $data->toArray()['data'], ['number' => $page, 'size' => $pageSize, 'total' => $data->total()]); 
    }
};