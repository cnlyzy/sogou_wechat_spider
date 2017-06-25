# sogou_wechat_spider
基于搜狗微信搜索的微信公众号爬虫

# 项目介绍
本项目基于ThinkPHP5.0.9核心版开发,采用了QueryList采集器。

使用本项目可以根据您自定义的关键字采集公众号信息。

如果对您有帮助,欢迎点 star ;如果有问题,请提 issue .

# 项目使用

## 基本环境
- PHP   5.6+  
- MySql 5.6+
- Redis 3+
    
## 基本配置
- 导入/sql/wechat_data.sql到数据库中,并在wd_task_keywords表中添加需要采集的公众号关键字
- 配置/application/database.php 为本机的数据库信息

## 设置代理
因为搜狗会封IP，所以需要设置代理，我用的代理是[阿布云]。
购买后把对应信息填写好，并把以下代码复制到程序curl opt中
 ```php
    CURLOPT_PROXYTYPE=> CURLPROXY_HTTP,
    CURLOPT_PROXY=> 'PROXY_URL',
    CURLOPT_PROXYAUTH=> CURLAUTH_BASIC,
    CURLOPT_PROXYUSERPWD=> 'PROXY_PASSWORD',
```
## 运行
    cd 到 public 目录 执行 ./sogou_wechat_spider.sh
    若报错，请检查是否赋予执行权限。

## 方法说明
方法|含义
---|---
- index/index/sg | 根据关键字去搜狗搜索匹配的公众号
- index/index/sg_art | 根据关键字去搜狗搜索匹配的文章
- index/index/autoStart | 根据任务量判断是否继续抓取还是休息
- index/index/setCookie | 手动设置cookie信息，设置了cookie可以抓取10页以上
- index/index/count | 统计当天总的抓取数量
- index/index/keyword_count | 统计关键字当天抓取数量

# 赞助作者
> 甲鱼说，咖啡是灵魂的饮料，买点咖啡。

<img src="https://raw.githubusercontent.com/cnlyzy/sogou_wechat_spider/master/sponsor/pay_zfb.jpg" width="250" height="350"/>
<img src="https://raw.githubusercontent.com/cnlyzy/sogou_wechat_spider/master/sponsor/pay_wx.png" width="250" height="350"/>