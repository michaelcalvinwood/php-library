
#!/bin/bash
IFS=$'\n'

clear

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}



yum -y remove httpd
yum -y remove mod_ssl

rm -rf /etc/httpd/*

yum -y install httpd
yum -y install mod_ssl

rm -f /etc/httpd/conf.d/welcome.conf

mv /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.BAK
cp httpd.conf /etc/httpd/conf/httpd.conf

#vi /etc/httpd/conf/httpd.conf

apachectl configtest

firewall-cmd --permanent --add-service=http

firewall-cmd --reload

systemctl enable httpd && systemctl start httpd

clear

#echo "remove virtual server name"

#read temp
	
#vi /etc/httpd/conf/httpd.conf

printf "<VirtualHost *:80>\n" > /etc/httpd/conf.d/$host.conf
printf "\tServerName $host\n" >> /etc/httpd/conf.d/$host.conf
printf "\tDocumentRoot /var/www/$host/curBuild\n" >> /etc/httpd/conf.d/$host.conf
printf "</VirtualHost>\n\n" >> /etc/httpd/conf.d/$host.conf

mkdir -p /var/www/$host/build-001

ln -s /var/www/$host/build-001 /var/www/$host/curBuild
 
systemctl restart httpd

echo "hello $host" > /var/www/$host/curBuild/index.php

chown -R apache:apache /var/www/*

firewall-cmd --permanent --add-service=https

firewall-cmd --reload
