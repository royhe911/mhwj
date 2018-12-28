<?php
namespace app\common\crontab;

use app\common\model\MiniprogramModel;
use app\common\model\PrizeUserModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 抽奖结果定时任务类
 * @author 贺强
 * @time   2018-12-28 16:29:53
 */
class Prize extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-28 16:29:56
     */
    protected function configure()
    {
        $this->setName('prize')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-28 16:30:02
     */
    protected function execute(Input $input, Output $output)
    {
        $pu   = new PrizeUserModel();
        $list = $pu->getJoinList([['m_prize p', 'a.prize_id=p.id'], ['m_user u', 'u.id=a.uid']], ['a.is_notice' => 0, 'p.status' => 44], ['a.id', 'a.prize_id', 'a.uid', 'a.form_id', 'p.name', 'u.openid']);
        if ($list) {
            $ids          = [];
            $access_token = $this->get_access_token();
            // API 地址
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';
            $url .= "?access_token=$access_token";
            $item           = $list[0];
            $data['touser'] = $item['openid'];
            // 下单成功模板ID
            $data['template_id'] = '5_VTzsMzU2C5G0qOQLnTq5PWYtwQKrBwHi7ffWqWXjA';
            $data['form_id']     = $item['form_id'];
            $data['page']        = "/pages/luckDraw/luckDetail/luckDetail?id={$item['prize_id']}";
            $data['data']        = ['keyword1' => ['value' => $item['name']], 'keyword2' => ['value' => '斗汁科技 发起的抽奖正在开奖，点击查看中奖名单']];
            // 处理逻辑
            $data = json_encode($data);
            $res  = $this->curl($url, $data);
            $res  = json_decode($res, true);
            if ($res['errcode'] === 0 && $res['errmsg'] === 'ok') {
                $pu->modifyField('is_notice', 1, ['prize_id' => $item['prize_id'], 'uid' => $item['uid']]);
            }
        }
    }

    /**
     * 取得 access_token
     * @author 贺强
     * @time   2018-12-27 11:36:27
     * @param  boolean $is_master 是否是陪玩端
     */
    public function get_access_token()
    {
        $mini = new MiniprogramModel();
        // 取得 appid
        $appid = 'wxe6f37de8e1e3225e';
        // 取 secret
        $appsecret = '357566bea005201ce062acaabd4a58e9';
        $program   = $mini->getModel(['appid' => $appid]);
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
            return false;
        }
        $mini->modify(['access_token' => $data['access_token'], 'expires_out' => time() + $data['expires_in'] - 10], ['appid' => $appid]);
        return $data['access_token'];
    }

    /**
     * URL 请求
     * @author 贺强
     * @time   2018-10-30 12:13:06
     * @param  string  $url     请求地址
     * @param  string  $post    POST 数据
     * @param  boolean $is_json 是否为 json 参数
     * @param  string  $charset 编码方式，默认utf8
     * @return object           返回请求返回的数据
     */
    public function curl($url, $post = '', $is_json = true, $charset = 'utf-8')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($is_json) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($post),
            )
            );
        }
        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }
}
