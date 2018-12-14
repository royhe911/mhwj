<?php
namespace app\admin\controller;

use app\common\model\GameModel;
use app\common\model\MessageModel;
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
        $param = $this->request->get();
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
                if ($attr['status'] === 8) {
                    $attr['status_txt'] = '已审核';
                } elseif ($attr['status'] === 4) {
                    $attr['status_txt'] = '审核未通过';
                } else {
                    $attr['status_txt'] = '未审核';
                }
                $urls = explode(',', $attr['level_url']);
                // 水平截图重新赋值
                $attr['level_url'] = $urls;
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
     * @param  UserModel     $u  UserModel 实例
     * @param  UserAttrModel $ua UserAttrModel 实例
     */
    public function auditor(UserModel $u, UserAttrModel $ua)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['status'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $is_skill = 0;
            if (!empty($param['is_skill'])) {
                $is_skill = $param['is_skill'];
            }
            unset($param['is_skill']);
            if (intval($param['status']) === 8) {
                if ($is_skill) {
                    $content = '恭喜你，游戏技能审核通过';
                } else {
                    $param['type'] = 2;
                    $content       = "恭喜你，陪玩师认证审核已通过";
                }
            } else {
                if ($is_skill) {
                    $content = "技能审核未通过，原因：" . $param['reason'];
                } else {
                    $param['type'] = 1;
                    $content       = "陪玩师认证审核未通过，原因：" . $param['reason'];
                }
            }
            $m = new MessageModel();
            if ($is_skill) {
                $res = $ua->modify($param, ['id' => $param['id']]);
                if ($res) {
                    $data = ['type' => 1, 'uid' => $param['id'], 'title' => '系统消息', 'content' => $content, 'addtime' => time()];
                    $m->add($data);
                    return ['status' => 0, 'info' => '审核成功'];
                }
            } else {
                $res = $u->modify($param, ['id' => $param['id']]);
                if ($res) {
                    $data = ['type' => 1, 'uid' => $param['id'], 'title' => '系统消息', 'content' => $content, 'addtime' => time()];
                    $m->add($data);
                    return ['status' => 0, 'info' => '审核成功'];
                }
            }
            return ['status' => 4, 'info' => '审核失败'];
        }
    }

    /**
     * 设置用户
     * @author 贺强
     * @time   2018-11-05 14:40:24
     * @param  UserModel $u UserModel 实例
     */
    public function operate(UserModel $u)
    {
        $param = $this->request->post();
        if (empty($param['type'])) {
            return ['status' => 1, 'info' => '非法操作'];
        }
        if (empty($param['ids']) || !preg_match('/^0[\,\d+]+$/', $param['ids'])) {
            return ['status' => 2, 'info' => '非法参数'];
        }
        switch ($param['type']) {
            case 'del':
                $field = 'is_delete';
                $value = 1;
                $msg   = '删除';
                break;
            case 'recommend':
                $field = 'is_recommend';
                $value = 1;
                $msg   = '推荐';
                break;
            case 'unrecommend':
                $field = 'is_recommend';
                $value = 0;
                $msg   = '取消推荐';
                break;
            default:
                $field = '';
                $value = '';
                $msg   = '';
                break;
        }
        if (empty($field) || $value === '') {
            return ['status' => 3, 'info' => '非法操作'];
        }
        $res = $u->modifyField($field, $value, ['id' => ['in', $param['ids']]]);
        if (!$res) {
            return ['status' => 4, 'info' => $msg . '失败'];
        }
        return ['status' => 0, 'info' => $msg . '成功'];
    }
}
