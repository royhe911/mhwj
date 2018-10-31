<?php
namespace app\common\model;

use think\Db;

/**
 * GameModel类
 * @author 贺强
 * @time   2018-10-29 11:14:09
 */
class GameModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_game';
    }

    /**
     * 添加游戏
     * @author 贺强
     * @time   2018-10-31 15:31:43
     * @param  array  $data      游戏基本信息
     * @param  array  $para_data 游戏段位信息
     */
    public function add_game($data, $para_data)
    {
        Db::startTrans();
        try {
            $res = $this->add($data);
            if ($res == false) {
                Db::rollback();
                return 10;
            }
            foreach ($para_data as &$item) {
                $item['game_id'] = $res;
            }
            $gc  = new GameConfigModel();
            $res = $gc->addArr($para_data);
            if ($res == false) {
                Db::rollback();
                return 20;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }

    /**
     * 删除游戏
     * @author 贺强
     * @time   2018-10-31 16:03:55
     * @param  int $game_id 要删除的游戏ID
     * @return int          返回删除结果
     */
    public function del_game($game_id)
    {
        Db::startTrans();
        try {
            $gc  = new GameConfigModel();
            $res = $gc->delByWhere(['game_id' => ['in', $game_id]]);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            $res = $this->delByWhere(['id' => ['in', $game_id]]);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }

    /**
     * 修改游戏
     * @author 贺强
     * @time   2018-10-31 15:55:11
     * @param  array $data      游戏基本信息
     * @param  array $para_data 游戏段位信息
     * @param  array $del_ids   被删除的段位
     */
    public function modify_game($data, $para_data, $del_ids = null)
    {
        Db::startTrans();
        try {
            $res = $this->modify($data, ['id' => $data['id']]);
            if ($res === false) {
                Db::rollback();
                return 10;
            }
            $add_data = [];
            $gc       = new GameConfigModel();
            foreach ($para_data as $item) {
                $para = $gc->getModel(['id' => $item['id']]);
                if ($para) {
                    $res = $gc->modify($item, ['id' => $para['id']]);
                    if ($res === false) {
                        Db::rollback();
                        return 20;
                    }
                } else {
                    unset($item['id']);
                    $item['game_id'] = $data['id'];
                    $add_data        = array_merge($add_data, [$item]);
                }
            }
            if (count($add_data) > 0) {
                $res = $gc->addArr($add_data);
                if ($res === false) {
                    Db::rollback();
                    return 30;
                }
            }
            if (count($del_ids) > 0) {
                $res = $gc->delByWhere(['id' => ['in', $del_ids]]);
                if ($res === false) {
                    Db::rollback();
                    return 40;
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
