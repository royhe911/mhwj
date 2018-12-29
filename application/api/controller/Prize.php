<?php
namespace app\api\controller;

use app\common\model\PrizeDistributeModel;
use app\common\model\PrizeModel;
use app\common\model\PrizeUserModel;
use app\common\model\UserModel;

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
        if (empty($param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '用户ID不能为空', 'data' => null]);exit;
        }
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $uid   = $param['uid'];
        $order = 'sort desc';
        $list  = $p->getList($where, ['id', 'name', 'url', 'desc', 'count', 'status'], "$page,$pagesize", $order);
        if ($list) {
            $pu    = new PrizeUserModel();
            $plis  = $pu->getList(['uid' => $uid], ['prize_id']);
            $plis  = array_column($plis, 'prize_id');
            $pids  = array_column($list, 'id');
            // $joins = $pu->getList(['prize_id' => ['in', $pids]], ['prize_id', 'count(distinct uid) count'], '', '', 'prize_id');
            // $joins = array_column($joins, 'count', 'prize_id');
            foreach ($list as &$item) {
                if (!empty($item['url'])) {
                    $url = $item['url'];
                    if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                        $item['url'] = config('WEBSITE') . $url;
                    }
                }
                if (in_array($item['id'], $plis)) {
                    $item['is_join'] = 1;
                } else {
                    $item['is_join'] = 0;
                }
                // if (!empty($joins[$item['id']])) {
                //     $item['joins'] = $joins[$item['id']];
                // } else {
                //     $item['joins'] = 0;
                // }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);
    }

    /**
     * 奖品详情
     * @author 贺强
     * @time   2018-12-27 15:19:02
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function prize_info(PrizeModel $p)
    {
        $param = $this->param;
        if (empty($param['prize_id'])) {
            $msg = ['status' => 1, 'info' => '奖品ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $prize_id = $param['prize_id'];
        $uid      = $param['uid'];
        $prize    = $p->getModel(['id' => $prize_id], ['id', 'name', 'url', 'desc', 'count', 'status']);
        if ($prize) {
            $pu    = new PrizeUserModel();
            $count = $pu->getCount(['prize_id' => $prize_id, 'uid' => $uid]);
            if ($count) {
                $prize['is_join'] = 1;
            } else {
                $prize['is_join'] = 0;
            }
            $count = $pu->getCount(['prize_id' => $prize_id, 'uid' => $uid, 'is_winners' => 1]);
            if ($count) {
                $prize['is_winners'] = 1;
            } else {
                $prize['is_winners'] = 0;
            }
            $joins = $pu->getModel(['prize_id' => $prize_id], ['count(distinct uid) count']);
            if ($joins) {
                $prize['joins'] = $joins['count'];
            } else {
                $prize['joins'] = 0;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $prize]);exit;
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
        $prize_id = $param['prize_id'];
        $uid      = $param['uid'];
        $pu       = new PrizeUserModel();
        $count    = $pu->getCount(['prize_id' => $prize_id, 'uid' => $uid]);
        if ($count) {
            echo json_encode(['status' => 5, 'info' => '您已参与过此奖品的抽奖了', 'data' => null]);exit;
        }
        $code = $this->get_prize_code($prize_id);
        if (!empty($param['share_uid'])) {
            $param['share_code'] = $this->get_prize_code($prize_id);
        }
        // 添加插入字段
        $param['code']    = $code;
        $param['addtime'] = time();
        // 调用插入参与抽奖方法
        $res = $p->joinPrize($param);
        if ($res === true || is_array($res)) {
            echo json_encode(['status' => 0, 'info' => '参与成功', 'data' => ['code' => $code]]);exit;
        }
        $msg = '参与失败，请重试';
        if ($res === 20) {
            $msg = '参与抽奖人数已满';
        } elseif ($res === 11) {
            $msg = '抽奖已结束';
        }
        echo json_encode(['status' => $res, 'info' => $msg, 'data' => null]);exit;
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

    /**
     * 我的抽奖码
     * @author 贺强
     * @time   2018-12-27 17:04:43
     * @param  PrizeUserModel $pu PrizeUserModel 实例
     */
    public function my_prize_code(PrizeUserModel $pu)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid   = $param['uid'];
        $where = ['uid' => $uid];
        if (!empty($param['prize_id'])) {
            $where['prize_id'] = $param['prize_id'];
        }
        $list = $pu->getList($where, ['uid', 'code', 'prize_id', 'is_winners', 'addtime']);
        if ($list) {
            $p    = new PrizeModel();
            $pids = array_column($list, 'prize_id');
            $plis = $p->getList(['id' => ['in', $pids]], ['id', 'name', 'url', 'desc', 'status']);
            $plis = array_column($plis, null, 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($plis[$item['prize_id']])) {
                    $prize = $plis[$item['prize_id']];
                    // 属性赋值
                    $item['name'] = $prize['name'];
                    $url          = $prize['url'];
                    if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                        $url = config('WEBSITE') . $url;
                    }
                    $item['url']  = $url;
                    $item['desc'] = $prize['desc'];
                    if ($prize['status'] === 0) {
                        $item['is_winners'] = 2;
                    }
                } else {
                    $item['name'] = '';
                    $item['url']  = '';
                    $item['desc'] = '';
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 我的奖品
     * @author 贺强
     * @time   2018-12-27 18:47:33
     * @param  PrizeDistributeModel $pd PrizeDistributeModel 实例
     */
    public function my_prizes(PrizeDistributeModel $pd)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = $param['uid'];
        $list = $pd->getList(['uid' => $uid], ['id', 'uid', 'prize_id', 'code', 'addtime', 'grant_time']);
        if ($list) {
            $p    = new PrizeModel();
            $pids = array_column($list, 'prize_id');
            $plis = $p->getList(['id' => ['in', $pids]], ['id', 'name', 'url', 'desc']);
            $plis = array_column($plis, null, 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($item['grant_time'])) {
                    $item['grant_time'] = date('Y-m-d H:i:s', $item['grant_time']);
                }
                if (!empty($plis[$item['prize_id']])) {
                    $prize = $plis[$item['prize_id']];
                    // 属性赋值
                    $item['name'] = $prize['name'];
                    $url          = $prize['url'];
                    if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                        $url = config('WEBSITE') . $url;
                    }
                    $item['url']  = $url;
                    $item['desc'] = $prize['desc'];
                } else {
                    $item['name'] = '';
                    $item['url']  = '';
                    $item['desc'] = '';
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    public function test(PrizeModel $p)
    {
        $p->luck_draw(1);
    }
}
