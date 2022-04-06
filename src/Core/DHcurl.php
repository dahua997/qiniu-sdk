<?php


namespace Dahua\QiniuSdk\Core;

class DHcurl
{
    private $_ch;
    private $_data;//需要设置的参数
    private $_url;//抓取连接
    private $_extends_data;//setopt的特殊设置
    private $_cookie_file; //cookies文件路径
    private $_header; //头部信息
    private $_https; //https
    public  $_res;//结果
    private $_options;//结果
    private $_debug;//是否开启调试
    private $_httpCode;//是否开启调试

    public function init()
    {

    }

    public function __construct()
    {
        $this->_ch = curl_init();
    }

    public function setData($data = array()){

        $this->_cookie_file = dirname(__FILE__).'/cookies.txt';

        if(is_array($data)){
            foreach($data as $k=>$v){
                $this->$k = $v;
            }
        }

        return $this;
    }

    public function get($isClose=true){

        curl_setopt ($this->_ch, CURLOPT_URL, $this->_url);
        curl_setopt ($this->_ch, CURLOPT_RETURNTRANSFER,1);
//      curl_setopt ($this->_ch, CURLOPT_ENCODING, 'gzip');
        if($this->_header){
            curl_setopt ($this->_ch,CURLOPT_HTTPHEADER,$this->_header);
        }else{
            curl_setopt ($this->_ch, CURLOPT_HEADER, 0);//是否带有头部信息
        }
        if($this->_https){
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
        }

        $res = curl_exec($this->_ch);
        $this->_httpCode = curl_getinfo($this->_ch,CURLINFO_HTTP_CODE);

        $encode = mb_detect_encoding($res, array("ASCII","UTF-8","GB2312","GBK",'BIG5'));

        if($encode != 'UTF-8'){
            $content = mb_convert_encoding($res,'utf-8',array('gb2312','gbk','ASCII'));
        }else{
            $content = $res;
        }

        $this->_res['response'] = $content;

        if($this->_debug){
            $this->_res['getinfo'] = curl_getinfo($this->_ch);
            $this->_res['error'] = curl_error($this->_ch);
        }

        if($isClose){
            curl_close($this->_ch);
        }

        return $this;

    }

    public function post($isClose=true){

        $this->_options = array(
            CURLOPT_POST   => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL    => $this->_url,
            CURLOPT_FRESH_CONNECT  => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE   => 1,
            CURLOPT_TIMEOUT  => 4,
            CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36'
        );

        if($this->_header){
            $this->_options[CURLOPT_HTTPHEADER] = $this->_header;
        }else{
            curl_setopt ($this->_ch, CURLOPT_HEADER, 0);//是否带有头部信息
        }
        if($this->_data){
            $this->_options[CURLOPT_POSTFIELDS] = $this->_data;
        }
        if($this->_https){
            $this->_options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->_options[CURLOPT_SSL_VERIFYHOST] = false;
        }else{
            $this->_options[CURLOPT_COOKIEJAR] = $this->_cookie_file;
            $this->_options[CURLOPT_COOKIEFILE] = $this->_cookie_file;
        }

        if( isset($this->_extends_data) && is_array($this->_extends_data)){
            curl_setopt_array($this->_ch, ($this->_extends_data+$this->_options));
        }else{
            curl_setopt_array($this->_ch, $this->_options);
        }

        $this->_res['response'] = curl_exec($this->_ch);

        if($this->_debug){
            $this->_res['getinfo'] = curl_getinfo($this->_ch);
            $this->_res['error'] = curl_error($this->_ch);
        }

        if($isClose){
            curl_close($this->_ch);
        }

        return $this;
    }


    /**
     * url数组 count
     */
    public function multiGet(array $url_arr,$count=0){

        $defaults = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT=>3
        );

        if($this->_https){
            $defaults[CURLOPT_SSL_VERIFYPEER] = false;
            $defaults[CURLOPT_SSL_VERIFYHOST] = false;
        }else{
            $this->_options[CURLOPT_COOKIEJAR] = $this->_cookie_file;
            $this->_options[CURLOPT_COOKIEFILE] = $this->_cookie_file;
        }

        $mch = curl_multi_init();
        foreach($url_arr as $k=>$url){
            $ch[$k] = curl_init($url);
            curl_setopt_array($ch[$k], $defaults);
            curl_multi_add_handle ($mch,$ch[$k]);
        }

        do {curl_multi_exec($mch,$active);
        } while ($active);

        foreach ($url_arr as $i => $url) {
            curl_error($ch[$i]);
            $content = curl_multi_getcontent($ch[$i]); // 获得爬取的代码字符串
            $data[$i]['content'] = $content;
            $data[$i]['errno']   = curl_getinfo($ch[$i],CURLINFO_HTTP_CODE);
        }

        foreach ($url_arr as $i => $url) {
            curl_multi_remove_handle($mch,$ch[$i]);
            curl_close($ch[$i]);
        }

        $response['data'] = $data;

        curl_multi_close($mch);

        return $response;
    }

    /***
     * 过滤json数据
     */
    public function jsonDecode(){
        $this->_res['res'] = object_array(json_decode($this->_res['res']));
        return $this;
    }

    public function close()
    {
        curl_close($this->_ch);
    }

}
