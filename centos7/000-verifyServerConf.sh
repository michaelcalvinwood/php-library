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

v=( `cat "server.conf" `)

host=${v[0]}
domain=${v[1]}
ip=${v[2]}

chmod +x *.sh

version=( `cat "/etc/centos-release" `)

echo "$version"
echo ""

echo "host:			$host"
echo "domain:			$domain"
echo "ip:			$ip"

echo ""
echo "Is this correct?: [y/n]"

read verify

if [ $verify = "y" ]
then
        echo "you are ready to configure the server"
else
        echo "please edit server.conf"
        exit 1
fi

