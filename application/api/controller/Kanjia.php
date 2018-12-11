<?php
namespace app\api\controller;

use app\common\model\GoodsModel;
use app\common\model\GoodsSkinModel;
use app\common\model\GoodsTaskInfoModel;
use app\common\model\GoodsTaskModel;
use app\common\model\UserModel;

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
     * 发起砍价
     * @author 贺强
     * @time   2018-12-11 09:35:13
     * @param  GoodsModel     $g  GoodsModel     实例
     * @param  GoodsTaskModel $gt GoodsTaskModel 实例
     */
    public function launch(GoodsModel $g, GoodsTaskModel $gt)
    {
        $param = $this->param;
        if (empty($param['goods_id'])) {
            $msg = ['status' => 1, 'info' => '请选择要砍价的商品', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '发起人ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $goods = $g->getModel(['id' => $param['goods_id']]);
        if (!$goods) {
            echo json_encode(['status' => 7, 'info' => '商品不存在', 'data' => null]);exit;
        }
        $uid  = $param['uid'];
        $num  = mt_rand($goods['min_knife_num'], $goods['max_knife_num']);
        $task = ['uid' => $uid, 'goods_id' => $param['goods_id'], 'knife_num' => $num, 'total_money' => $goods['price'], 'addtime' => time()];
        if ($goods['count'] === 2) {
            $task['is_lucky']  = 1;
            $num               = mt_rand(2, 4);
            $task['knife_num'] = $num;
        }
        $data = $this->algorithm($goods['price'], $num);
        // 砍价详情
        $taskInfo = [];
        foreach ($data as $k => $item) {
            $info = ['price' => $item, 'is_baodao' => 0];
            if ($k === 8) {
                $info['is_baodao'] = 1;
            }
            $taskInfo[] = $info;
        }
        $res = $gt->launch($task, $taskInfo);
        if (!is_array($res)) {
            $msg = ['status' => $res, 'info' => '发起失败', 'data' => null];
        } else {
            $gti  = new GoodsTaskInfoModel();
            $data = $gti->helpChop(['task_id' => $res['tid'], 'uid' => $uid, 'is_self' => 1]);
            $msg  = ['status' => 0, 'info' => '发起成功', 'data' => $data];
        }
        echo json_encode($msg);exit;

    }

    /**
     * 帮砍
     * @author 贺强
     * @time   2018-12-11 12:25:28
     * @param  GoodsTaskInfoModel $gti GoodsTaskInfoModel 实例
     */
    public function help_chop(GoodsTaskInfoModel $gti)
    {
        $param = $this->param;
        if (empty($param['task_id'])) {
            $msg = ['status' => 1, 'info' => '任务ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '帮砍者ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $info = $gti->getModel(['uid' => $param['uid'], 'is_self' => 0]);
        if ($info) {
            $gt   = new GoodsTaskModel();
            $task = $gt->getModel(['id' => $param['task_id']]);
            if ($task) {
                $g     = new GoodsModel();
                $goods = $g->getModel(['id' => $task['goods_id']], ['deadline']);
                if ($goods && $goods['deadline'] > time()) {
                    echo json_encode(['status' => 5, 'info' => '您已在活动期内砍过了', 'data' => null]);exit;
                }
            }
        }
        $info = $gti->helpChop($param);
        if (!is_array($info)) {
            if ($info === 40 || $info === 10) {
                $msg = '砍价已完成';
            } elseif ($info === 20 || $info === 30) {
                $msg = '砍价失败，请重试';
            }
            echo json_encode(['status' => $info, 'info' => $msg, 'data' => null]);exit;
        }
        if (!empty($info['addtime'])) {
            $info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
        }
        echo json_encode(['status' => 0, 'info' => '砍价成功', 'data' => $info]);exit;
    }

    /**
     * 获得帮砍帮
     * @author 贺强
     * @time   2018-12-11 14:18:07
     * @param  GoodsTaskInfoModel $gti GoodsTaskInfoModel 实例
     */
    public function get_help_list(GoodsTaskInfoModel $gti)
    {
        $param = $this->param;
        if (empty($param['task_id'])) {
            $msg = ['status' => 1, 'info' => '任务ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $list = $gti->getList(['task_id' => $param['task_id'], 'is_use' => 1, 'uid' => ['>', 0]], ['uid', 'price', 'addtime']);
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($users[$item['uid']])) {
                    $user = $users[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    public function get_kj_info(GoodsTaskModel $gt)
    {
        # code...
    }

    /**
     * 获取皮肤列表
     * @author 贺强
     * @time   2018-12-11 16:10:23
     * @param  GoodsSkinModel $gs GoodsSkinModel 实例
     */
    public function get_skin(GoodsSkinModel $gs)
    {
        $param = $this->param;
        $page  = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $gs->getList([], ['name', 'url', 'price'], "$page,$pagesize");
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['url']) && strpos($item['url'], 'https://') === false && strpos($item['url'], 'http://') === false) {
                    $item['url'] = config('WEBSITE') . $item['url'];
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 砍价算法
     * @author 贺强
     * @time   2018-12-10 11:53:29
     * @param  int   $total 需砍总价
     * @param  int   $num   需砍刀数
     * @return array        返回每刀砍的价格数组
     */
    private function algorithm($total, $num)
    {
        $num_arr = [];
        if ($num < 5) {
            for ($i = 1; $i < $num; $i++) {
                $rand = $this->random_fload(1, $total * 0.65);
                $rand = sprintf('%.2f', $rand);
                $total -= $rand;
                $num_arr[] = $rand;
            }
            $num_arr[] = sprintf('%.2f', $total);
            return $num_arr;
        }
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
            if ($rand <= 0) {
                $rand = 0.01;
            }
            $rand = sprintf('%.2f', $rand);
            $rand = $rand < 0 ? 0 : $rand;
            $total -= $rand;
            $num_arr[] = $rand;
        }
        $num_arr[] = sprintf('%.2f', $total);
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
