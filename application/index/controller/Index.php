<?php

namespace app\index\controller;

use QL\QueryList;
use think\Db;
use think\Request;

class Index
{
    /**
     * 搜狗公众号采集
     */
    public function sg()
    {
        //外层控制程序运行状态
        $status = cache('sogou_task_status');
        if ($status == 'start') {
            $page = cache('sogou_pages');
            if (cache('is_cookie_invalid')) {
                if ($page > 5) {
                    $this->getKeyword();
                    $page = 0;
                    cache('sogou_pages', 0, 60 * 60 * 24);
                }
            } else {
                if ($page > 15) {
                    $this->getKeyword();
                    $page = 0;
                    cache('sogou_pages', 0, 60 * 60 * 24);
                }
            }
            if (empty(cache('sogou_keyword'))) {
                $this->getKeyword();
            }
            $sogo_query = cache('sogou_keyword');
            for ($i = 1; $i <= 5; $i++) {
                $c_page = $page + $i;
                $urls[] = "http://weixin.sogou.com/weixin?query={$sogo_query}&_sug_type_=&s_from=input&_sug_=n&type=1&page={$c_page}&ie=utf8";
            }

            $cookie = cache('sogou_Cookie');
            QueryList::run('Multi', [
                'list' => $urls,
                'curl' => [
                    'opt' => array(
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_USERAGENT => $this->UA(),
                        CURLOPT_COOKIE => $cookie,
                    ),
                    'maxThread' => 5,
                    'maxTry' => 2
                ],
                'success' => function ($a) {
                    $reg = array(
                        'article_url' => array('dl:nth-child(4) > dd > a', 'href'),//文章链接
                    );
                    $ql = QueryList::Query($a['content'], $reg);
                    $data = $ql->getData();
                    $getHtml = $a['content'];
                    $pos = mb_strpos($getHtml, '当前只显示100条结果');//cookie失效
                    if ($pos != false) {
                        cache('is_cookie_invalid', 1, 60 * 60 * 24 * 30);
                        echo date('Y-m-d H:i:s', time()) . " cookie 失效,请及时更新. \r\n";
                        die();
                    }
                    $c_code = mb_strpos($getHtml, '为确认本次访问为正常用户行为');//出现验证码
                    if ($c_code != false) {
                        echo date('Y-m-d H:i:s', time()) . " 出现验证码了\r\n";
                        die();
                    }
//                dump($getHtml);
                    global $all_url;
                    $article_url = array_column($data, 'article_url');//转一维数组
                    $all_url[] = $article_url;
                }
            ]);
            $arrUrl = $GLOBALS['all_url'];
//        dump($arrUrl);
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            foreach ($arrUrl as $value) {
                foreach ($value as $v) {
                    $urlInfo = [
                        'k' => $sogo_query,
                        'u' => $v,
                        'f' => 'mp'
                    ];
                    $jsonUrlInfo = json_encode($urlInfo);
                    try {
                        $redis->lPush('sogou_task_url', $jsonUrlInfo);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
            $sogou_mp_page_count = cache('sogou_mp_page_count');
            cache('sogou_mp_page_count', $sogou_mp_page_count + 5, 60 * 60 * 25);
            cache('sogou_pages', $c_page, 60 * 60 * 24);
            echo date('Y-m-d H:i:s', time()) . " mp ok \r\n";
        } else {
            echo date('Y-m-d H:i:s', time()) . " mp stop:task to much\r\n";
            $this->mps();
        }
    }

    /**
     * 采集搜狗文章任务
     */
    public function sg_art()
    {
        //外层控制程序运行状态
        $status = cache('sogou_task_status');
        if ($status == 'start') {
            $page = cache('sogou_art_pages');
            if (cache('is_cookie_invalid')) {
                if ($page > 5) {
                    $this->getArtKeyword();
                    $this->getArtPage(cache('sogou_art_keyword'));
                    $page = 0;
                    cache('sogou_art_pages', 0, 60 * 60 * 24);
                }
            } else {
                $keyword_page = cache('sogou_art_keyword_pages') - 5;
                if ($page > $keyword_page) {
                    $this->getArtKeyword();
                    $this->getArtPage(cache('sogou_art_keyword'));
                    $page = 0;
                    cache('sogou_art_pages', 0, 60 * 60 * 24);
                }
            }
            if (empty(cache('sogou_art_keyword'))) {
                $this->getArtKeyword();
                $this->getArtPage(cache('sogou_art_keyword'));
            }
            $sogo_query = cache('sogou_art_keyword');
            for ($i = 1; $i <= 5; $i++) {
                $c_page = $page + $i;
                $urls[] = "http://weixin.sogou.com/weixin?query={$sogo_query}&_sug_type_=&s_from=input&_sug_=n&type=2&page={$c_page}&ie=utf8";
            }

            $cookie = cache('sogou_Cookie');
            QueryList::run('Multi', [
                'list' => $urls,
                'curl' => [
                    'opt' => array(
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_USERAGENT => $this->UA(),
                        CURLOPT_COOKIE => $cookie,
                    ),
                    'maxThread' => 5,
                    'maxTry' => 2
                ],
                'success' => function ($a) {
                    $reg = array(
                        'article_url' => array('li > div.txt-box > h3>a', 'href'),//文章链接
                    );
                    $ql = QueryList::Query($a['content'], $reg);
                    $data = $ql->getData();
                    $getHtml = $a['content'];
                    $pos = mb_strpos($getHtml, '当前只显示100条结果');
                    if ($pos != false) {
                        cache('is_cookie_invalid', 1, 60 * 60 * 24 * 30);
                        echo date('Y-m-d H:i:s', time()) . " cookie 失效,请及时更新. \r\n";
                        die();
                    }
                    $c_code = mb_strpos($getHtml, '为确认本次访问为正常用户行为');//出现验证码
                    if ($c_code != false) {
                        echo date('Y-m-d H:i:s', time()) . " 出现验证码了\r\n";
                        die();
                    }
//                dump($getHtml);
                    global $all_art_url;
                    $article_url = array_column($data, 'article_url');//转一维数组
                    $all_art_url[] = $article_url;
                }
            ]);
            $arrUrl = $GLOBALS['all_art_url'];
//        dump($arrUrl);
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            foreach ($arrUrl as $value) {
                foreach ($value as $v) {
                    $urlInfo = [
                        'k' => $sogo_query,
                        'u' => $v,
                        'f' => 'art'
                    ];
                    $jsonUrlInfo = json_encode($urlInfo);
                    try {
                        $redis->lPush('sogou_task_url', $jsonUrlInfo);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
            $sogou_art_page_count = cache('sogou_art_page_count');
            cache('sogou_art_page_count', $sogou_art_page_count + 5, 60 * 60 * 25);
            cache('sogou_art_pages', $c_page, 60 * 60 * 24);
            echo date('Y-m-d H:i:s', time()) . " art ok \r\n";
        } else {
            echo date('Y-m-d H:i:s', time()) . " art stop:task to much\r\n";
            $this->mps();
        }
    }

    /**
     * 根据文章链接采集公众号信息
     */
    public function mps()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        try {
            for ($i = 1; $i <= 5; $i++) {
                $sogou_task_url[] = json_decode($redis->rPop('sogou_task_url'), true);
            }
            foreach ($sogou_task_url as $v) {
                $keyword_flag[$v['u']] = $v;
                $sogou_task_begin_url[] = $v['u'];
            }
            if (empty($sogou_task_url[0])) {
                die(date('Y-m-d H:i:s', time()) . " 链接任务空 \r\n");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        QueryList::run('Multi', [
            'list' => $sogou_task_begin_url,
            'curl' => [
                'opt' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_AUTOREFERER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_USERAGENT => $this->UA(),
                ),
                'maxThread' => 5,
                'maxTry' => 2
            ],
            'success' => function ($a) use ($keyword_flag) {
                $reg = array(
                    'mp_name' => array('#js_profile_qrcode > div > strong', 'text'),
                    'mp_num' => array('#js_profile_qrcode > div > p:nth-child(3) > span', 'text'),
                    'content' => array('#js_profile_qrcode > div > p:nth-child(4) > span', 'text'),
                    'biz' => array('#js_content > p:nth-child(26) > a', 'href'),

                );
                $html = str_replace("<!--headTrap<body></body><head></head><html></html>-->", "", $a['content']);
                $ql = QueryList::Query($html, $reg);
                $data = $ql->getData();
                //截取biz
                $biz_s = mb_strpos($html, 'var biz =');
                $biz_e = mb_strpos($html, 'var sn =');
                $biz = mb_substr($html, $biz_s + 15, $biz_e - $biz_s - 23);
                $data[0]['biz'] = $biz;
                //截取原始ID gh
                $gh_s = mb_strpos($html, 'var user_name =');
                $gh_e = mb_strpos($html, 'var user_name_new =');
                $gh = mb_substr($html, $gh_s + 17, $gh_e - $gh_s - 25);
                $data[0]['gh'] = $gh;
                $data[0]['qr_code'] = "http://open.weixin.qq.com/qr/code/?username={$gh}";//二维码
                if (!empty($biz) && !empty($data[0]['mp_name'])) {
                    $unique = Db::name('mp_library')
                        ->field('biz')
                        ->where("BINARY biz = '" . $biz . "'")
                        ->find();
                    if (empty($unique)) {
                        $add['mp_name'] = $data[0]['mp_name'];
                        $add['weixinname'] = $data[0]['mp_num'];
                        $add['original_id'] = $gh;
                        $add['introduce'] = $data[0]['content'];
                        $add['biz'] = $biz;
                        $add['head_img_url'] = $data[0]['qr_code'];
                        $add['create_time'] = time();
                        Db::name('mp_library')->insert($add);
                        $sogou_new_add_count = cache('sogou_new_add_count');
                        cache('sogou_new_add_count', $sogou_new_add_count + 1, 60 * 60 * 25);//当天总入库数量标记
                        //关键词入库标记
                        foreach ($keyword_flag as $k => $v) {
                            if ($a['info']['url'] == $v['u']) {
                                if ($v['f'] == 'mp') {
                                    $current_mp_count = cache('current_mp_count_' . $v['k']);
                                    cache('current_mp_count_' . $v['k'], $current_mp_count + 1, 60 * 60 * 24);
                                    unset($current_mp_count);
                                } else {
                                    $current_art_count = cache('current_art_count_' . $v['k']);
                                    cache('current_art_count_' . $v['k'], $current_art_count + 1, 60 * 60 * 24);
                                    unset($current_art_count);
                                }
                            }
                        }
                    } else {
                        $sogou_repeat_count = cache('sogou_repeat_count');
                        cache('sogou_repeat_count', $sogou_repeat_count + 1, 60 * 60 * 25);
                        //关键词入库重复标记
                        foreach ($keyword_flag as $k => $v) {
                            if ($a['info']['url'] == $v['u']) {
                                if ($v['f'] == 'mp') {
                                    $current_mp_repeat_count = cache('current_mp_repeat_count_' . $v['k']);
                                    cache('current_mp_repeat_count_' . $v['k'], $current_mp_repeat_count + 1, 60 * 60 * 24);
                                    unset($current_mp_repeat_count);
                                } else {
                                    $current_art_repeat_count = cache('current_art_repeat_count_' . $v['k']);
                                    cache('current_art_repeat_count_' . $v['k'], $current_art_repeat_count + 1, 60 * 60 * 24);
                                    unset($current_art_repeat_count);
                                }
                            }
                        }
                    }
                }
            }
        ]);
        echo date('Y-m-d H:i:s', time()) . " task add db ok \r\n";

    }

    /**
     * 自动取关键字文章页码
     */
    private function getArtPage($keyword)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://weixin.sogou.com/weixin?type=2&s_from=input&query={$keyword}&ie=utf8&_sug_=n&_sug_type_=",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => $this->UA(),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $p_s = mb_strpos($response, '找到约');
            $p_e = mb_strpos($response, '条结果');
            $page = mb_substr($response, $p_s + 3, $p_e - $p_s - 3);
            $page_arr = explode(',', $page);
            if (sizeof($page_arr) == 2) {
                $page_num = $page_arr[0] . $page_arr[1];
            } else {
                $page_num = $page;
            }
            $autoPage = ceil($page_num / 10);
            if ($autoPage >= 100) {
                cache('sogou_art_keyword_pages', 100, 60 * 60 * 24);
            } else {
                cache('sogou_art_keyword_pages', $autoPage, 60 * 60 * 24);
            }
        }

    }

    /**
     * 搜索公众号用的关键字
     */
    private function getKeyword()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);

        $sogou_keyword_db = cache('sogou_keyword_db');
        if (empty($sogou_keyword_db)) {
            $result = Db::name('task_keywords')
                ->field('keyword')
                ->order('id')
                ->select();
            cache('sogou_keyword_db', $result, 60 * 60 * 24 * 30);
            try {
                foreach ($result as $value) {
                    $redis->lPush('keyword', $value['keyword']);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        try {
            $sogouKeyword = $redis->rPop('keyword');
            if (empty($sogouKeyword)) {
                cache('sogou_keyword_db', 0, 60 * 60 * 24 * 30);
                die(date('Y-m-d H:i:s', time()) . " 关键词空 \r\n");
            } else {
                $query = urlencode($sogouKeyword);
                cache('sogou_keyword', $query, 60 * 60 * 24);
                cache('sogou_keyword_raw', $sogouKeyword, 60 * 60 * 24);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 模拟浏览器UA
     */
    private function UA()
    {
        $userAgent = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.86 Safari/537.36',//Chrome
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',//win7 chrome
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',//360
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0',//firefox
        ];
        return $userAgent[rand(0, 3)];
    }

    /**
     * 搜索文章用的关键字
     */
    private function getArtKeyword()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $sogou_keyword_db = cache('sogou_art_keyword_db');
        if (empty($sogou_keyword_db)) {
            $result = Db::name('task_keywords')
                ->field('keyword')
                ->order('id')
                ->select();
            cache('sogou_art_keyword_db', $result, 60 * 60 * 24 * 30);
            try {
                foreach ($result as $value) {
                    $redis->lPush('art_keyword', $value['keyword']);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        try {
            $sogouKeyword = $redis->rPop('art_keyword');
            if (empty($sogouKeyword)) {
                cache('sogou_art_keyword_db', 0, 60 * 60 * 24 * 30);
                die(date('Y-m-d H:i:s', time()) . " 关键词空 \r\n");
            } else {
                $query = urlencode($sogouKeyword);
                cache('sogou_art_keyword', $query, 60 * 60 * 24);
                cache('sogou_art_keyword_raw', $sogouKeyword, 60 * 60 * 24);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * 设置/更新cookie
     */
    public function setCookie()
    {
        $cookie = Request::instance()->get('cookie');
        if (!empty($cookie)) {
            cache('sogou_Cookie', $cookie, 60 * 60 * 24 * 30);
            cache('is_cookie_invalid', 0, 60 * 60 * 24 * 30);
            echo 'cookie设置成功，cookie为';
            dump(cache('sogou_Cookie'));
        } else {
            echo 'cookie值不能为空，请检查。以下为以缓存的cookie';
            dump(cache('sogou_Cookie'));

        }
    }

    /**
     * 限速策略
     */
    public function autoStart()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $q_num = $redis->lLen("sogou_task_url");
        $flag = cache('sogou_task_status');
        if ($flag != 'stop') {
            if ($q_num < 10000) {
                cache('sogou_task_status', 'start', 60 * 60);
                echo date('Y-m-d H:i:s', time()) . " Current task number:{$q_num} \r\n";
            } else {
                cache('sogou_task_status', 'stop', 60 * 60);
                echo date('Y-m-d H:i:s', time()) . " Current task number:{$q_num} \r\n";
            }
        } else {
            //休息时间
            echo date('Y-m-d H:i:s', time()) . " stop URL task , begin ALL ADD DB task , Current task number:{$q_num} \r\n";
        }
    }

    /**
     * 统计当天抓取量
     */
    public function count()
    {
        $time = date('H:i', time());
        $flag = cache('sogou_add_db_flag');
        $mp_page = cache('sogou_mp_page_count');
        $art_page = cache('sogou_art_page_count');
        $new_add = cache('sogou_new_add_count');
        $repeat = cache('sogou_repeat_count');

        $add['date'] = date("Y-m-d", strtotime("-1 day"));
        $add['mp_page'] = $mp_page;
        $add['art_page'] = $art_page;
        $add['new'] = $new_add;
        $add['repeat'] = $repeat;
        $add['create_time'] = time();
        dump($add);

//        if (true){//测试时候打开
        if ($time == '00:00' && empty($flag)) {
            Db::name('task_count')->insert($add);
            //重置统计
            cache('sogou_mp_page_count', 0, 60 * 60 * 25);
            cache('sogou_art_page_count', 0, 60 * 60 * 25);
            cache('sogou_new_add_count', 0, 60 * 60 * 25);
            cache('sogou_repeat_count', 0, 60 * 60 * 25);
            cache('sogou_add_db_flag', 1, 60);

            $this->keyword_count();//当日关键词入库统计
        } else {
            echo date('Y-m-d H:i:s', time()) . " wait time\r\n";
        }
    }

    /**
     * 关键词统计
     */
    public function keyword_count()
    {
        $sogou_keyword_db = cache('sogou_keyword_db');
        $kw_num = 0;
        foreach ($sogou_keyword_db as $v) {
            //赋值
            $db_kw = $v['keyword'];
            $mp_count = cache('current_mp_count_' . urlencode($db_kw));
            $art_count = cache('current_art_count_' . urlencode($db_kw));
            $mp_repeat_count = cache('current_mp_repeat_count_' . urlencode($db_kw));
            $art_repeat_count = cache('current_art_repeat_count_' . urlencode($db_kw));
            $add['keyword'] = $db_kw;
            $add['mp_count'] = $mp_count;
            $add['art_count'] = $art_count;
            $add['mp_repeat_count'] = $mp_repeat_count;
            $add['art_repeat_count'] = $art_repeat_count;
            $add['date'] = date("Y-m-d", strtotime("-1 day"));
            $add['time'] = time();
            //入库
            if (!empty($mp_count) || !empty($art_count) || !empty($mp_repeat_count) || !empty($art_repeat_count)) {
                Db::name('task_keyword_count')->insert($add);
                $kw_num = $kw_num + 1;
            }
            //重置
//            unset($db_kw);
            unset($mp_count);
            unset($art_count);
            unset($mp_repeat_count);
            unset($art_repeat_count);
            $db_kw = $v['keyword'];
            cache('current_mp_count_' . urlencode($db_kw), 0, 600);
            cache('current_art_count_' . urlencode($db_kw), 0, 600);
            cache('current_mp_repeat_count_' . urlencode($db_kw), 0, 600);
            cache('current_art_repeat_count_' . urlencode($db_kw), 0, 600);
        }
        echo date('Y-m-d H:i:s', time()) . " keyword count {$kw_num} \r\n";
    }


}
