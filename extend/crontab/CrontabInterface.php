<?php
namespace crontab;

interface CrontabInterface
{
    /**
     * 设置一个定时任务执行时间
     * @return mixed
     */
    public function setCrontab();

    /**
     * @return mixed
     */
    public function _init();
}