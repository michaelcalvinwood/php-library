# Galera has an issue with MariaDB 10.3 therefore we are installing MariaDB 10.2 in the meantime

printf "# MariaDB 10.2 CentOS repository list\n" > /etc/yum.repos.d/MariaDB.repo
printf "# http://downloads.mariadb.org/mariadb/repositories/\n" >> /etc/yum.repos.d/MariaDB.repo
printf "[mariadb]\n" >> /etc/yum.repos.d/MariaDB.repo
printf "name = MariaDB\n" >> /etc/yum.repos.d/MariaDB.repo
printf "baseurl = http://yum.mariadb.org/10.2/centos7-amd64\n" >> /etc/yum.repos.d/MariaDB.repo
printf "gpgkey=https://yum.mariadb.org/RPM-GPG-KEY-MariaDB\n" >> /etc/yum.repos.d/MariaDB.repo
printf "gpgcheck=1\n" >> /etc/yum.repos.d/MariaDB.repo


rm -rf /var/cache/yum

yum -y install MariaDB-server MariaDB-client MariaDB-common

systemctl enable mariadb

systemctl start mariadb

systemctl status mariadb

mysql_secure_installation

firewall-cmd --permanent --add-port=3306/tcp

firewall-cmd --permanent --add-service=mysql

firewall-cmd --reload


