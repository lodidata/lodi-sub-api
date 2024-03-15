<?php

namespace Logic\Lottery;

use OSS\OssClient;

class Package extends \Logic\Logic{


    public function batch($param){
        if(empty($param)){
            return false;
        }
        $batch_no=$param['batch_no'];
        $data=\DB::table('channel_package')->where('batch_no',$batch_no)->where('status','=',2)->get()->toArray();
        if(empty($data)){
            $this->logger->error('批次号:'.$batch_no.",暂无数据");
            return false;
        }
        $num=1;
        $count=count($data);
        foreach($data as $value){
            $result=$this->packgeUpload($value->url,$value->channel_id,$num,$count);
            if($result){
                $updateData=[
                    'download_url'=>$this->replaceImageUrl($result),
                    'status'=>1
                ];
                \DB::table('channel_package')->where('id',$value->id)->update($updateData);
            }
            $num++;
        }
        return true;
    }

    /**
     * 替换图片,返回相对地址
     * @param $content
     * @return string
     */
    public function replaceImageUrl($content)
    {
        global $app;
        $picDoman = $app->getContainer()->get('settings')['upload']['dsn'][$app->getContainer()->get('settings')['upload']['useDsn']]['domain'];
        if (empty($content)) {
            return $content;
        }

        return str_replace($picDoman, '', $content);
    }

    /**
     * @param $url string 软件包地址
     * @param $channelList array 渠道列表
     * @param $type string 1：android 2：ios
     */
    public function batchGenerateChannelPackage($url, $channelList, $type)
    {
        $num = 1;
        $count = $channelList->count();
        $date = date('Y-m-d H:i:s');
        try {

            foreach($channelList as $value){
                if ($type == 1) {
                    $result = $this->packgeUpload($url, $value->channel_no, $num, $count);
                    $appKey = 'android';
                } else {
                    $result = $this->packgeUploadIOS($url, $value->channel_no, $num, $count);
                    $appKey = 'ios';
                }

                if($result){
                    // 更新渠道表
                    \DB::table('channel_download')->where('id', $value->id)->update([
                        $appKey => $this->replaceImageUrl($result),
                        'update_time' => $date
                    ]);
                }
                $num++;
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('batchGenerateChannelPackage error msg:'.$e->getMessage());
            return false;
        }
    }

    public function packgeUploadIOS($url,$channel,$cnt=1,$count=1){
        $docPath=realpath(dirname(__FILE__).'/../../../doc/channel');
        $lib = $docPath . '/lib/ChannelPackage.sh';
        //根据链接下载ipa到临时目录
        $tempPackPath=$docPath.'/tempPackages/';
        //批量生成不在下载
        if($cnt > 1){
            $pathinfo=scandir($tempPackPath);
            $path=$tempPackPath.$pathinfo[2];
            $filename=$pathinfo[2];
        }else{
            $downfile=$this->downloadfile($url,$tempPackPath);
            $path=$downfile['path'];
            $filename=$downfile['filename'];
        }
        if (!is_file($path)) {
            $this->logger->error('下载ipa失败');
            return false;
        }
        //创建渠道ipa临时目录
        $packpath=$docPath.'/tempPackages/OutApps/';
        if (!is_dir($packpath)) {
            mkdir($packpath, 0777, true);
        }
        $sfilename = $channel.'.ipa';
        $spath = $packpath .$sfilename;

        //创建渠道apk
        $cmd = "sh {$lib} {$path} {$channel}";
        $this->logger->info('sh cmd:'.$cmd);
        exec($cmd);
        if(!is_file($spath)){
            $this->logger->error('sh ipa error spath:'.$spath);
            return false;
        }
        $obj = $this->ci->get('settings')['upload']['useDsn'];
        $settings  = $this->ci->get('settings')['upload']['dsn'][$obj];
        $ossResult=$this->$obj($settings,$spath,$sfilename);
        if(!$ossResult){
            $this->logger->error('上传ipa失败');
            return false;
        }
        //创建完成上传新包到服务器中获取链接并删除临时文件包
        if (is_file($spath)) {
            @unlink($spath);
        }
        //删除原包ipa
        if(is_file($path) && $cnt == $count){
            @unlink($path);
        }

        return $ossResult;
    }

    public function packgeUpload($url,$channel,$cnt=1,$count=1){
        $docPath=realpath(dirname(__FILE__).'/../../../doc/channel');
        $lib = $docPath . '/lib/walle-cli-all.jar';
        //根据链接下载apk到临时目录
        $tempPackPath=$docPath.'/tempPackages/';
        //批量生成不在下载
        if($cnt > 1){
            $pathinfo=scandir($tempPackPath);
            $path=$tempPackPath.$pathinfo[2];
            $filename=$pathinfo[2];
        }else{
            $downfile=$this->downloadfile($url,$tempPackPath);
            $path=$downfile['path'];
            $filename=$downfile['filename'];
        }
        $packgeName=rtrim($filename,'.apk');
        if (!is_file($path)) {
            $this->logger->error('下载apk失败,path:'.$path);
            return false;
        }
        //创建渠道apk临时目录
        $packpath=$docPath.'/package/';
        if (!is_dir($packpath)) {
            mkdir($packpath, 0777, true);
        }
        $sfilename=$packgeName.'_'.$channel.'.apk';
        $spath = $packpath .$sfilename;

        //创建渠道apk
        $cmd = "java -jar {$lib} put -c {$channel} {$path} {$spath}";
        $this->logger->info('java cmd:'.$cmd);
        exec($cmd);
        if(!is_file($spath)){
            $this->logger->error('java -jar error,spath:'.$spath);
            return false;
        }
        $obj = $this->ci->get('settings')['upload']['useDsn'];
        $settings  = $this->ci->get('settings')['upload']['dsn'][$obj];
        $ossResult=$this->$obj($settings,$spath,$sfilename);
        if(!$ossResult){
            $this->logger->error('上传apk失败,ossResult'.$ossResult);
            return false;
        }
        //创建完成上传新包到服务器中获取链接并删除临时文件包
        if (is_file($spath)) {
            @unlink($spath);
        }
        //删除原包apk
        if(is_file($path) && $cnt == $count){
            @unlink($path);
        }
        return $ossResult;
    }

    //下载远程图片 到指定目录
    public  function downloadfile($file_url, $path, $save_file_name = '')
    {
        $dir_path =$path;
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $file_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $file = curl_exec($ch);
        curl_close($ch);
        //传入保存文件的名称
        $filename = $save_file_name ?: pathinfo($file_url, PATHINFO_BASENAME);

        $resPath=$dir_path. $filename;
        $resource = fopen($resPath, 'w');

        fwrite($resource, $file);

        fclose($resource);

        return [
            'path'=>$resPath,
            'filename'=>$filename,
        ];
    }

    public function put_contents($url,$path){
        $dir_path =$path;
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }
        $filename = mb_convert_encoding(pathinfo($url, PATHINFO_BASENAME),'UTF-8','GBK');

        $resPath=$dir_path . $filename;
        file_put_contents($resPath,$url);
        return [
            'path'=>$resPath,
            'filename'=>$filename,
        ];
    }



