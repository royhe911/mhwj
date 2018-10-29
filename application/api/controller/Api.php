<?php
namespace app\api\controller;

use app\common\model\NoticeModel;
use app\common\model\UserModel;

/**
 * Api-控制器
 * @author 贺强
 * @time   2018-10-26 14:12:39
 */
class Api extends \think\Controller
{
    /**
     * 获取轮播图
     * @author 贺强
     * @time   2018-10-26 14:14:50
     * @param  NoticeModel $n NoticeModel 实例
     * @return string         返回 json 串
     */
    public function get_carousel(NoticeModel $n)
    {
        $count = $this->request->post('count', 3);
        $where = ['is_delete' => 0, 'status' => 0];
        $list  = $n->getList($where, '`name`,`url`', "1,$count", "sort");
        foreach ($list as &$item) {
            if (!empty($item['url'])) {
                $item['url'] = config('WEBSITE') . $item['url'];
            }
        }
        echo json_encode(['status' => 0, 'data' => $list]);
    }

    /**
     * 用户入驻
     * @author 贺强
     * @time   2018-10-26 16:29:42
     * @param  UserModel     $u  UserModel 实例
     * @return bool              返回入驻是否成功
     */
    public function user_admission(UserModel $u)
    {
        $param = file_get_contents('php://input');
        $param = json_decode($param, true);
        if (empty($param['user'])) {
            echo json_encode(['status' => 1, 'info' => '非法参数']);exit;
        }
        $user = $param['user'];
        if (empty($user['type'])) {
            echo json_encode(['status' => 2, 'info' => '用户类型不能为空']);exit;
        }
        if (empty($user['nickname'])) {
            echo json_encode(['status' => 3, 'info' => '用户昵称不能为空']);exit;
        }
        if (empty($user['avatar'])) {
            echo json_encode(['status' => 4, 'info' => '用户头像不能为空']);exit;
        }
        if (empty($user['tape'])) {
            echo json_encode(['status' => 5, 'info' => '录音地址不能为空']);exit;
        }
        $user['addtime'] = time();
        $attr            = [];
        if (!empty($param['attr'])) {
            $attr = $param['attr'];
            foreach ($attr as $tt) {
                $regx = '/^\d+$/';
                if (!preg_match($regx, $tt['game_id']) || !preg_match($regx, $tt['curr_para']) || !preg_match($regx, $tt['play_para']) || !preg_match($regx, $tt['play_type']) || empty($tt['level_url'])) {
                    echo json_encode(['status' => 6, 'info' => '参数缺失或不合法']);exit;
                    break;
                }
            }
        }
        $res = $u->admission($user, $attr);
        if ($res !== true) {
            $msg = ['status' => $res];
            switch ($res) {
                case 7:
                    $msg['info'] = '用户基本信息入库失败';
                    break;
                case 8:
                    $msg['info'] = '陪玩师游戏段位入库失败';
                    break;
                case 9:
                    $msg['info'] = '服务器异常';
                    break;
            }
            echo json_encode($msg);exit;
        }
        echo json_encode(['status' => 0, 'info' => '入驻成功']);exit;
    }
}
