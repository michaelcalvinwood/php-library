#remove Mariadb

systemctl stop mariadb

yum -y remove MariaDB-server MariaDB-client MariaDB-common

yum -y remove mariadb-server mariadb-client mariadb-common

rm -rf /var/lib/mysql

rm -f /etc/my.cnf

rm -f ~/.my.cnf

yum clean all

rm -rf /var/cache/yum

rm -rf /var/log/mariadb.log


