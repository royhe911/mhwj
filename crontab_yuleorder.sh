#!/bin/bash  
PATH=/usr/local/php/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

cd /www/wwwroot/wwwdragontangcom/   #进入项目的根目录下，保证可以运行php think的命令
step=1 #间隔的秒数，不能大于60  

for ((i = 0; i < 60; i=(i+step)));
do
    php think yuleorder
    sleep $step
done

exit 0
