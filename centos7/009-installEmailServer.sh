#!/bin/bash
IFS=$'\n'

clear

FILE="./server.conf"
if [ -f "$FILE" ]
then
        clear
else
        clear
        echo "Error: server.conf does not exist"
        exit 1
fi

FILE="./email.conf"
if [ -f "$FILE" ]
then
        clear
else
        clear
        echo "Error: email.conf does not exist"
        exit 1
fi

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}


version=( `cat "/etc/centos-release" `)

echo "$version"
echo ""

echo "host:				$host"
echo "domain:			$domain"
echo "ip:				$ip"

echo ""
echo "Is this correct?: [y/n]"

read verify

if [ $verify = "y" ]
then
        echo "you are ready to verify email configuration"
else
        echo "please edit server.conf"
        exit 1
fi

clear

dbPassword=$1

v=( `cat "email.conf" `)

emailDomain=${v[0]}
emailAddress=${v[1]}
emailPassword=${v[2]}
emailAlias=${v[3]}

echo "dbPassword:			$dbPassword"
echo ""

echo "emailDomain:			$emailDomain"
echo "emailAddress:			$emailAddress"
echo "emailPassword:		$emailPassword"
echo "emailAlias:			$emailAlias"

echo ""
echo "Is this correct?: [y/n]"

read verify

if [ $verify = "y" ]
then
        echo "you are ready to setup the email server"
else
        echo "please edit email.conf or start this script with dbPassword"
        exit 1
fi


yum -y install epel-release

yum -y remove postfix dovecot dovecot-mysql spamassassin clamav clamavscanner clamav-scanner-systemd clamav-data clamav-update