    protected function oss($config, $file, $fileName) {
        try {
            $ossClient = new OssClient($config['accessKeyId'], $config['accessKeySecret'], $config['endpoint']);
        } catch (OssException $e) {
            $this->logger->error('上传apk包初始化失败:'.$e->getMessage());
            return false;
        }

        try {
            $object = $config['dir'].'/'.$fileName;
            $object = str_replace('//','/',$object);
            $content = file_get_contents($file);
            $ossClient->putObject($config['bucket'], $object, $content);
            return $config['domain'].'/'.$object;
        } catch (OssException $e) {
            $this->logger->error('上传apk包失败:'.$e->getMessage());
            return false;
        }
    }


    protected function awsS3($config, $file, $fileName)
    {
        //设置超时
        set_time_limit(0);
        //证书 AWS access KEY ID  和  AWS secret  access KEY 替换成自己的
        $credentials = new \Aws\Credentials\Credentials($config['accessKeyId'], $config['accessKeySecret']);
        //s3客户端
        $s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            //地区 亚太区域（新加坡）    AWS区域和终端节点： http://docs.amazonaws.cn/general/latest/gr/rande.html
            'region' => $config['region'],
            //加载证书
            'credentials' => $credentials,
            //开启bug调试
            'debug' => $config['debug']
        ]);

        //存储桶 获取AWS存储桶的名称
        //需要上传的文件
        $source = $file; //ROOT_PATH项目根目录，文件的本地路径例:D:/www/abc.jpg;
        $object = $config['dir'] . '/' . $fileName;
        //多部件上传
        $uploader = new \Aws\S3\MultipartUploader($s3, $source, [
            //存储桶
            'bucket' => $config['bucket'],
            //上传后的新地址
            'key' => $object,
            //设置访问权限  公开,不然访问不了
            'ACL' => 'public-read',
            //分段上传
            'before_initiate' => function (\Aws\Command $command) {
                // $command is a CreateMultipartUpload operation
                $command['CacheControl'] = 'max-age=3600';
            },
            'before_upload' => function (\Aws\Command $command) {
                // $command is an UploadPart operation
                $command['RequestPayer'] = 'requester';
            },
            'before_complete' => function (\Aws\Command $command) {
                // $command is a CompleteMultipartUpload operation
                $command['RequestPayer'] = 'requester';
            },
        ]);

        try {
            $result = $uploader->upload();
            //上传成功--返回上传后的地址
            /*$data = [
                'type' => '1',
                'data' => urldecode($result['ObjectURL'])
            ];*/
            return $config['domain'].'/'.$object;
        } catch (\Aws\Exception\MultipartUploadException $e) {
            $this->logger->error('上传apk包失败:'.$e->getMessage());
            return false;
        }
    }
}
