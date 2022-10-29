
#!/bin/bash

clear

host=$1

printf "<VirtualHost *:80>\n" > /etc/httpd/conf.d/$host.conf
printf "\tServerName $host\n" >> /etc/httpd/conf.d/$host.conf
printf "\tDocumentRoot /var/www/$host/curBuild\n" >> /etc/httpd/conf.d/$host.conf
printf "</VirtualHost>\n\n" >> /etc/httpd/conf.d/$host.conf

mkdir -p /var/www/$host/build-001

ln -s /var/www/$host/build-001 /var/www/$host/curBuild
 
systemctl restart httpd

echo "hello $host" > /var/www/$host/curBuild/index.php

chown -R apache:apache /var/www/*

certbot
