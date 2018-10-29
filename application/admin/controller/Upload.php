<?php
namespace app\admin\controller;

/**
 * 文件上传
 */
class Upload extends \think\Controller
{
    /**
     * 图片上传
     * @Author 贺强
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
            return json(['status' => 0, 'info' => '上传成功', 'path' => $upload_dir . $filename]);
        }
    }

    /**
     * 导出文件
     * @Author 贺强
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
