yum update

curl -sL https://rpm.nodesource.com/setup_16.x | bash -

yum -y install nodejs

yum -y install gcc-c++ make git

node -v

npm -v

firewall-cmd --add-port 8080/tcp --permanent

firewall-cmd --reload

echo "Copy knexfile.js to root directory"

echo "Then you are ready to run: node index.js/server.js"

