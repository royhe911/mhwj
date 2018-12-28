<?php
namespace app\common\crontab;

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
        $list = $pu->getJoinList([['m_prize p', 'a.prize_id=p.id'], ['m_user u', 'u.id=a.uid']], ['a.is_notice' => 0, 'p.status' => 44], ['a.id', 'a.form_id', 'p.name', 'u.openid']);
        if ($list) {
            $ids = [];
            // API 地址
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';
            $url .= "?access_token=$access_token";
            foreach ($list as $item) {
                $data['touser'] = $item['openid'];
                // 下单成功模板ID
                $data['template_id'] = '5_VTzsMzU2C5G0qOQLnTq5PWYtwQKrBwHi7ffWqWXjA';
                $data['form_id']     = $item['form_id'];
                $data['page']        = "/pages/luckDraw/luckDetail/luckDetail?id=$prize_id";
                $data['data']        = ['keyword1' => ['value' => $item['name']], 'keyword2' => ['value' => '斗汁科技 发起的抽奖正在开奖，点击查看中奖名单']];
                // 处理逻辑
                $data = json_encode($data);
                $res  = $this->curl($url, $data);
                $res  = json_decode($res, true);
                if ($res['errcode'] === 0 && $res['errmsg'] === 'ok') {
                    $ids[] = $item['id'];
                }
            }
            $pu->modifyField('is_notice', 1, ['id' => ['in', $ids]]);
        }
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
