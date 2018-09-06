#!/bin/bash
ps axuf|grep start.php | grep -v 'grep'| awk '{print $2}'|xargs kill;
cd /data/www/websocket;php start.php;
