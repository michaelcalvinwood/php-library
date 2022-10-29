#!/bin/bash
IFS=$'\n'

clear

chmod +x *.sh

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}

clear

setenforce 0

mv /etc/selinux/config /etc/selinux/config.BAK

cp selConfig /etc/selinux/config

hostnamectl set-hostname $host

echo "$ip $host" >> /etc/hosts

mkdir /etc/$host
mkdir /etc/host

shutdown -r now
