#!/bin/bash  

cd /root/swoole-src/users
find /root/swoole-src/users/ -mtime +3 -name "*.log" -exec rm -rf {} \;
