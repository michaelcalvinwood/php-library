#!/bin/bash
IFS=$'\n'

clear

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}

yum clean all

yum -y install epel-release

yum -y install mod_ssl yum-utils certbot-apache

certbot

cp renewLetsEncrypt.sh /etc/cron.weekly/

rm -f /etc/httpd/conf.d/ssl.conf
mv ssl.conf /etc/httpd/conf.d/ssl.conf

ln -s /etc/letsencrypt/live/$host/cert.pem /etc/$host/cert.pem
ln -s /etc/letsencrypt/live/$host/cert.pem /etc/$host/cert.pem
ln -s /etc/letsencrypt/live/$host/chain.pem /etc/$host/chain.pem
ln -s /etc/letsencrypt/live/$host/fullchain.pem /etc/$host/fullchain.pem

ln -s /etc/letsencrypt/live/$host/privkey.pem /etc/host/privkey.pem
ln -s /etc/letsencrypt/live/$host/chain.pem /etc/host/chain.pem
ln -s /etc/letsencrypt/live/$host/fullchain.pem /etc/host/fullchain.pem
ln -s /etc/letsencrypt/live/$host/privkey.pem /etc/host/privkey.pem


systemctl restart httpd
