#!/bin/bash
#回收站脚本，用于替换系统的rm删除命令

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

#TRASH_DIR="$(pwd)/trash"
#REMOVE_CMD="alias rm='sh "$(pwd)"/remove.sh'"

TRASH_DIR="/data/wwwroot/www/free_mvc/fky/cli/trash"
REMOVE_CMD="alias rm='sh /data/wwwroot/www/free_mvc/fky/cli/remove.sh'"

#创建回收站目录
if [ -d $TRASH_DIR ]; then
       echo '不干活' > /dev/null
else
       mkdir $TRASH_DIR
fi

#配置命令替换
bashrc_str=`cat ~/.bashrc`
#是否包含
if [[ $bashrc_str =~ $REMOVE_CMD ]] #增强模式匹配，不用再进行转义
then
	for i in $*; do
		if [[ $i == '-'* ]]
		then
			if [[ $i == '-uninstall' ]] #卸载配置
			then
				sed -i "s@alias rm=.*@alias rm='rm -i'@" ~/.bashrc
				echo "重启系统或使用命令使配置生效：${CMSG}source ~/.bashrc${CEND}"
				break
			fi
		else 
			STAMP=`date +%s`
			#fileName=`basename $i`

			#记录文件或目录的绝对路径
			file_real_path=`readlink -f $i`

			#使用'@_', 代替所有匹配的'/'
			fileName=${file_real_path//'/'/'@_'}
			mv $i $TRASH_DIR/$fileName.$STAMP
		fi
		
	done  
else
	sed -i "s@alias rm=.*@$REMOVE_CMD@" ~/.bashrc
	echo "重启系统或使用命令使配置生效：${CMSG}source ~/.bashrc${CEND}"
fi


