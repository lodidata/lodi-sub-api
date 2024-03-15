#BAG-GAME管理系统
#前台接口
<url>https://api-www.caacaya.com</url>
#管理后台接口
<url>https://api-admin.caacaya.com</url>

#安装服务
<ol>
    <li>php-7.3.4</li>
    <li>mysql-5.7.26</li>
    <li>-3.0.5x</li>
    <li>rabbitMQ</li>
    <li>composer</li>
</ol>

#PHP特殊扩展
<ol>
    <li>openssl</li>
    <li>php-soap</li>
    <li>curl</li>
    <li>redis</li>
</ol>

#database 
数据库更新SQL目录

#游戏更新
<ol>
    <li>php gameServer.php stop</li>
    <li>更新sql</li>
    <li>更新php代码</li>
    <li>redis del game_api_list</li>
    <li>redis del menu.list (横版)</li>
    <li>redis del menu:vertical:list (竖版)</li>
    <li>php gameServer.php start -d</li>
</ol>

#配置更新
<ol>
    <li>redis  del system.config.global.key</li>
    <li>正式环境 RUNMODE 设置成 product</li>
    <li>日志目录 /data/logs 要有创建、读写权限</li>
</ol>

#workman
<ol>
    <li>
    游戏拉第三方订单、补单、MQ汇总orders订单 gameServer.php 
    </li>
    <li>messageServer.php
        <ul>
            <li>回水</li>
            <li>返佣</li>
            <li>会员消息</li>
            <li>查询代付结果</li>
        </ul>
    </li>
    <li>彩票相关 prizeServer.php</li>
    <li>
        彩票生成随机结果号 InsertNumberServer.php
     </li>
</ol>
#BUKA短信平台
<ol>
<li>默认区号为0066</li>
<li>在config/setting.php中website BuKa配置中增加'telphoneCode' => '0067',</li></ol>
