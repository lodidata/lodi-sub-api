#!/bin/bash
#set -x

# $1 就是传进来的第一个参数 $2就是第二个参数
# 路径
path=$1
echo ${path}
# ipa所在目录
currDir=$(dirname ${path})
echo "-----${currDir}"



# echo 是打印的意思
echo "---ahh---"

# 输入的包名
name=$(basename ${path} .ipa)

echo ${name}


ipa="${name}.ipa"

nipa=${name}.zip


# 打好包后输出的文件夹名字

outUpdateAppDir="OutApps"

# 获取当前目录，并切换过去


cd "${currDir}"

# 修改ipa为zip
mv ${ipa} ${nipa}

# 生成日志目录

#mkdir log

rm -rf Payload

#解压缩

unzip -o -q ${nipa} #>> log/unzipUpdateApp.log

#app父路径
appPath="${currDir}/Payload/"
echo ${appPath}

#app路径
appName=$(ls ${appPath})
echo ${appName}

#渠道文件路径
configName="Payload/${appName}/channel.txt"

#echo `ls Payload`

# 删除旧的文件，重新生成

rm -rf "${outUpdateAppDir}"

mkdir "${outUpdateAppDir}"

echo "------------------------开始打包程序------------------------"

#echo ""

# 渠道列表文件开始打包

#for line in $(cat ChannelID.txt)
#for line in $(cat Payload/${appName}/channel.txt)
for ((i=2;i<=$#;i++))
#循环数组,批量打包时需要修改的渠道Id  ("1174" "1173")

do

#echo是输出命令,可以忽略

echo "........正在打包渠道号:${!i}"

#    cd Payload/${appName}

# 设置Channel.plist

echo "-----1----${PWD}"

#修改

echo ${!i} > ${configName}



zip -rq "${outUpdateAppDir}/${!i}.ipa" "Payload"

echo "........打包已完成"

done

#修改zip为ipa
mv ${nipa} ${ipa}

echo "------------------------程序打包已结束------------------------"
