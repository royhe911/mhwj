<?php
namespace app\admin\controller;

use app\common\model\PrizeDistributeModel;
use app\common\model\PrizeModel;
use app\common\model\PrizeUserModel;
use app\common\model\UserModel;

/**
 * 抽奖-控制器
 * @author 贺强
 * @time   2018-12-26 17:59:24
 */
class Prize extends \think\Controller
{
    /**
     * 添加奖品
     * @author 贺强
     * @time   2018-12-26 18:23:07
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function add(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name']) || empty($param['url']) || empty($param['desc']) || empty($param['type']) || empty($param['count'])) {
                return ['status' => 3, 'info' => '非法参数'];
            }
            $param['code']    = get_millisecond();
            $param['addtime'] = time();
            $res              = $p->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time = time();
            return $this->fetch('add', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 删除奖品
     * @author 贺强
     * @time   2018-12-26 18:42:27
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function del(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->post('ids');
            $res = $p->delByWhere(['id' => ['in', $ids]]);
            if (!$res) {
                return ['status' => 4, 'info' => '删除失败'];
            }
            return ['status' => 0, 'info' => '删除成功'];
        }
    }

    /**
     * 修改奖品
     * @author 贺强
     * @time   2018-12-26 19:07:03
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function edit(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '奖品名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '奖品图片不能为空'];
            }
            $param['updatetime'] = time();
            // 修改奖品
            $res = $p->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $prize = $p->getModel(['id' => $id]);
            if (!empty($prize['url'])) {
                $prize['url1'] = config('WEBSITE') . $prize['url'];
            }
            $time = time();
            return $this->fetch('edit', ['prize' => $prize, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 修改开奖人数
     * @author 贺强
     * @time   2018-12-12 10:12:34
     * @param  PrizeModel $g PrizeModel 实例
     */
    public function editcount(PrizeModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['count'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $count = $param['count'];
            $res   = $g->modifyField('count', $count, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 修改排序
     * @author 贺强
     * @time   2018-12-29 12:30:23
     * @param  PrizeModel $g PrizeModel 实例
     */
    public function editsort(PrizeModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['sort'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $sort = $param['sort'];
            $res  = $g->modifyField('sort', $sort, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 奖品列表
     * @author 贺强
     * @time   2018-12-26 18:12:23
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function lists(PrizeModel $p)
    {
        $where    = [];
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $p->getList($where, true, "$page,$pagesize", 'sort desc');
        foreach ($list as &$item) {
            if (!empty($item['url'])) {
                $url = $item['url'];
                if (strpos($url, 'http://') === false && strpos($url, 'https://')) {
                    $item['url'] = config('WEBSITE') . $url;
                }
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
            switch ($item['status']) {
                case 0:
                    $item['status'] = '进行中';
                    break;
                default:
                    $item['status'] = '已结束';
                    break;
            }
        }
        $count = $p->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 中奖者列表
     * @author 贺强
     * @time   2018-12-26 19:28:14
     * @param  PrizeDistributeModel $pd PrizeDistributeModel 实例
     */
    public function users(PrizeDistributeModel $pd)
    {
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $pd->getList([], true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $pids = array_column($list, 'prize_id');
            $u    = new UserModel();
            $ulis = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $ulis = array_column($ulis, null, 'id');
            $p    = new PrizeModel();
            $plis = $p->getList(['id' => ['in', $pids]], ['name', 'id']);
            $plis = array_column($plis, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($item['grant_time'])) {
                    $item['grant_time'] = date('Y-m-d H:i:s', $item['grant_time']);
                }
                if ($item['is_grant']) {
                    $item['status'] = '已发放';
                } else {
                    $item['status'] = '未发放';
                }
                if (!empty($ulis[$item['uid']])) {
                    $user = $ulis[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
                if (!empty($plis[$item['prize_id']])) {
                    $item['name'] = $plis[$item['prize_id']];
                } else {
                    $item['name'] = '';
                }
            }
        }
        $count = $pd->getCount();
        $pages = ceil($count / $pagesize);
        return $this->fetch('users', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 发放奖品
     * @author 贺强
     * @time   2018-12-13 17:26:47
     * @param  PrizeDistributeModel $pd PrizeDistributeModel 实例
     */
    public function ffjp(PrizeDistributeModel $pd)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid'])) {
                return ['status' => 1, 'info' => '中奖者ID不能为空'];
            }
            if (empty($param['wx'])) {
                return ['status' => 3, 'info' => '中奖者微信不能为空'];
            }
            if (empty($param['mobile'])) {
                return ['status' => 7, 'info' => '中奖者手机不能为空'];
            }
            $param['grant_time'] = time();
            $param['is_grant']   = 1;
            // 添加发放记录
            $res = $pd->modify($param, ['id' => $param['id']]);
            if ($res) {
                return ['status' => 0, 'info' => '发放成功'];
            } else {
                return ['status' => 4, 'info' => '发放失败'];
            }
        } else {
            $id    = $this->request->get('id');
            $pd    = new PrizeDistributeModel();
            $dist  = $pd->getModel(['id' => $id]);
            $p     = new PrizeModel();
            $prize = $p->getModel(['id' => $dist['prize_id']]);
            $u     = new UserModel();
            $user  = $u->getModel(['id' => $dist['uid']]);
            $data  = ['id' => $id, 'uid' => $dist['uid'], 'prize' => $prize, 'mobile' => $user['mobile'], 'avatar' => $user['avatar']];
            return $this->fetch('ffjp', ['data' => $data]);
        }
    }

    /**
     * 参与抽奖列表
     * @author 贺强
     * @time   2018-12-28 09:52:23
     * @param  PrizeUserModel $pu PrizeUserModel 实例
     */
    public function jackpot(PrizeUserModel $pu)
    {
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $pages    = 0;
        $prize_id = $this->request->get('prize_id');
        $nickname = $this->request->get('nickname');
        $where    = [];
        if ($prize_id) {
            $where = ['prize_id' => $prize_id];
        }
        $u = new UserModel();
        if (!empty($nickname)) {
            $ulis = $u->getList(['nickname' => ['like', "%$nickname%"]], ['id']);
            $uuid = array_column($ulis, 'id');
            if ($uuid) {
                $where['uid'] = ['in', $uuid];
            }
        }
        $p     = new PrizeModel();
        $prizs = $p->getList([], ['id', 'name']);
        $prizs = array_column($prizs, 'name', 'id');
        $list  = $pu->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids  = array_column($list, 'uid');
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if ($item['is_winners'] === 1) {
                    $item['is_winners'] = '是';
                } else {
                    $item['is_winners'] = '否';
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
                if (!empty($prizs[$item['prize_id']])) {
                    $item['name'] = $prizs[$item['prize_id']];
                } else {
                    $item['name'] = '';
                }
            }
            $count = $pu->getCount($where);
            $pages = ceil($count / $pagesize);
        }
        return $this->fetch('jackpot', ['list' => $list, 'pages' => $pages, 'prizs' => $prizs, 'prize_id' => $prize_id, 'nickname' => $nickname]);
    }
}
