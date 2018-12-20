<?php
namespace app\api\controller;

use app\common\model\GoodsDistributeModel;
use app\common\model\GoodsModel;
use app\common\model\GoodsSkinModel;
use app\common\model\GoodsTaskInfoModel;
use app\common\model\GoodsTaskModel;
use app\common\model\MiniprogramModel;
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
        $goods_id = $param['goods_id'];
        $goods    = $g->getModel(['id' => $goods_id]);
        if (!$goods) {
            echo json_encode(['status' => 7, 'info' => '商品不存在', 'data' => null]);exit;
        }
        $uid = $param['uid'];
        // 查询用户是否已发起过砍价
        $count = $gt->getCount(['uid' => $uid, 'goods_id' => $goods_id, 'status' => ['in', [1, 8]]]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '不能重复发起砍价', 'data' => null]);exit;
        }
        $num   = mt_rand($goods['min_knife_num'], $goods['max_knife_num']);
        $task  = ['uid' => $uid, 'goods_id' => $param['goods_id'], 'knife_num' => $num, 'total_money' => $goods['price'], 'addtime' => time(), 'form_id' => $param['form_id']];
        $lucky = explode(',', $goods['lucky']);
        if (in_array($goods['count'], $lucky)) {
            $task['is_lucky']  = 1;
            $num               = mt_rand(2, 4);
            $task['knife_num'] = $num;
        }
        $data = $this->algorithm($goods['price'], $num);
        // 砍价详情
        $taskInfo = [];
        foreach ($data as $k => $item) {
            $info = ['price' => $item, 'is_baodao' => 0];
            // 是否是宝刀
            // if ($k === 8) {
            //     $info['is_baodao'] = 1;
            // }
            $taskInfo[] = $info;
        }
        $res = $gt->launch($task, $taskInfo);
        if (!is_array($res)) {
            $msg = ['status' => $res, 'info' => '发起失败', 'data' => null];
        } else {
            $gti  = new GoodsTaskInfoModel();
            $data = $gti->helpChop(['task_id' => $res['tid'], 'uid' => $uid, 'is_self' => 1]);
            $data = $data['info'];
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
        $uid       = intval($param['uid']);
        $task_id   = $param['task_id'];
        $git_where = ['uid' => $uid, 'is_self' => 0, 'is_box' => 0];
        if (!empty($param['box1'])) {
            $git_where['is_box'] = 1;
        }
        if (!empty($param['box2'])) {
            $git_where['is_box'] = 2;
        }
        // 查询任务
        $gt   = new GoodsTaskModel();
        $task = $gt->getModel(['id' => $task_id]);
        if ($uid === $task['uid']) {
            $git_where['is_self'] = 1;
        }
        $info = $gti->getModel($git_where);
        if ($info && $task) {
            $g     = new GoodsModel();
            $goods = $g->getModel(['id' => $task['goods_id']], ['deadline']);
            if ($goods && $goods['deadline'] > time()) {
                echo json_encode(['status' => 5, 'info' => '您已在活动期内砍过了', 'data' => null]);exit;
            }
        }
        $info = $gti->helpChop($param);
        if (!is_array($info)) {
            if ($info === 40 || $info === 10) {
                $msg = '砍价已完成';
            } elseif ($info === 20 || $info === 30) {
                $msg = '砍价失败，请重试';
            } elseif ($info === 44) {
                $msg = '服务器异常';
            }
            echo json_encode(['status' => $info, 'info' => $msg, 'data' => null]);exit;
        }
        $status = $info['status'];
        $data   = $info['info'];
        if (!empty($data['addtime'])) {
            $data['addtime'] = date('Y-m-d H:i:s', $data['addtime']);
        }
        if ($status === 1) {
            $u         = new UserModel();
            $user      = $u->getModel(['id' => $task['uid']], ['openid']);
            $g         = new GoodsModel();
            $goods     = $g->getModel(['id' => $task['goods_id']], ['name']);
            $remark    = '恭喜您砍价成功，请在有效期内进入小程序领取';
            $validdate = date('Y-m-d H:i:s', strtotime('+7 day'));
            $this->kj_notice($user['openid'], $task['form_id'], $goods['name'], $task['total_money'], '砍价成功', $task['knife_num'], $remark, $validdate);
        }
        echo json_encode(['status' => 0, 'info' => '砍价成功', 'data' => $data]);exit;
    }

    /**
     * 砍价成功通知
     * @author 贺强
     * @time   2018-12-19 16:13:46
     * @param  string $openid     发起砍价者OPENID
     * @param  string $form_id    FORMID
     * @param  string $goods_name 商品名称
     * @param  string $money      砍价金额
     * @param  string $status     状态描述
     * @param  int    $count      帮砍人数
     * @param  string $remark     备注
     * @param  string $validdate  领取奖品有效期限
     */
    public function kj_notice($openid, $form_id, $goods_name, $money, $status, $count, $remark, $validdate)
    {
        // 取得 access_token
        $access_token = $this->get_access_token();
        if ($access_token === false) {
            // 记录日志
        }
        // API 地址
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';
        $url .= "?access_token=$access_token";
        $data['touser'] = $openid;
        // 下单成功模板ID
        $data['template_id'] = 'udY_BaQtt4vS35evpPZ-8QDnKiBHAxfTGxTDjPBKiWI';
        $data['form_id']     = $form_id;
        $data['page']        = '/pages/welfare/welfare';
        $data['data']        = ['keyword1' => ['value' => $goods_name], 'keyword2' => ['value' => $money], 'keyword3' => ['value' => $status], 'keyword4' => ['value' => $count], 'keyword5' => ['value' => $remark], 'keyword6' => ['value' => $validdate]];
        // 处理逻辑
        $data = json_encode($data);
        $res  = $this->curl($url, $data);
        $res  = json_decode($res, true);
        if (!empty($res['errcode'])) {
            // 记录日志
        }
        return true;
    }

    /**
     * 取得 access_token
     * @author 贺强
     * @time   2018-12-19 15:54:40
     */
    public function get_access_token()
    {
        $mini    = new MiniprogramModel();
        $appid   = config('APPID_PLAYER');
        $program = $mini->getModel(['appid' => $appid]);
        // 取 secret
        $appsecret = config('APPSECRET_PLAYER');
        if (!$program) {
            $id = $mini->add(['appid' => $appid, 'appsecret' => $appsecret, 'name' => '游戏陪玩咖']);
        } else {
            $id = $program['id'];
        }
        if (!empty($program['access_token']) && $program['expires_out'] > time()) {
            return $program['access_token'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $url .= '?grant_type=client_credential';
        $url .= "&appid=$appid";
        $url .= "&secret=$appsecret";
        $data = $this->curl($url);
        if (!empty($data)) {
            $data = json_decode($data, true);
        }
        if (!empty($data['errcode'])) {
            // 写日志
        }
        $mini->modify(['access_token' => $data['access_token'], 'expires_out' => time() + $data['expires_in'] - 10], ['appid' => $appid]);
        return $data['access_token'];
    }

    /**
     * 获得砍价帮
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
        $list = $gti->getList(['task_id' => $param['task_id'], 'status' => 8, 'uid' => ['>', 0]], ['uid', 'price', 'addtime']);
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

    /**
     * 快刀榜
     * @author 贺强
     * @time   2018-12-12 11:14:23
     * @param  GoodsTaskModel $gt GoodsTaskModel 实例
     */
    public function knife_list(GoodsTaskModel $gt)
    {
        $list = $gt->getList(['status' => 8], ['id task_id', 'uid', 'total_money', 'knife_num', 'addtime'], '1,10', 'knife_num');
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

    /**
     * 获取用户发起的砍价
     * @author 贺强
     * @time   2018-12-11 18:40:58
     * @param  GoodsTaskModel $gt GoodsTaskModel 实例
     */
    public function get_kj_info(GoodsTaskModel $gt)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $task   = $gt->getModel(['uid' => $param['uid']], true, 'addtime desc');
        $g      = new GoodsModel();
        $goods  = $g->getModel(['id' => $task['goods_id']]);
        $winner = null;
        $gd     = new GoodsDistributeModel();
        $count  = $gd->getCount();
        if ($count) {
            $pagesize = 10;
            $num      = ceil($count / $pagesize);
            $page     = mt_rand(1, $num);
            $distri   = $gd->getList([], ['uid', 'skin_name'], "$page,$pagesize");
            if ($distri) {
                $uids  = array_column($distri, 'uid');
                $u     = new UserModel();
                $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
                $users = array_column($users, null, 'id');
                foreach ($distri as &$item) {
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
            $winner = $distri;
        }
        if (!$task) {
            echo json_encode(['status' => 0, 'info' => '没有砍价', 'data' => ['is_kj' => 0, 'winner' => $winner]]);exit;
        }
        $data = ['task_id' => $task['id'], 'starttime' => date('Y/m/d H:i:s'), 'endtime' => date('Y/m/d H:i:s', $task['addtime'] + 24 * 3600), 'has_cut_money' => $task['has_cut_money'], 'overplus' => $task['total_money'] - $task['has_cut_money'], 'box1' => $task['box1'], 'box2' => $task['box2'], 'status' => $task['status'], 'winner' => $winner];
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
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
     * 获取砍列表
     * @author 贺强
     * @time   2018-12-12 18:31:26
     * @param  GoodsTaskModel $gt GoodsTaskModel 实例
     */
    public function get_kj_list(GoodsTaskModel $gt)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $where = ['uid' => $param['uid']];
        $list  = $gt->getList($where, ['uid', 'total_money', 'knife_num', 'addtime', 'status'], "$page,$pagesize");
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
        // $baodao = $this->random_fload($third_min, $third_max);
        // $total -= $baodao;
        for ($i = 1; $i < $num - 3; $i++) {
            // 生成宝刀
            // if ($i === 6) {
            //     $num_arr[] = sprintf('%.2f', $baodao);
            //     continue;
            // }
            $avg  = $total / ($num - $i - 3);
            $rand = $this->random_fload($avg - $avg_num, $avg);
            if ($rand < 0.1) {
                $rand = 0.1;
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
