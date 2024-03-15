<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/11 16:20
 */
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '银行列表接口';
    const DESCRIPTION = '获取银行信息列表';

    const QUERY       = [
        'page'      => 'int()   #页码',
        'page_size' => 'int()    #每页大小',
        'name'      => 'string()   #银行名称',
        'code'      => 'string() #银行代码 ICBC',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'type'  => 'enum[rows, row, dataset]',
            'size'  => 'unsigned',
            'total' => 'unsigned',
            'data'  => 'rows[id:int,code:string,name:string,shortname:string,logo:string,updated:string,
                updated_uname:string,created_uname:string,created:string,state:set[enabled,online]] 
                #id:ID; code:银行英文简称; name:银行名称; shortname:银行简称; logo:logo; updated:更新时间; 
                updated_uname:更新人; created_uname:创建人; created:创建时间; state:集合信息, enabled:启用，online:线上支付;',
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $params =$this->request->getParams();
        $query = DB::table('bank')->where('type',1);
        $query = isset($params['name']) && !empty($params['name']) ? $query->where('name',$params['name']) : $query;
        $query = isset($params['code']) && !empty($params['code']) ? $query->where('code',$params['code']) : $query;
        $rel    = $query->get()->toArray();

        return $rel ;
    }
};
