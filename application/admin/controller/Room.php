<?php
namespace app\admin\controller;

use app\common\model\GameModel;
use app\common\model\RoomModel;
use app\common\model\RoomNoticeModel;
use app\common\model\RoomSetModel;
use app\common\model\UserModel;

/**
 * Room-控制器
 * @author 贺强
 * @time   2018-11-06 10:01:29
 */
class Room extends \think\Controller
{
    /**
     * 设置房间状态
     * @author 贺强
     * @time   2018-11-06 11:18:52
     * @param  RoomModel $r RoomModel 实例
     */
    public function operate(RoomModel $r)
    {
        $param = $this->request->post();
        if (empty($param['ids']) || !preg_match('/^0[\,\d+]+$/', $param['ids']) || empty($param['type'])) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        if ($param['type'] === 'disable') {
            $field = 'status';
            $value = 44;
        } elseif ($param['type'] === 'relieve') {
            $field = 'status';
            $value = 0;
        }
        $res = $r->modifyField($field, $value, ['id' => ['in', $param['ids']]]);
        if (!$res) {
            return ['status' => 4, 'info' => '操作失败'];
        }
        return ['status' => 0, 'info' => '操作成功'];
    }

    /**
     * 房间列表
     * @author 贺强
     * @time   2018-11-06 10:04:53
     * @param  RoomModel $r RoomModel 实例
     */
    public function lists(RoomModel $r)
    {
        $where = ['is_delete' => 0];
        $param = $this->request->get();
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        if (!empty($param['status'])) {
            if (intval($param['status']) === 1) {
                $where['status'] = ['in', [0, 1]];
            } else {
                $where['status'] = $param['status'];
            }
        } else {
            $param['status'] = '';
        }
        $list = $r->getList($where, true, "$page,$pagesize", 'status');
        if ($list) {
            $uids     = array_column($list, 'uid');
            $game_ids = array_column($list, 'game_id');
            $u        = new UserModel();
            $users    = $u->getList(['id' => ['in', $uids]], 'id,nickname');
            $users    = array_column($users, 'nickname', 'id');
            $g        = new GameModel();
            $games    = $g->getList(['id' => ['in', $game_ids]], 'id,name');
            $games    = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
                if (!empty($games[$item['game_id']])) {
                    $item['game_name'] = $games[$item['game_id']];
                } else {
                    $item['game_name'] = '';
                }
                if ($item['type'] === 1) {
                    $item['type'] = '实力上分';
                } else {
                    $item['type'] = '娱乐陪玩';
                }
                if ($item['region'] === 1) {
                    $item['region'] = 'QQ';
                } else {
                    $item['region'] = '微信';
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                switch ($item['status']) {
                    case 0:
                    case 1:
                        $item['status_txt'] = '待进人';
                        break;
                    case 5:
                        $item['status_txt'] = '待玩家支付';
                        break;
                    case 8:
                        $item['status_txt'] = '正在游戏';
                        break;
                    case 44:
                        $item['status_txt'] = '被禁用';
                        break;
                    default:
                        $item['status_txt'] = '';
                        break;
                }
            }
        }
        $count = $r->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages, 'param' => $param]);
    }

    /**
     * 设置活动开启结束时间
     * @author 贺强
     * @time   2018-12-20 10:42:26
     */
    public function set_limit()
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $rs    = new RoomSetModel();
            if (!empty($param['start_time'])) {
                $ros = $rs->getModel(['identity' => 'start_time']);
                if ($ros) {
                    $rs->modifyField('content', $param['start_time'], ['identity' => 'start_time']);
                } else {
                    $rs->add(['identity' => 'start_time', 'content' => $param['start_time']]);
                }
            }
            if (!empty($param['end_time'])) {
                $ros = $rs->getModel(['identity' => 'end_time']);
                if ($ros) {
                    $rs->modifyField('content', $param['end_time'], ['identity' => 'end_time']);
                } else {
                    $rs->add(['identity' => 'end_time', 'content' => $param['end_time']]);
                }
            }
            return ['status' => 0, 'info' => '设置成功'];
        } else {
            $rs    = new RoomSetModel();
            $list  = $rs->getList();
            $start = '';
            $end   = '';
            foreach ($list as $item) {
                if ($item['identity'] === 'start_time') {
                    $start = $item['content'];
                }
                if ($item['identity'] === 'end_time') {
                    $end = $item['content'];
                }
            }
            return $this->fetch('setlimit', ['start' => $start, 'end' => $end]);
        }
    }

    /**
     * 房管
     * @author 贺强
     * @time   2019-01-06 10:29:25]
     */
    public function house(RoomNoticeModel $rn)
    {
        $time = strtotime(date('Y-m-d H:i:s', strtotime('-12 hour')));
        $list = $rn->getList(['addtime' => ['gt', $time]], ['msg'], null, '', 'addtime desc');
        $str  = '';
        foreach ($list as $item) {
            if ($str === '') {
                $str = $item['msg'];
            } else {
                $str .= "\n{$item['msg']}";
            }
        }
        return $this->fetch('house', ['str' => $str]);
    }
}
