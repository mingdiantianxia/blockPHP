<?php
namespace fky\classs;

class FileUploader
{ // class start

    /**
     * 大文件切片上传
     * 需配合前端webuploader插件切片上传
     */
    public function VideoUpload(){
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        if ( !empty($_REQUEST[ 'debug' ]) ) {
            $random = rand(0, intval($_REQUEST[ 'debug' ]) );
            if ( $random === 0 ) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }
        // header("HTTP/1.0 500 Internal Server Error");
        // exit;
        // 5 minutes execution time
        @set_time_limit(5 * 60);
        // Uncomment this one to fake upload time
        // usleep(5000);
        // Settings
        // $targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
        $targetDir = FKY_PROJECT_PATH.'/cache/split/';
        $uploadDir = FKY_PROJECT_PATH.'/data/upload/';
        $cleanupTargetDir = true; // 开启文件缓存删除
        $maxFileAge = 60*60*24; // 文件缓存时间超过时间自动删除
        // 验证缓存目录是否存在不存在创建
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }
        // 验证缓存目录是否存在不存在创建
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir);
        }
        // Get 或 file 方式获取文件名
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }
        $oldName = $fileName;//记录文件原始名字
        $filePath = $targetDir . $fileName;
        // $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        // 删除缓存校验
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir  . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp|mp4|apk)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }
        // 打开并写入缓存文件
        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
        $index = 0;
        $done = true;
        for( $index = 0; $index < $chunks; $index++ ) {
            if ( !file_exists("{$filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }
        //文件全部上传 执行合并文件
        if ( $done ) {
            $pathInfo = pathinfo($fileName);
            $hashStr = substr(md5($pathInfo['basename']),8,16);
            $hashName = time() . $hashStr . '.' .$pathInfo['extension'];
            $uploadPath = $uploadDir .$hashName;//合并后得文件名
            if (!$out = @fopen($uploadPath, "wb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
            if ( flock($out, LOCK_EX) ) {
                for( $index = 0; $index < $chunks; $index++ ) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);

            $response = array(
                'success'=>true,
                'oldName'=>$oldName,
                //'filePaht'=>$hashName,
                //'filePaht'=>substr($uploadPath,1),
                'fileSuffixes'=>$pathInfo['extension'],
            );
            //删除源文件
            /*unlink($uploadPath);*/
            die(json_encode($response));
        }
        // Return Success JSON-RPC response
        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    }

    /**
     * 将文件切片保存
     * @param $filePath -上传文件地址
     * @return bool
     */
    public function split($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fp = fopen($filePath, "rb");
        $filesize = 10;
        $i = 0;
        $no = 1;

        $cacheDir = FKY_PROJECT_PATH.'/cache/split/';
        // 验证缓存目录是否存在不存在创建
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir);
        }

        while(!feof($fp))
        {
            $file = fread($fp, $filesize);

            $fp2 = fopen($cacheDir."socket.port".sprintf("%04d",$no).".".$i."-".($i+$filesize).".tmp", "wb");
            fwrite($fp2, $file, $filesize);
            fclose($fp2);
            $i+=$filesize+1;
            $no++;
        }

        fclose($fp);
        return true;
    }

    /**
     * 将切片文件合并
     * @param $mergeFileName -合并后得文件名
     * @return bool
     */
    public function merge($mergeFileName)
    {
        //glob返回一个包含有匹配文件/目录的数组。
        $filelist = glob(FKY_PROJECT_PATH.'/cache/split/*socket*.tmp');
        $filesize = 10;

        //print_r($filelist);
//        $mergeFileName = 'merg.zip';

        @unlink($mergeFileName);
        $fp2 = fopen($mergeFileName,"w+");
        foreach($filelist as $k => $v)
        {
            $fp = fopen($v, "rb");
            $content = fread($fp, $filesize);

            fwrite($fp2, $content, $filesize);
            unset($content);
            fclose($fp);
//            echo $k,"\n";
        }
        fclose($fp2);
        return true;
    }


} // class end
