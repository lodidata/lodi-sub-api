<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Lottery as lotteryLogic;
use lib\exception\BaseException;
return new class() extends BaseController
{
    const TITLE       = '历史开奖';
    const DESCRIPTION = '获取指定彩种的开奖结果信息，高频彩获取最近3天，低频彩最近3个月';
    
    const QUERY       = [
        'page'       => 'int()   #页码',
        'page_size'  => 'int()    #每页大小',
        'lottery_id' => 'int(required) #彩种ID，参见彩种列表接口，http://admin.las.me:8888/lottery/types?debug=1',
        'type'       => 'enum[high,low] #高频还是低频彩。见上面接口'
    ];
    
    const PARAMS      = [];
    const STATEs      = [
//        \Las\Utils\ErrorCode::UNREALIZED_OR_UNABLE => '开奖结果未出炉'
    ];
    const SCHEMAS     = [
        [
            'type'  => 'enum[rowset, row, dataset]',
            'size'  => 'unsigned',
            'total' => 'unsigned',
            'data'  => 'rows[start_time:string,lottery_name:string,lottery_number:string,catch_time:string,
                period_result:string,open_status:set[enabled,stop,valid,open]]',
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        (new BaseValidate([
            'lottery_id'  => 'require|isPositiveInteger',
//            'type'  => 'require|isPositiveInteger',
        ]))->paramsCheck('',$this->request,$this->response);
        $req     = $this->request->getParams();

        if (isset($req['type'])) {
            if ($req['type'] == 'high') {
                $from = strtotime('3 days ago');
            } else {
                $from = strtotime('3 months ago');
            }
        }

        $params = [
            'select'    => '*',
            'page'      => $req['page'],
            'page_size' => $req['page_size'],
            'lottery_type' => $req['lottery_id'],
            'date_from'    => $from ?? null

        ];
        $rs     = (new lotteryLogic($this->ci))->result($params);
        if (!$rs) {
            return [];
        }
        $data = $rs['data'];
        foreach ($data as &$datum) {
            $datum['official_time'] = $datum['official_time'] ? date('Y-m-d H:i:s', $datum['official_time']) : '';
            $datum['catch_time']    = $datum['catch_time']  ? date('Y-m-d H:i:s', $datum['catch_time']) : '';
            $datum['start_time']    = $datum['start_time']  ? date('Y-m-d H:i:s', $datum['start_time']) : '';
            $datum['end_time']    = $datum['end_time']  ? date('Y-m-d H:i:s', $datum['end_time']) : '';
            //$datum['created']       = date('Y-m-d', strtotime($datum['inserted_at']));
        }
        return $this->lang->set(0,[],$data,$rs['attributes']);

    }

};
