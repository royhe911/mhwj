<?php
namespace app\api\controller;

use app\common\model\PrizeModel;
use app\common\model\PrizeUserModel;

/**
 * 抽奖-控制器
 * @author 贺强
 * @time   2018-12-26 16:11:17
 */
class Prize extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2018-12-26 16:16:24
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
     * 奖品列表
     * @author 贺强
     * @time   2018-12-26 16:16:46
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function get_prizes(PrizeModel $p)
    {
        // 查询条件
        $where = [];
        // 分页参数
        $page     = 1;
        $pagesize = 10;
        $param    = $this->param;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $order = 'sort';
        $list  = $p->getList($where, ['id', 'name', 'url', 'desc', 'count'], "$page,$pagesize", $order);
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['url'])) {
                    $url = $item['url'];
                    if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                        $item['url'] = config('WEBSITE') . $url;
                    }
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);
    }

    /**
     * 参与抽奖
     * @author 贺强
     * @time   2018-12-26 16:30:17
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function join_prize(PrizeModel $p)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '参与人ID不能为空', 'data' => null];
        } elseif (empty($param['prize_id'])) {
            $msg = ['status' => 3, 'info' => '奖品ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $code = $this->get_prize_code($param['prize_id']);
        // 添加插入字段
        $param['code']    = $code;
        $param['addtime'] = time();
        // 调用插入参与抽奖方法
        $res = $p->joinPrize($param);
        if ($res !== true) {
            $msg = '参与失败';
            if ($res === 20) {
                $msg = '参与抽奖人数已满';
            }
            echo json_encode(['status' => $res, 'info' => $msg, 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '参与成功', 'data' => ['code' => $code]]);exit;
    }

    /**
     * 得到抽奖码
     * @author 贺强
     * @time   2018-12-26 16:38:41
     * @param  integer $prize_id 奖品ID
     * @param  integer $num      抽奖码位数
     * @return [type]            [description]
     */
    public function get_prize_code($prize_id, $num = 5)
    {
        $code  = get_random_str($num);
        $pu    = new PrizeUserModel();
        $count = $pu->getCount(['prize_id' => $prize_id, 'code' => $code]);
        if ($count) {
            $this->get_code($prize_id, $num);
        }
        return $code;
    }
}
