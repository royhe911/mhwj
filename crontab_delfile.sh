#!/bin/bash  

cd /root/swoole-src/users
find /root/swoole-src/users/ -mtime +10 -name "*.log" -exec rm -rf {} \;
