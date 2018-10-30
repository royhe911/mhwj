<?php
namespace app\admin\controller;

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
        $where = [];
        $param = $this->request->post();
        if (!empty($param['type'])) {
            $where['type'] = $param['type'];
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
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 用户详情
     * @author 贺强
     * @time   2018-10-29 15:46:55
     * @param  UserModel     $u  UserModel 实例
     * @param  UserAttrModel $ua UserAttrModel 实例
     */
    public function detail(UserModel $u, UserAttrModel $ua)
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
        if ($user['type'] === 2) {
            $user['type_txt'] = '陪玩师';
            $attrs            = $ua->getList(['uid' => ['in', $id]]);
            $user['attrs']    = $attrs;
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
        }
        if (!empty($user['addtime'])) {
            $user['addtime'] = date('Y-m-d H:i:s', $user['addtime']);
        }
        if (!empty($user['login_time'])) {
            $user['login_time'] = date('Y-m-d H:i:s', $user['login_time']);
        }

        return $this->fetch('detail', ['user' => $user]);
    }
}
