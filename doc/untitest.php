<?php


class Skeleton {

    protected $files = [];

    function showdir($path){
        $dh = opendir($path);//打开目录
        while(($d = readdir($dh)) != false) {
            //逐个文件读取，添加!=false条件，是为避免有文件或目录的名称为0
            if($d=='.' || $d == '..') {//判断是否为.或..，默认都会有
                continue;
            }

            $file = $path.'/'.$d;
            if (is_file($file)) {
                $this->files[] =  $file;
            }
            if(is_dir($path.'/'.$d)) {//如果为目录
                $this->showdir($path.'/'.$d);//继续读取该目录下的目录或文件
            }
        }
    }


    function createDirectory($dir) {
        if(is_dir($dir) || @mkdir($dir,0777)) { 
            //查看目录是否已经存在或尝试创建，加一个@抑制符号是因为第一次创建失败，会报一个“父目录不存在”的警告。
            // echo $dir."创建成功", PHP_EOL;  //输出创建成功的目录
        } else {
            $dirArr = explode('/',$dir); //当子目录没创建成功时，试图创建父目录，用explode()函数以'/'分隔符切割成一个数组
            array_pop($dirArr); //将数组中的最后一项（即子目录）弹出来，
            $newDir = implode('/',$dirArr); //重新组合成一个文件夹字符串
            $this->createDirectory($newDir); //试图创建父目录
            if (@mkdir($dir,0777)) {
                // echo $dir."创建成功", PHP_EOL;
            } //再次试图创建子目录,成功输出目录名
        }
    }

    function apiFetch($source, $target, $force = false) {
        $this->showdir($source);
        $template = file_get_contents(__DIR__.'/untitestApiTemplate.phl');
        foreach ($this->files as $file) {
            $targetFile = str_replace($source, $target, $file);

            $fileName = basename($targetFile);
            $fileNames = explode('.', $fileName);

            if ($fileNames[1] != 'php') {
                continue;
            }

            $targetFileName = $fileNames[0].'Test'.'.'.$fileNames[1];

            $targetFile = str_replace($fileName, $targetFileName, $targetFile);

            if (is_file($targetFile) && !$force) {
                continue;
            }

            $content = $template;
            $uri = dirname(str_replace($target, '', $targetFile));
            $uris = [];
            foreach (explode('/', $uri) as $v) {
                $uris[] = 'Test'.ucfirst($v);
            }
            $content = str_replace('{namespace}', 'Tests\ApiWWW'.join('\\', $uris), $content);
            $content = str_replace('{className}', $fileNames[0].'Test', $content);
            $content = str_replace('{method}', strtoupper($fileNames[0]), $content);
            $content = str_replace('{uri}', $uri, $content);
            $this->createDirectory(dirname($targetFile));
            file_put_contents($targetFile, $content);
        }
    }
}

$sl = new Skeleton;

$sl->apiFetch(__DIR__.'/../api.www/src/entries', __DIR__.'/../tests/api.www/src/entries', $force = true);
echo 'over'.PHP_EOL;