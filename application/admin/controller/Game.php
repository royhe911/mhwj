<?php
namespace app\admin\controller;

use app\common\model\GameConfigModel;
use app\common\model\GameModel;

/**
 * Game-控制器
 * @author 贺强
 * @time   2018-10-29 11:06:19
 */
class Game extends \think\Controller
{
    /**
     * 添加游戏
     * @author 贺强
     * @time   2018-10-29 11:26:58
     * @param  GameModel $g GameModel 实例
     */
    public function add(GameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $data  = ['addtime' => time()];
            if (empty($param['identify'])) {
                return ['status' => 1, 'info' => '游戏标识不能为空'];
            } else {
                $count = $g->getCount(['identify' => $param['identify']]);
                if ($count > 0) {
                    return ['status' => 1, 'info' => '游戏标识已存在'];
                }
                $data['identify'] = $param['identify'];
            }
            if (empty($param['name'])) {
                return ['status' => 2, 'info' => '游戏名称不能为空'];
            } else {
                $data['name'] = $param['name'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '游戏图片不能为空'];
            } else {
                $data['url'] = $param['url'];
            }
            if (empty($param['demo_url1'])) {
                return ['status' => 4, 'info' => '示例图片不能为空'];
            } else {
                $data['demo_url1'] = $param['demo_url1'];
            }
            if (empty($param['demo_url2'])) {
                return ['status' => 4, 'info' => '示例图片不能为空'];
            } else {
                $data['demo_url2'] = $param['demo_url2'];
            }
            if (empty($param['demo_url3'])) {
                return ['status' => 4, 'info' => '示例图片不能为空'];
            } else {
                $data['demo_url3'] = $param['demo_url3'];
            }
            if (empty($param['sort'])) {
                return ['status' => 4, 'info' => '排序不能为空'];
            } else {
                $data['sort'] = $param['sort'];
            }
            if (empty($param['para_id_arr'])) {
                return ['status' => 5, 'info' => '游戏段位不能为空'];
            }
            if (empty($param['para_str_arr'])) {
                return ['status' => 6, 'info' => '段位描述不能为空'];
            }
            if (count($param['para_id_arr']) !== count($param['para_str_arr'])) {
                return ['status' => 7, 'info' => '段位数量和描述数量不匹配'];
            }
            $para_data = [];
            foreach ($param['para_id_arr'] as $key => $para_id) {
                $para_data[] = ['para_id' => $para_id, 'para_str' => $param['para_str_arr'][$key]];
            }

            $res = $g->add_game($data, $para_data);
            if ($res !== true) {
                $msg = ['status' => $res];
                switch ($res) {
                    case 10:
                        $msg['info'] = '添加游戏基本信息失败';
                        break;
                    case 20:
                        $msg['info'] = '添加游戏段位信息失败';
                        break;
                    case 44:
                        $msg['info'] = '服务器异常';
                        break;
                }
                return $msg;
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time = time();
            return $this->fetch('add', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 删除游戏
     * @author 贺强
     * @time   2018-10-29 11:38:01
     * @param  GameModel $g GameModel 实例
     */
    public function del(GameModel $g)
    {
        $ids = $this->request->post('ids', 0);
        if (empty($ids) || preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        $res = $g->del_game($ids);
        if (!$res) {
            return ['status' => 4, 'info' => '删除失败'];
        }
        return ['status' => 0, 'info' => '删除成功'];
    }

    /**
     * 修改游戏
     * @author 贺强
     * @time   2018-10-29 11:41:55
     * @param  GameModel $g GameModel 实例
     */
    public function edit(GameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $data  = [];
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            } else {
                $data['id'] = $param['id'];
            }
            if (empty($param['identify'])) {
                return ['status' => 1, 'info' => '游戏标识不能为空'];
            } else {
                $count = $g->getCount(['id' => ['neq', $param['id']], 'identify' => $param['identify']]);
                if ($count > 0) {
                    return ['status' => 1, 'info' => '游戏标识已存在'];
                }
                $data['identify'] = $param['identify'];
            }
            if (empty($param['name'])) {
                return ['status' => 2, 'info' => '游戏名称不能为空'];
            } else {
                $data['name'] = $param['name'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '游戏图片不能为空'];
            } else {
                $data['url'] = $param['url'];
            }
            if (empty($param['demo_url1'])) {
                return ['status' => 3, 'info' => '例图不能为空'];
            } else {
                $data['demo_url1'] = $param['demo_url1'];
            }
            if (empty($param['demo_url2'])) {
                return ['status' => 3, 'info' => '例图不能为空'];
            } else {
                $data['demo_url2'] = $param['demo_url2'];
            }
            if (empty($param['demo_url3'])) {
                return ['status' => 3, 'info' => '例图不能为空'];
            } else {
                $data['demo_url3'] = $param['demo_url3'];
            }
            if (empty($param['para_id_arr'])) {
                return ['status' => 4, 'info' => '游戏段位不能为空'];
            }
            if (empty($param['para_str_arr'])) {
                return ['status' => 5, 'info' => '段位描述不能为空'];
            }
            if (count($param['para_id_arr']) !== count($param['para_str_arr'])) {
                return ['status' => 6, 'info' => '段位数量和描述数量不匹配'];
            }
            $para_data = [];
            foreach ($param['para_id_arr'] as $key => $para_id) {
                $para_data[] = ['id' => $param['paraids_arr'][$key], 'para_id' => $para_id, 'para_str' => $param['para_str_arr'][$key]];
            }
            $del_ids = null;
            if (!empty($param['del_ids'])) {
                $del_ids = $param['del_ids'];
            }
            $res = $g->modify_game($data, $para_data, $del_ids);
            if ($res !== true) {
                $msg = ['status' => $res];
                switch ($res) {
                    case 10:
                        $msg['info'] = '游戏基本信息修改失败';
                        break;
                    case 20:
                        $msg['info'] = '游戏段位信息修改失败';
                        break;
                    case 30:
                        $msg['info'] = '游戏段位信息增加失败';
                        break;
                    case 44:
                        $msg['info'] = '服务器异常';
                        break;
                }
                return $msg;
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $game = $g->getModel(['id' => $id]);
            if (!empty($game['url'])) {
                $game['url1'] = config('WEBSITE') . $game['url'];
            }
            $gc        = new GameConfigModel();
            $game_para = $gc->getList(['game_id' => $id], 'id,para_id,para_str', 'para_id');
            if (!empty($game_para)) {
                $game['game_para'] = $game_para;
            }
            $time = time();
            return $this->fetch('edit', ['game' => $game, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 修改游戏排序
     * @author 贺强
     * @time   2018-10-31 10:04:14
     * @param  GameModel $g GameModel 实例
     */
    public function editsort(GameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $g->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 游戏列表
     * @author 贺强
     * @time   2018-10-29 11:08:03
     * @param  GameModel $g GameModel 实例
     * @return array        返回游戏数据集
     */
    public function lists(GameModel $g)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $g->getList($where, true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['url']) && strpos($item['url'], 'https://') === false) {
                $item['url'] = config('WEBSITE') . $item['url'];
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $g->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }
}
