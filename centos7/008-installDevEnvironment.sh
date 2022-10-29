yum -y groupinstall "Development Tools"

yum -y remove openssl-devel

yum install -y openssl-devel MariaDB-shared MariaDB-devel

yum install -y MariaDB-shared
yum install -y MariaDB-devel

gcc -Wall -o client  client.c -L/usr/lib -lssl -lcrypto
gcc -Wall -o server server.c -L/usr/lib -lssl -lcrypto

firewall-cmd --permanent --add-port=5000/tcp
firewall-cmd --reload

gcc mysqlVersion.c -o version  `mysql_config --cflags --libs`

clear

./version

read temp

echo "run ./client localhost 5000 in another window"

./server 5000
