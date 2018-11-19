<?php
/**
 * 创建者 ： 伏伟
 * 创建时间： 2018-05-31
 * 用于处理任务时间的格式化
 */
namespace crontab;

class Times
{
    //每隔一秒执行一次
    const SECOND = 1;

    //每隔一分钟执行一次
    const MINUTE = 2;

    //每隔一小时执行一次
    const HOUR = 3;

    //每隔一天执行一次
    const DAY = 4;

    //不设置频率 指定一个未来时间执行
    const NONE = 0;

    public $rate;
    public $time = 1;
    public $startTime = '';

    /**
     * Times constructor.
     * @param $time
     */
    public function __construct($rate, $times = 1)
    {
        $this->rate = $rate;
        $this->time = $times;
    }

    /**
     * @param string $time
     * @return mixed
     */
    public function setTime($time = 1)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @param $date
     * @return $this
     */
    public function setStartTime($date)
    {
        $this->startTime = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return mixed
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }
}