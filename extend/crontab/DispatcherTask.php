<?php
namespace crontab;

class DispatcherTask
{
    /**
     * @var crontabInterface
     */
    protected $crontab;

    /**
     * @var
     */
    protected $cacheDir;

    /**
     * @var
     */
    protected $times;

    /**
     * @var string  缓存文件
     */
    protected $cacheFile;

    /**
     * DispatcherTask constructor.
     * @param crontabInterface $crontab
     * @param $cacheDir
     */
    public function __construct(crontabInterface $crontab, $cacheDir)
    {
        $this->crontab = $crontab;
        $this->cacheDir = $cacheDir;

        if (!$crontab->crontabName) {
            throw new \Exception('the crontabName is null!');
        }

        $this->cacheFile = $cacheDir . md5($crontab->crontabName);
    }

    /**
     * @return crontabInterface
     */
    public function getcrontab()
    {
        return $this->crontab;
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
       $timeClass   = $this->crontab->setcrontab();

       if ($this->crontab->isStop === true) {
           return null;
       }

       $times       = $timeClass->getTime();
       $this->createCacheFile($timeClass->getStartTime());

       switch((int)$timeClass->getRate()) {
           case 1: $this->times = 1 * $times; break;
           case 2: $this->times = 60 * $times; break;
           case 3: $this->times = 3600 * $times;break;
           case 4: $this->times = 86400 * $times; break;
           case 0: $this->_praseDate($timeClass->getStartTime()); break;
       }

       $this->runWithSecond();
    }

    /**
     * @return bool|int
     */
    public function getFileLastTime()
    {
        if (!file_exists($this->cacheFile)) {
            $this->createCacheFile('');
            return time();
        }
        return (int) file_get_contents($this->cacheFile);
    }

    /**
     * @param $startTime string
     * @return bool
     */
    public function createCacheFile($startTime)
    {
        $touchTime = time();
        if (!file_exists($this->cacheFile)) {
            if (date('Y-m-d H:i:s', strtotime($startTime)) == $startTime) {
                $touchTime = strtotime($startTime);
            }
            return file_put_contents($this->cacheFile, $touchTime);
        }
        return true;
    }

    /**
     * @return bool|int|void
     */
    public function updateCacheFile()
    {
        return file_put_contents($this->cacheFile, time());
    }

    /**
     * @return mixed
     */
    private function running()
    {
        $this->crontab->_init();
        $this->updateCacheFile();
    }

    /**
     * @return bool
     */
    protected function runWithSecond()
    {
        if (!$this->times) {
            return null;
        }

        $lastTime = $this->getFileLastTime();
        if (time() == $lastTime || (time() - $lastTime) % $this->times == 0) {
            $this->running();
        }
        return true;
    }

    /**
     * @param $times
     */
    protected function _praseDate($times)
    {
        $this->times = false;
        if (strtotime($times) == time()) {
            $this->running();
        }
    }
}