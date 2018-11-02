<?php
namespace app\admin\controller;

use app\common\model\GameModel;
use app\common\model\UserAttrModel;
use app\common\model\UserModel;

/**
 * User-控制器
 * @author 贺强
 * @time   2018-10-29 12:19:03
 */
class User extends \think\Controller
{
    /**
     * 用户列表
     * @author 贺强
     * @time   2018-10-29 12:21:17
     * @param  UserModel $u UserModel 实例
     * @return array            返回用户列表数据集
     */
    public function lists(UserModel $u)
    {
        $where = ['is_delete' => 0];
        $param = $this->request->post();
        if (!empty($param['type'])) {
            $where['type'] = $param['type'];
        } else {
            $param['type'] = 0;
        }
        if (!empty($param['status'])) {
            $where['status'] = $param['status'];
        } else {
            $param['status'] = 0;
        }
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $u->getList($where, true, "$page,$pagesize");
        foreach ($list as &$item) {
            if ($item['type'] === 1) {
                $item['type_txt'] = '玩家';
            } elseif ($item['type'] === 2) {
                $item['type_txt'] = '陪玩师';
            } else {
                $item['type_txt'] = '';
            }
            if ($item['status'] === 8) {
                $item['status_txt'] = '已审核';
            } elseif ($item['status'] === 1) {
                $item['status_txt'] = '待审核';
            } elseif ($item['status'] === 4) {
                $item['status_txt'] = '审核不通过';
            } else {
                $item['status_txt'] = '普通玩家';
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
            if ($item['sex'] === 1) {
                $item['sex'] = '男';
            } elseif ($item['sex'] === 2) {
                $item['sex'] = '女';
            } else {
                $item['sex'] = '保密';
            }
        }
        $count = $u->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages, 'param' => $param]);
    }

    /**
     * 用户详情
     * @author 贺强
     * @time   2018-10-29 15:46:55
     * @param  UserModel     $u  UserModel 实例
     */
    public function detail(UserModel $u)
    {
        $id = $this->request->get('id');
        if (!preg_match('/^\d+$/', $id)) {
            echo "非法参数";exit;
        }
        $user = $u->getModel(['id' => $id]);
        if (empty($user)) {
            echo "用户不存在";exit;
        }
        $user['type_txt'] = '玩家';
        if ($user['type'] === 2 || $user['status'] === 1 || $user['status'] === 8) {
            $user['type_txt'] = '陪玩师';
            $ua               = new UserAttrModel();
            $attrs            = $ua->getList(['uid' => ['in', $id]]);
            $g                = new GameModel();
            $games            = $g->getList(['is_delete' => 0], 'id,`name`,`url`');
            $games            = array_column($games, null, 'id');
            foreach ($attrs as &$attr) {
                if (!empty($games[$attr['game_id']])) {
                    $attr['game_name'] = $games[$attr['game_id']]['name'];
                } else {
                    $attr['game_name'] = '';
                }
                if ($attr['play_type'] === 1) {
                    $attr['play_type'] = '实力上分';
                } elseif ($attr['play_type'] === 2) {
                    $attr['play_type'] = '娱乐陪玩';
                }
            }
            // print_r($attrs);exit;
            $user['attrs'] = $attrs;
        }
        if ($user['sex'] === 1) {
            $user['sex'] = '男';
        } elseif ($user['sex'] === 3) {
            $user['sex'] = '女';
        } else {
            $user['sex'] = '保密';
        }
        if ($user['status'] === 0) {
            $user['status_txt'] = '正常';
        } elseif ($user['status'] === 1) {
            $user['status_txt'] = '待审核';
        } elseif ($user['status'] === 4) {
            $user['status_txt'] = '审核不通过';
        } else {
            $user['status_txt'] = '';
        }
        if (!empty($user['addtime'])) {
            $user['addtime'] = date('Y-m-d H:i:s', $user['addtime']);
        }
        if (!empty($user['login_time'])) {
            $user['login_time'] = date('Y-m-d H:i:s', $user['login_time']);
        }

        return $this->fetch('detail', ['user' => $user]);
    }

    /**
     * 用户审核
     * @author 贺强
     * @time   2018-11-01 17:27:55
     * @param  UserModel $u UserModel 实例
     */
    public function auditor(UserModel $u)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['status'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            if (intval($param['status']) === 8) {
                $param['type'] = 2;
            } else {
                $param['type'] = 1;
            }
            $res = $u->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '审核失败'];
            }
            return ['status' => 0, 'info' => '审核成功'];
        }
    }
}
