yum list installed > /etc/yum/initial-packages-list

yum clean all

#yum install deltarpm -y

yum update
#yum remove btrfs-progs gssproxy hyperv-daemons-license libgudev1 NetworkManager NetworkManager-libnm NetworkManager-tui NetworkManager-wifi postfix -y

#yum update bash binutils ca-certificates centos-release chkconfig coreutils cpio cryptsetup-libs cyrus-sasl-lib curl dbus dbus-libs dracut elfutils-libelf expat filesystem gawk glib2 glibc glibc-common gmp gnupg2 grep gzip kmod kmod-libs kpartx krb5-libs libblkid libcap libdb libffi libgcc libgcrypt libmount libuuid libsemanage libstdc++ libssh2 libtasn1 libxml2 lua ncurses nspr nss nss-softokn nss-softokn-freebl nss-sysinit nss-tools nss-util openldap openssl-libs pam pcre pinentry procps-ng python python-libs python-pycurl readline rpm setup shadow-utils shared-mime-info systemd systemd-libs systemd-sysv util-linux tar tzdata xz yum yum-plugin-fastestmirror zlib -y

#yum update audit authconfig bind-libs-lite bind-license biosdevname cronie cronie-anacron crontabs device-mapper-persistent-data dhclient dmidecode dnsmasq dracut-network dracut-config-rescue e2fsprogs epel-release ethtool file fipscheck freetype gettext gnutls gobject-introspection grub2 grubby hwdata iproute iprutils iputils initscripts irqbalance kbd kernel kernel-tools kernel-tools-libs kexec-tools libcroco libdrm libgomp libnetfilter_conntrack libpciaccess lsscsi lvm2 make microcode_ctl mozjs17 nettle openssh openssh-clients openssh-server openssl os-prober parted pciutils-libs plymouth plymouth-scripts policycoreutils polkit python-gobject-base python-perf python-pyudev rdma-core rsync rsyslog selinux-policy selinux-policy-targeted sudo tuned vim-minimal virt-what xfsprogs yum-utils -y

#yum update -y mariadb-libs

#yum install net-tools -y

yum -y remove iptables
yum -y remove firewalld

yum -y install firewalld rsync

systemctl enable firewalld

systemctl start firewalld
