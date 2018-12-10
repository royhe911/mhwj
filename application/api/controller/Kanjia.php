<?php
namespace app\api\controller;

/**
 * 砍价-控制器
 * @author 贺强
 * @time   2018-12-10 12:11:08
 */
class Kanjia extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2018-11-13 09:49:16
     */
    public function __construct()
    {
        $param = file_get_contents('php://input');
        $param = json_decode($param, true);
        if (empty($param['vericode'])) {
            echo json_encode(['status' => 300, 'info' => '非法参数', 'data' => null]);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数', 'data' => null]);exit;
        }
        $this->param = $param;
    }

    /**
     * 砍价算法
     * @author 贺强
     * @time   2018-12-10 11:53:29
     * @param  int   $total 需砍总价
     * @param  int   $num   需砍刀数
     * @return array        返回每刀砍的价格数组
     */
    public function algorithm($total, $num)
    {
        $num_arr   = [];
        $avg_num   = $total * 0.03;
        $third_min = $total * 0.1;
        $third_max = $total * 0.2;
        for ($i = 0; $i < 3; $i++) {
            $f_num     = $this->random_fload($third_min, $third_max);
            $f_num     = sprintf('%.2f', $f_num);
            $num_arr[] = $f_num;
            $total -= $f_num;
        }
        $baodao = $this->random_fload($third_min, $third_max);
        $total -= $baodao;
        for ($i = 1; $i < $num - 3; $i++) {
            if ($i === 6) {
                $num_arr[] = sprintf('%.2f', $baodao);
                continue;
            }
            $avg  = $total / ($num - $i - 3);
            $rand = $this->random_fload($avg - $avg_num, $avg);
            $rand = sprintf('%.2f', $rand);
            $rand = $rand < 0 ? 0 : $rand;
            $total -= $rand;
            $num_arr[] = $rand;
        }
        $num_arr[] = sprintf('%.2f', $total);
        // $this->array_sort($num_arr);
        return $num_arr;
    }

    /**
     * 获取随机浮点数
     * @author 贺强
     * @time   2018-12-10 14:56:10
     * @param  int   $min 最小值
     * @param  int   $max 最大值
     * @return float      返回生成的浮点数
     */
    private function random_fload($min, $max)
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    public function array_sort(&$arr)
    {
        $max_arr = [];
        for ($i = 0; $i < 3; $i++) {
            $max_arr[] = max($arr);
            $arr       = array_diff($arr, [max($arr)]);
        }
        $arr = array_merge($max_arr, $arr);
    }

    public function test()
    {
        $param = $this->param;
        $total = $param['total'];
        $num   = $param['num'];
        $data  = $this->algorithm($total, $num);
        while (true) {
            if (count($data) < $num) {
                $data = $this->algorithm($total, $num);
                continue;
            }
            foreach ($data as $item) {
                if ($item <= 0) {
                    $data = $this->algorithm($total, $num);
                    break;
                }
            }
            break;
        }
        print_r($data);exit;
    }
}
