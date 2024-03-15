<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/13 14:15
 */
use Logic\Admin\BaseController;
use Logic\Admin\Notice as noticeLogic;

return new class() extends BaseController
{
    const TITLE       = '查看单条公告';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id' => 'int',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id' => 'ID',
            'title' => ' 标题',
            'content' => '内容',
            'recipient' => '接收人',
            'popup_type' => '弹出类型(1：登录弹出，2：首页弹出，3：滚动公告）',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'status'=> 'enum#状态（1：发布，0：未发布',
            'admin_name'=>'发布者',
           'language_id' => 'int(required) #语言id，通过接口获取，',
        ],
    ];

    public $id;

    public function run($id=null)
    {
        $this->checkID($id);
        return (new noticeLogic($this->ci))->getNoticeById($id);

    }
};
