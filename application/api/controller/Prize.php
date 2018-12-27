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
        $pu    = new PrizeUserModel();
        $plis  = $pu->getList(['uid' => $uid], ['prize_id']);
        $plis  = array_column($plis, 'prize_id');
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
                if (in_array($item['id'], $plis)) {
                    $item['is_join'] = 1;
                } else {
                    $item['is_join'] = 0;
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
        $prize_id = $param['prize_id'];
        $uid      = $param['uid'];
        $pu       = new PrizeUserModel();
        $count    = $pu->getCount(['prize_id' => $prize_id, 'uid' => $uid]);
        if ($count) {
            echo json_encode(['status' => 5, 'info' => '您已参与过此奖品的抽奖了', 'data' => null]);exit;
        }
        $code = $this->get_prize_code($prize_id);
        // 添加插入字段
        $param['code']    = $code;
        $param['addtime'] = time();
        // 调用插入参与抽奖方法
        $res = $p->joinPrize($param);
        if ($res === true || is_array($res)) {
            if (is_array($res)) {
                $this->luck_notice($res);
            }
            echo json_encode(['status' => 0, 'info' => '参与成功', 'data' => ['code' => $code]]);exit;
        }
        $msg = '参与失败';
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
     * 发送抽奖结果通知
     * @author 贺强
     * @time   2018-12-27 11:32:45
     * @param  array $param 通知参数
     */
    public function luck_notice($param)
    {
        // 取得 access_token
        $access_token = $this->get_access_token();
        if ($access_token === false) {
            // 记录日志
        }
        // API 地址
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';
        $url .= "?access_token=$access_token";
        $data['touser'] = $param['openid'];
        // 下单成功模板ID
        $data['template_id'] = '5_VTzsMzU2C5G0qOQLnTq5PWYtwQKrBwHi7ffWqWXjA';
        $data['form_id']     = $param['form_id'];
        $data['page']        = '/pages/welfare/welfare';
        $data['data']        = ['keyword1' => ['value' => $param['prize_name']], 'keyword2' => ['value' => '恭喜您被抽中，中奖码为：' . $param['code']]];
        // 处理逻辑
        $data = json_encode($data);
        $res  = $this->curl($url, $data);
        $res  = json_decode($res, true);
        if (!empty($res['errcode'])) {
            // 记录日志
        }
        return true;
    }
}
