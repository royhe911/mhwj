<?php
namespace app\admin\controller;

/**
 * 文件上传
 */
class Upload extends \think\Controller
{
    /**
     * 图片上传
     * @author 贺强
     * @date   2018-08-20
     * @return string     返回上传后的图片 json 数据
     */
    public function upload_img()
    {
        $timestamp = $this->request->post('timestamp');
        if (time() - $timestamp > 600) {
            return ['status' => 4, 'info' => '上传超时，请刷新页面重试'];
        }
        $root_path  = ROOT_PATH . 'public';
        $file_types = ['jpg', 'jpeg', 'gif', 'png'];
        $file       = $_FILES['Filedata'];
        if (empty($file)) {
            return json(['status' => 1, 'info' => '请选择上传文件']);
        }
        $file         = (array) $file;
        $fileinfo     = pathinfo($file['name']);
        $verify_token = md5(config('UPLOAD_SALT') . $timestamp);
        if ($verify_token !== $this->request->post('token')) {
            return json(['status' => 2, 'info' => '非法操作']);
        }
        $tmp_file   = $file['tmp_name'];
        $upload_dir = '/uploads/' . date('Y') . '/' . date('m') . '/' . date('d');
        if (!is_dir($root_path . $upload_dir)) {
            @mkdir($root_path . $upload_dir, 0755, true);
        }
        if (!in_array(strtolower($fileinfo['extension']), $file_types)) {
            return json(['status' => 3, 'info' => '文件类型不合法']);
        }
        $filename    = '/' . get_millisecond() . '.' . $fileinfo['extension'];
        $target_file = $root_path . $upload_dir . $filename;
        // 上传
        $res = move_uploaded_file($tmp_file, $target_file);
        if ($res) {
            $thumb = $this->thumb($target_file, 100, 100);
            $msg   = ['status' => 0, 'info' => '上传成功', 'path' => $upload_dir . $filename];
            if (!empty($thumb)) {
                $msg['thumb'] = $upload_dir . '/' . $thumb;
            }
            return json($msg);
        }
    }

    /**
     * 上传文件
     * @author 贺强
     * @time   2018-10-31 09:37:34
     * @return string 返回上传后路径
     */
    public function upload()
    {
        $root_path  = ROOT_PATH . 'public';
        $file_types = ['mp3', 'mp4', 'jpg', 'jpeg', 'gif', 'png'];
        $file       = $_FILES['Filedata'];
        if (empty($file)) {
            echo json_encode(['status' => 1, 'info' => '请选择上传文件']);exit;
        }
        $file     = (array) $file;
        $fileinfo = pathinfo($file['name']);
        $tmp_file = $file['tmp_name'];
        $type     = strtolower($fileinfo['extension']);
        $path     = $this->request->post('path');
        $is_pic   = false;
        $thumb_w  = 100;
        $thumb_h  = 100;
        if ($type === 'mp4') {
            $path  = 'mp4';
            $thumb = getVideoCover($tmp_file);
        } elseif ($type === 'mp3') {
            $path = 'mp3';
        } elseif (empty($path)) {
            $path = 'img';
        }
        if ($path === 'img') {
            $is_pic = true;
            if (!empty($file['thumb_w'])) {
                $thumb_w = $file['thumb_w'];
            }
            if (!empty($file['thumb_h'])) {
                $thumb_h = $file['thumb_h'];
            }
        }
        $upload_dir = "/uploads/cli/$path/" . date('Y') . '/' . date('m') . '/' . date('d');
        if (!is_dir($root_path . $upload_dir)) {
            @mkdir($root_path . $upload_dir, 0755, true);
        }
        if (!in_array($type, $file_types)) {
            echo json_encode(['status' => 3, 'info' => '文件类型不合法']);exit;
        }
        $filename    = '/' . get_millisecond() . '.' . $fileinfo['extension'];
        $target_file = $root_path . $upload_dir . $filename;
        // 上传
        $res = move_uploaded_file($tmp_file, $target_file);
        if ($res) {
            if ($is_pic) {
                $thumb = $this->thumb($target_file, $thumb_w, $thumb_h);
                $thumb = $upload_dir . '/' . $thumb;
            }
            $msg = ['status' => 0, 'info' => '上传成功', 'path' => $upload_dir . $filename];
            if (!empty($thumb)) {
                $msg['thumb'] = $thumb;
            }
            echo json_encode($msg);
        } else {
            echo json_encode(['status' => 4, 'info' => '上传失败']);
        }
        exit;
    }

    /**
     * 生成缩略图
     * @author 贺强
     * @time   2019-01-16 16:44:30
     * @param  string  $src_path 源图路径
     * @param  float   $max_w    图片宽度
     * @param  float   $max_h    图片高度
     * @param  boolean $flag     是否等比缩放
     * @param  string  $prefix   缩略图前缀
     * @return string            返回缩略图路径
     */
    public function thumb($src_path, $max_w, $max_h, $flag = true, $prefix = 'thumb_')
    {
        //获取文件的后缀
        $ext = strtolower(strrchr($src_path, '.'));
        //判断文件格式
        switch ($ext) {
            case '.jpg':
                $type = 'jpeg';
                break;
            case '.gif':
                $type = 'gif';
                break;
            case '.png':
                $type = 'png';
                break;
            default:
                return 1;
        }

        //拼接打开图片的函数
        $open_fn = 'imagecreatefrom' . $type;
        //打开源图
        $src = $open_fn($src_path);
        //创建目标图
        $dst = imagecreatetruecolor($max_w, $max_h);

        //源图的宽
        $src_w = imagesx($src);
        //源图的高
        $src_h = imagesy($src);

        //是否等比缩放
        if ($flag) {
            //求目标图片的宽高
            if ($max_w / $max_h < $src_w / $src_h) {
                //横屏图片以宽为标准
                $dst_w = $max_w;
                $dst_h = $max_w * $src_h / $src_w;
            } else {
                //竖屏图片以高为标准
                $dst_h = $max_h;
                $dst_w = $max_h * $src_w / $src_h;
            }
            //在目标图上显示的位置
            $dst_x = (int) (($max_w - $dst_w) / 2);
            $dst_y = (int) (($max_h - $dst_h) / 2);
        } else {
            //不等比
            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $max_w;
            $dst_h = $max_h;
        }

        //生成缩略图
        imagecopyresampled($dst, $src, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

        //文件名
        $filename = basename($src_path);
        //文件夹名
        $foldername = substr(dirname($src_path), 0);
        //缩略图存放路径
        $thumb_path = $foldername . '/' . $prefix . $filename;

        //把缩略图上传到指定的文件夹
        imagepng($dst, $thumb_path);
        //销毁图片资源
        imagedestroy($dst);
        imagedestroy($src);

        //返回新的缩略图的文件名
        return $prefix . $filename;
    }

    /**
     * 导出文件
     * @author 贺强
     * @date   2018-09-03
     * @param  string     $filename 要导出的文件名
     * @param  array      $titleArr excel 表头
     * @param  array      $data     要导出的数据
     */
    public function export($filename, $titleArr = [], $data = [])
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=" . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); //转码 防止乱码(比如微信昵称(乱七八糟的))
        fputcsv($fp, $titleArr);
        $index = 0;
        foreach ($data as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }

        ob_flush();
        flush();
        ob_end_clean();
    }
}