rm -rf /etc/dovecot/*
rm -rf /etc/postfix/*

yum -y update && yum -y install postfix dovecot dovecot-mysql spamassassin clamav clamavscanner clamav-scanner-systemd clamav-data clamav-update

firewall-cmd --add-port=25/tcp
firewall-cmd --add-port=25/tcp --permanent
firewall-cmd --add-port=587/tcp
firewall-cmd --add-port=587/tcp --permanent

firewall-cmd --add-port=143/tcp
firewall-cmd --add-port=143/tcp --permanent
firewall-cmd --add-port=993/tcp
firewall-cmd --add-port=993/tcp --permanent
firewall-cmd --add-port=110/tcp
firewall-cmd --add-port=110/tcp --permanent

groupadd -g 5000 vmail
useradd -g vmail -u 5000 vmail -d /home/vmail -m

mysql --user=root --password=$dbPassword -e "CREATE USER 'dba'@'localhost' IDENTIFIED BY '$dbPassword'"

mysql --user=root --password=$dbPassword -e "GRANT ALL PRIVILEGES ON * . * TO 'dba'@'localhost'"

mysql --user=root --password=$dbPassword -e "FLUSH PRIVILEGES"

mysql --user=root --password=$dbPassword -e "CREATE DATABASE EmailServer_db"

mysql --user=root --password=$dbPassword -e "CREATE TABLE EmailServer_db.Domains_tbl ( DomainId INT NOT NULL AUTO_INCREMENT , DomainName VARCHAR(150) NOT NULL , PRIMARY KEY (DomainId)) ENGINE = InnoDB"

mysql --user=root --password=$dbPassword -e "CREATE TABLE EmailServer_db.Users_tbl (UserId INT NOT NULL AUTO_INCREMENT, DomainId INT NOT NULL,password VARCHAR(200) NOT NULL, Email VARCHAR(200) NOT NULL, PRIMARY KEY (UserId), UNIQUE KEY Email (Email),  FOREIGN KEY (DomainId) REFERENCES EmailServer_db.Domains_tbl(DomainId) ON DELETE CASCADE) ENGINE = InnoDB"

mysql --user=root --password=$dbPassword -e "CREATE TABLE EmailServer_db.Alias_tbl (AliasId INT NOT NULL AUTO_INCREMENT, DomainId INT NOT NULL, Source varchar(200) NOT NULL, Destination varchar(200) NOT NULL, PRIMARY KEY (AliasId), FOREIGN KEY (DomainId) REFERENCES EmailServer_db.Domains_tbl(DomainId) ON DELETE CASCADE) ENGINE = InnoDB"

printf "user = dba\npassword = $dbPassword\nhosts = 127.0.0.1\ndbname = EmailServer_db\nquery = SELECT 1 FROM Domains_tbl WHERE DomainName='%%s'" > /etc/postfix/mariadb-vdomains.cf
printf "user = dba\npassword = $dbPassword\nhosts = 127.0.0.1\ndbname = EmailServer_db\nquery = SELECT 1 FROM Users_tbl WHERE Email='%%s'" > /etc/postfix/mariadb-vusers.cf
printf "user = dba\npassword = $dbPassword\nhosts = 127.0.0.1\ndbname = EmailServer_db\nquery = SELECT Destination FROM Alias_tbl WHERE Source='%%s'" > /etc/postfix/mariadb-valias.cf

chmod 640 /etc/postfix/mariadb-vdomains.cf
chmod 640 /etc/postfix/mariadb-vusers.cf
chmod 640 /etc/postfix/mariadb-valias.cf

chown root:postfix /etc/postfix/mariadb-vdomains.cf
chown root:postfix /etc/postfix/mariadb-vusers.cf
chown root:postfix /etc/postfix/mariadb-valias.cf

mysql --user=root --password=$dbPassword -e "INSERT INTO EmailServer_db.Domains_tbl (DomainName) VALUES ('$emailDomain')"

mysql --user=root --password=$dbPassword -e "INSERT INTO EmailServer_db.Users_tbl (DomainId, password, Email) VALUES (1, ENCRYPT('$emailPassword'), '$emailAddress')"

mysql --user=root --password=$dbPassword -e "INSERT INTO EmailServer_db.Alias_tbl (DomainId, Source, Destination) VALUES (1, '$emailAlias', '$emailAddress')"


postconf -e 'append_dot_mydomain = no'
postconf -e 'inet_interfaces = all'
postconf -e 'biff = no'
postconf -e 'config_directory = /etc/postfix'
postconf -e 'dovecot_destination_recipient_limit = 1'
postconf -e 'message_size_limit = 4194304'
postconf -e 'readme_directory = no'
postconf -e 'smtp_tls_session_cache_database = btree:${data_directory}/smtp_scache'
postconf -e "smtpd_banner = $host ESMTP \$mail_name (CentOS)"
postconf -e "smtpd_tls_cert_file = /etc/letsencrypt/live/$host/fullchain.pem"
postconf -e "smtpd_tls_key_file = /etc/letsencrypt/live/$host/privkey.pem"
postconf -e 'smtpd_tls_session_cache_database = btree:${data_directory}/smtpd_scache'
postconf -e 'smtpd_use_tls = yes'
postconf -e 'smtpd_sasl_type = dovecot'
postconf -e 'smtpd_sasl_path = private/auth'
postconf -e 'virtual_transport = dovecot'
postconf -e 'virtual_mailbox_domains = mysql:/etc/postfix/mariadb-vdomains.cf'
postconf -e 'virtual_mailbox_maps = mysql:/etc/postfix/mariadb-vusers.cf'
postconf -e 'virtual_alias_maps = mysql:/etc/postfix/mariadb-valias.cf'
postconf -e 'inet_interfaces = all'

mv /etc/postfix/master.cf /etc/postfix/master.cf.BAK
cp postFixMaster.cf /etc/postfix/master.cf

postfix check
systemctl restart postfix

postfix reload

clear

echo "Check domain mapping"

postmap -q $emailDomain mysql:/etc/postfix/mariadb-vdomains.cf

read temp

clear

echo "Check user mapping"

postmap -q $emailAddress mysql:/etc/postfix/mariadb-vusers.cf

read temp

clear

echo "Check alias mapping"

postmap -q $emailAlias mysql:/etc/postfix/mariadb-valias.cf

rm -f /etc/dovecot/dovecot.conf 
cp dovecot.conf /etc/dovecot/dovecot.conf

rm -f /etc/dovecot/conf.d/10-auth.conf 
cp 10-auth.conf /etc/dovecot/conf.d/10-auth.conf

rm -f /etc/dovecot/conf.d/auth-sql.conf.ext 
cp auth-sql.conf.ext /etc/dovecot/conf.d/auth-sql.conf.ext

rm -f /etc/dovecot/conf.d/10-mail.conf
cp 10-mail.conf /etc/dovecot/conf.d/10-mail.conf

rm -f /etc/dovecot/conf.d/10-master.conf
cp 10-master.conf /etc/dovecot/conf.d/10-master.conf

rm -f /etc/dovecot/conf.d/10-logging.conf
cp 10-logging.conf /etc/dovecot/conf.d/10-logging.conf 

printf "driver = mysql\nconnect = \"host=127.0.0.1 dbname=EmailServer_db user=dba password=$dbPassword\"\ndefault_pass_scheme = SHA512-CRYPT\npassword_query = SELECT Email as User, password FROM Users_tbl WHERE Email='%%u';" > /etc/dovecot/dovecot-sql.conf.ext

printf "ssl = required\nssl_cert = </etc/letsencrypt/live/$host/fullchain.pem\nssl_key = </etc/letsencrypt/live/$host/privkey.pem" > /etc/dovecot/conf.d/10-ssl.conf

touch /var/log/dovecot.log
chown vmail:dovecot /var/log/dovecot.log
chmod 660 /var/log/dovecot.log

chown -R vmail:vmail /home/vmail

chown -R vmail:dovecot /etc/dovecot
chmod -R o-rwx /etc/dovecot

systemctl enable dovecot.service
systemctl start  dovecot.service



