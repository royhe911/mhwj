<?php
namespace app\common\model;

/**
 * TUserDynamicCommentModel类
 * @author 贺强
 * @time   2019-01-22 16:34:53
 */
class TUserDynamicCommentModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_user_dynamic_comment';
    }
}