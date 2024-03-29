#/bin/bash
#需要先安装iptables-services
#yum install iptables-services
# step 1: 安装必要的一些系统工具
sudo yum install -y yum-utils device-mapper-persistent-data lvm2

# Step 2: 添加软件源信息
sudo yum-config-manager --add-repo http://mirrors.aliyun.com/docker-ce/linux/centos/docker-ce.repo

# Step 3: 更新并安装Docker-CE
sudo yum makecache fast
sudo yum -y install docker-ce docker-ce-cli containerd.io

# Step 4: 开启Docker服务
sudo service docker start

# docker镜像加速
# 在dockerd后面加参数
#echo "ExecStart=/usr/bin/dockerd --registry-mirror=https://registry.docker-cn.com" >> /usr/lib/systemd/system/docker.service
#echo "ExecStart=/usr/bin/dockerd --registry-mirror=https://docker.mirrors.ustc.edu.cn" >> /lib/systemd/system/docker.service
#echo "ExecStart=/usr/bin/dockerd --registry-mirror=https://hub-mirror.c.163.com" >> /lib/systemd/system/docker.service
./set_mirror.sh https://tfl5ig5c.mirror.aliyuncs.com

#重新载入 systemd，扫描新的或有变动的单元
sudo systemctl daemon-reload

# 重启docker
#sudo systemctl restart docker
sudo service docker restart
