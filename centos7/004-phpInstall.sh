
#!/bin/bash
IFS=$'\n'

clear

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}

yum -y install epel-release yum-utils

yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm

yum-config-manager --enable remi-php72


yum -y remove php php-common php-opcache php-mcrypt php-cli php-gd php-curl php-mysqlnd
yum -y remove php-dom php-mbstring

yum clean all

yum -y install php php-common php-opcache php-mcrypt php-cli php-gd php-curl php-mysqlnd
yum -y install php-dom php-mbstring

php -v

mv /etc/php.ini /etc/php.ini.BAK
cp php.ini /etc/php.ini

systemctl restart httpd

printf "<?php\necho \"hello PHP\";\n?>" > /var/www/$host/curBuild/index.php

