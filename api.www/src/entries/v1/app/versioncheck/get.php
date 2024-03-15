<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '包版本检查';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];
    public function run()
    {
        $type = $this->request->getParam('type');
        $channel_no = $this->request->getParam('channelNo', '');
        if(empty($channel_no)){
            $channel_no = 'default';
        }

        $info = \DB::table('app_package')->select('bag_url','version','update_type')->where('type',$type)
                                ->where('status',0)
                                ->orderBy('version','desc')
                                ->first();

        $data = [
            'bag_url' => '',
            'version' => '',
            'update_type' => ''
        ];
        if(!empty($info)){
            //3月17修改  bryce要求修改
            //根据渠道号获取下载
            $data['update_type'] = $info->update_type;
            $download_info = \DB::table('channel_download')->where('is_delete',0)->where('channel_no', $channel_no)->first();
            if(!empty($download_info)){
                $data['version'] = $info->version;
                if($type == 1 && !empty($download_info->android)){
                    $data['bag_url'] = showImageUrl($download_info->android);
                }elseif($type == 2){
                    if($download_info->super_label_state == 1){
                        $data['bag_url'] = $download_info->super_label;
                    }elseif($download_info->enterprise_label_state == 1){
                        $data['bag_url'] = $download_info->enterprise_label;
                    }elseif($download_info->TF_label_state == 1){
                        $data['bag_url'] = $download_info->TF_label;
                    }
                }
            }
        }

        return $this->lang->set(0,[],$data);
    }
};
