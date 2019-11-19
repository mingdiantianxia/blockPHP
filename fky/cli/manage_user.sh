#!/bin/bash
#filename:Shell自动化管理账号脚本
#author:fky
#date:2019-7-22 12:04:50

#clear
printf "
#######################################################################
#                    Shell自动化管理账号脚本                          #
#                         author:fky                                  #
#                    date:2019-7-22 12:04:50                          #
#######################################################################
"

#配色设置
echo=echo
for cmd in echo /bin/echo; do
  $cmd >/dev/null 2>&1 || continue
  if ! $cmd -e "" | grep -qE '^-e'; then
    echo=$cmd
    break
  fi
done
CSI=$($echo -e "\033[")
CEND="${CSI}0m"
CDGREEN="${CSI}32m"
CRED="${CSI}1;31m"
CGREEN="${CSI}1;32m"
CYELLOW="${CSI}1;33m"
CBLUE="${CSI}1;34m"
CMAGENTA="${CSI}1;35m"
CCYAN="${CSI}1;36m"
CSUCCESS="$CDGREEN"
CFAILURE="$CRED"
CQUESTION="$CMAGENTA"
CWARNING="$CYELLOW"
CMSG="$CCYAN"


echo "用户管理程序"
echo "${CMSG}1${CEND}.创建用户"
echo "${CMSG}2${CEND}.删除用户"
echo "${CMSG}3${CEND}.锁定用户"
echo "${CMSG}4${CEND}.解锁用户"
echo "${CMSG}5${CEND}.查看用户列表"
echo "${CMSG}6${CEND}.退出脚本"
 
read -p "请输入您的操作选择(${CMSG}1-6${CEND})：" sn
case $sn in
1)
read -p "请输入创建用户名：" nu
useradd $nu
read -p "请输入密码：" pa
echo $pa | passwd --stdin $nu #管道命令，把第一个命令的执行结果作为第二个命令的输入值
if [ $? -eq '0' ];#$? 可以获取上一个命令的退出的返回结果，一般执行成功会返回 0，失败返回 1
then
	echo "${CSUCCESS}用户已经创建成功${CEND}"
	exit
fi
 
;;
 
2)
read -p "请输入要删除用户名：" nl
userdel -r $nl
if [ $? -ne 0 ]; #不等于
then
	echo "${CWARNING}删除$nl失败${CEND}"
else
	echo "${CSUCCESS}已经删除$nl用户${CEND}"
fi
;;
 
3)
read -p "锁定用户名：" use
echo $use 
STAT=$(passwd -S $use | awk '{print $2}') #查看用户状态，并输出第二列
if [ $STAT == "PS" ];
then
	passwd -l $use 
	if [ $? -eq '0' ];then
		echo "${CSUCCESS}锁定用户成功${CEND}"
		exit
	fi
fi
if [ $STAT == "LK" ];
then
	echo "${CWARNING}已经锁定用户${CEND}"
	exit
fi
;;
 
4)
read -p "解锁用户名：" jie
echo $jie 
STAT=$(passwd -S $jie | awk '{print $2}') 
if [ $STAT == "LK" ];
then
	passwd -u $jie
	if [ $? -eq '0' ];then
		echo "${CSUCCESS}解锁用户成功${CEND}"
		exit
	fi
fi
if [ $STAT == "PS" ];
then
	echo "${CWARNING}已经解锁用户${CEND}"
	exit
fi
;;
5)
cat /etc/passwd
;;
6)
if [ $sn -eq 6 ];
then
	read -p "是否退出(${CMSG}y${CEND})" tu
	if [ $tu == y ];then
		exit 
	fi
fi
 
esac
