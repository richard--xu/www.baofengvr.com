<?php
/**
* 公共函数类
* 
*/
class Common
{
    /**
    * 存储css和js配置信息
    *
    * @var array
    */
    private static $tempCssAndJsConfig = array();

    /**
     * get css file url
     * @param string $file     file name
     * @param string $module   module name
     * @param string   $version  come with version
     * @return string
     */
    public static function getCssUrl($file, $module = '', $version = '')
    {
        $module  = $module ? '/' . $module : '';
        return Bootstrap::$config['imgDomain'] . '/front' . $module . '/css/' . $file . '.css?v=' . $version;
    }
    
    /**
     * get merged css file url
     * @param array $files     file name
     * @param string $module   module name
     * @param string   $version  come with version
     * @return string
     */
    public static function getMergedCssUrl($files, $module = '', $version = '')
    {
        $url = $module ? Bootstrap::$config['imgDomain'] . '/front/' . $module .'/css/??' : Bootstrap::$config['imgDomain'] . '/front/css/??';
        foreach ($files as $file) {
            $url .=  $file . '.css,';
        }
        
        return rtrim($url, ',') . '?v=' . $version;
    }
    

    /**
     * get js file url
     * @param string $file     file name
     * @param string $module   module name
     * @param string   $version  come with version
     * @return string
     */
    public static function getJsUrl($file, $module = '', $version = '')
    {
        $module  = $module ? '/' . $module : '';
        return Bootstrap::$config['imgDomain'] . '/front' . $module . '/js/' . $file . '.js?v=' . $version;
    }
    
    /**
     * get merged js file url
     * @param string $file     file name
     * @param string $module   module name
     * @param string   $version  come with version
     * @return string
     */
    public static function getMergedJsUrl($files, $module = '', $version = '')
    {
        $url  = $module ? Bootstrap::$config['imgDomain'] . '/front/' . $module .'/js/??' : Bootstrap::$config['imgDomain'] . '/front/js/??';
        foreach ($files as $file) {
            $url .=  $file . '.js,';
        }
        
        return rtrim($url, ',') . '?v=' . $version;
    }

    /**
     * get image file url
     * @param string $file     file name
     * @param string $module   module name
     * @return string
     */
    public static function getImgUrl($file, $module = '')
    {
        $module  = $module ? '/' . $module : '';
        return Bootstrap::$config['imgDomain'] . '/front' . $module . '/images/' . $file;
    }

    /**
    * 获取所有要加载的css或者js的url
    *
    * @param string $modules 程序运行所在的module
    * @param string $controller 程序运行所在的controller
    * @param string $action 程序运行所在的action
    * @param string(js|css) $type 要加载的是css还是js 
    * @return mix(array|false)
    */
    public static function loadCssAndJsUrl($modules, $controller, $action, $type = 'js')
    {
        if (!in_array($type, array('js', 'css'))) {
            return false;
        }

        if (!self::$tempCssAndJsConfig)
            self::$tempCssAndJsConfig = include INC_PATH . DS . 'config' . DS . 'configCssAndJs.php';

        $configCssAndJs = self::$tempCssAndJsConfig;
        $filePath = array();
        $tempFiles = array();
        if ($configCssAndJs) {
            $tempFiles['default'] = $configCssAndJs['__'][$type];
            
            if (isset($configCssAndJs[$modules])) {
                //如果设定不继承父级,清空
                if (isset($configCssAndJs[$modules]['__'][$type.'NotInherit'])
                        && $configCssAndJs[$modules]['__'][$type.'NotInherit']) {
                    $tempFiles = array();    
                }
                $tempFiles['modules'] = $configCssAndJs[$modules]['__'][$type];
                //controller
                if (isset($configCssAndJs[$modules][$controller])) {
                    //如果设定不继承父级,清空
                    if (isset($configCssAndJs[$modules][$controller]['__'][$type.'NotInherit'])
                            && $configCssAndJs[$modules][$controller]['__'][$type.'NotInherit']) {
                        $tempFiles = array();    
                    }
                    $tempFiles['controller'] = $configCssAndJs[$modules][$controller]['__'][$type];
                    //action
                    if (isset($configCssAndJs[$modules][$controller][$action]) 
                            && isset($configCssAndJs[$modules][$controller][$action][$type])
                                && $configCssAndJs[$modules][$controller][$action][$type]) {
                        //如果设定不继承父级,清空
                        if (isset($configCssAndJs[$modules][$controller][$action][$type.'NotInherit'])
                                && $configCssAndJs[$modules][$controller][$action][$type.'NotInherit']) {
                            $tempFiles = array();    
                        }
                        $tempFiles['action'] = $configCssAndJs[$modules][$controller][$action][$type];
                    }
                }
            }
        }
        
        if ($tempFiles) {
            $version = $configCssAndJs['version'];
            foreach ($tempFiles as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $val) {
                        //如果是controller或者action级的并且路径不是('full', '_top')要加上controller的路径
                        if (($key == 'action' || $key == 'controller') && $controller && !in_array($val, array('full', '_top'))) {
                            $val = $controller . '/' . $val;
                        }

                        //如果是配置的 '路径或者文件名' => '标志最顶级(_top)或者完整路径(full)'
                        if (is_string($k)) {
                            if ($val == 'full') {
                                $filePath[] = Bootstrap::$config['imgDomain'].$k.'.'.$type ;
                                continue;    
                            } elseif ($val == '_top') {
                                $mod = '';
                                $val = $k;
                            } else {
                                continue;
                            }
                        } else { //如果正常情况, 即 array('文件名')
                            $mod = $key == 'default' ? '' : $modules;    
                        }
                        
                        $filePath[] = $type == 'js' ? self::getJsUrl($val, $mod, $version) : self::getCssUrl($val, $mod, $version);
                    }
                }
            }
        }

        return array_unique($filePath);
    }    

    /**
    * 将gbk字符转换成utf8 
    *
    * @param mix(array|string) $array 需要转换的数组
    * @return mix(array|string)
    */
    public static function encodeUTF8($array)
    {
        if (!is_array($array)) {
            return iconv('gbk', 'utf-8', $array);
        }

        foreach ($array as $key=>$value) {
            if (!is_array($value)) {
                $array[$key]=mb_convert_encoding($value,"UTF-8","GBK"); //由gbk转换到utf8
            } else {
                self::encodeUTF8($array[$key]);
            }
        }

        return $array;
    }

    /**
     * 检测是否是手机号码
     *
     * @param int $phone 手机号码
     * @return boolen
     **/
    public static function isPhone($phone)
    {
        return preg_match("/^((\(\d{3}\))|(\d{3}\-))?1\d{10}$/", $phone);
    }

    /**
     * 检测是否是邮箱
     *
     * @param int $email 邮箱账号
     * @return boolen
     **/
    public static function isEmail($email)
    {
        return preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/", $email);
    }

    /**
     * 上传图片至本地
     * @param string  $dest  目录地址, 例: 'upload/develop/company'
     * @param int     $max   最大尺寸, 单位 K
     * @param array   $type  文件类型, array('jpg', 'png' ....)
     * @param string  $name  $_FILES[$name]
     * @return array 文件路径加名字
     */
    public static function uploadPic($dest, $max = '512000', $type = array('gif','png','jpg','jpeg','bmp'), $name = 'file')
    {
        if (!$name) {
            $name = key($_FILES);
        }

        if (empty($_FILES)) {
            return array('result' => false, 'msg' => '未发现上传文件');
        }

        if (isset($_FILES[$name]['error']) && ($_FILES[$name]['error'] == 0)) {
            $fileExt =  self::checkFileType($_FILES[$name]['tmp_name']);
            //如果没找到类型,采用文件名后缀
            if (strpos($fileExt, 'unknown') !== false) {
                $fileExt = substr($_FILES[$name]['name'], strrpos($_FILES[$name]['name'], '.')+1);
            }
            
            if (!in_array($fileExt, $type)) {
                return array('result' => false, 'msg' => '文件格式不正确');
            }

            if ($_FILES[$name]['size'] > $max) {
                return array('result' => false, 'msg' => '文件尺寸过大');
            }
            $fileName = uniqid(rand()) . '.' . $fileExt;
            //$realDest = Bootstrap::$config['uploadRoot'] . '/' . trim($dest, '/') . '/';
            
            if (@move_uploaded_file($_FILES[$name]['tmp_name'], $dest . $fileName)) {
                return array('result' => true, 'msg' => '/'.trim($dest, '/') . '/' . $fileName);
            }
        }
        return array('result' => false, 'msg' => '上传文件失败');
    }

    /**
     * 循环建文件夹
     *
     * @param string  $dest  要建文件夹的路径
     * @param string  $mode  文件允许的权限
     * @return boolen  true|false
     */
    public static function mkDirByPath($dest, $mode = 0777)
    {
        if (!is_dir($dest)) {
            $prePath = dirname($dest);
            $result = self::mkDirByPath($prePath, $mode);
            if ($result === true) {
                if (mkdir($dest)) {
                    return chmod($dest, $mode);
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 获取url指定的内容
     *
     * @param string  $url  要获取文件的url地址
     * @param int  $timeout  请求等待时间
     * @return mix
     */
    public static function curlGetContents($url, $dir)
    {
        $ch = curl_init($url);
        $fp = fopen($dir, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res=curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return $res;
    }

    /**
     * 检测用户上传的文件类型
     * @param  string $filename
     * @return string
     */
    static public function checkFileType($filename)
    {
        $file     = fopen($filename, 'rb');
        $bin      = fread($file, 2);
        fclose($file);
        $strInfo  = @unpack("c2chars", $bin);
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        $fileType = '';
        switch ($typeCode)
        {
            case 7790   : $fileType = 'exe';  break;
            case 7784   : $fileType = 'midi'; break;
            case 8297   : $fileType = 'rar';  break;
            case 255216 : $fileType = 'jpg';  break;
            case 7173   : $fileType = 'gif';  break;
            case 6677   : $fileType = 'bmp';  break;
            case 13780  : $fileType = 'png';  break;
            //case 8075   : $fileType = 'docx'; break;
            //case -48    : $fileType = 'doc'; break;
            default     : $fileType = 'unknown' . $typeCode;  break;
        }

        if ($strInfo['chars1'] == '-1' && $strInfo['chars2'] == '-40') {
            return 'jpg';
        }
        if ($strInfo['chars1'] == '-119' && $strInfo['chars2'] == '80') {
            return 'png';
        }
        return $fileType;
    }

    /**
     * 取数组中的某一个键名的值， 以 array 形式返回
     * @param array  $array
     * @param string $column
     * @return array
     */
    public static function getColumnArray($array, $column)
    {
        if (is_array($array) && $array && $column) {
            $temp = array();
            foreach ($array as $key => $val) {
                if (isset($val[$column]) && $val[$column]) {
                    $temp[] = $val[$column];
                }
            }
            return $temp;
        }
        return $array;
    }

    /**
     * 将数组中某一个值作为键名
     * @param array  $array
     * @param string $column
     * @return array
     */
    public static function convertArrBycolumn($array, $column)
    {
        if (is_array($array) && $array && $column) {
            $temp = array();
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $temp[$val[$column]] = $array[$key];
                }
            }
            return $temp;
        }
        return $array;
    }

    /**
     * 将数组某一个字段值相同的组合在一个数组中
     * 列: array(array('a' => 1, 'b' => 2), array('a' =>1 , 'b' => 3), array('a' =>2 , 'b' => 4)) , 以a的值为键, 变为
     * array(1 => array(array('a' => 1, 'b' => 2), array('a' =>1 , 'b' => 3)), 2 => array(array('a' =>2 , 'b' => 4)))
     * @param array  $array
     * @param string $column
     * @return array
     */
    public static function convertToOneArrayBycolumn($array, $column)
    {
        if (is_array($array) && $array && $column) {
            $temp = array();
            foreach ($array as $key => $val) {
                if (!is_array($val) || !isset($val[$column])) {
                    return $array;
                }
                $temp[$val[$column]][] = $val;
            }
            return $temp;
        }
        return $array;
    }

    /**
    * 获得二维数组中的某一列的值数组
    * @param array  $array
    * @param string $columns 需要获取的列的键值
    * @param string $index 是否以一维的键为键值
    */
    public static function arrayArrayColumns($array, $columns, $index = '')
    {
        $ret = array();
        foreach ($array as $key => $item) {
            if (!isset($item[$columns])) {
                return $ret;
            } else {
                if($index && isset($item[$index])){
                    $ret[$item[$index]] = $item[$columns];
                } else {
                    $ret[$key] = $item[$columns];
                }
            }
        }
        return $ret;
    }

    /**
    * 获得二维数组中的某一列等于特定值的值数组
    * @param array  $array
    * @param string $columns 具体的列
    * @param string $value   具体的值
    */
    public static function arrayArrayColumnsByValue($array, $columns, $value = '')
    {
        $ret = array();
        foreach ($array as $key => $item) {
            if(isset($item[$columns]) && $item[$columns] == $value){
                $ret[] = $item;
            }
        }
        return $ret;
    }

    /**
     * Utf-8、gb2312都支持的汉字截取函数
     *
     * @param string $string 要截取的字符串
     * @param int $sublen 截取长度
     * @param int $start 开始长度
     * @param string $code 编码
     * @return string
     **/
    public static function cut_str($string, $sublen, $start = 0, $code = 'UTF-8')
    {

        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);

            if (count($t_string[0]) - $start > $sublen) {
                return join('', array_slice($t_string[0], $start, $sublen))."...";
            }

            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start = $start*2;
            $sublen = $sublen*2;
            $strlen = strlen($string);
            $tmpstr = '';

            for ($i=0; $i< $strlen; $i++) {
                if ($i>=$start && $i< ($start+$sublen)) {
                    if (ord(substr($string, $i, 1))>129) {
                        $tmpstr.= substr($string, $i, 2);
                    } else {
                        $tmpstr.= substr($string, $i, 1);
                    }
                }

                if (ord(substr($string, $i, 1))>129) {
                    $i++;
                }
            }

            if (strlen($tmpstr)< $strlen ) 
                $tmpstr.= "...";

            return $tmpstr;
        }
    }

    /**
     *
     * 获取字符长度
     * @param string $str
     */
    public static function strLength($str,$encode='gb2312'){
        $str = trim($str);
        $char = 0; //字符字母
        $hanchar = 0; //汉字
        $len = strlen($str);
        $jumpbit=strtolower($encode)=='gb2312'?2:3;//跳转位数
        for($i=0; $i<$len; ){
            if(ord($str[$i])>0 && ord($str[$i])<128){  //字符字母
                $i++;
                $char++;
            }elseif(ord($str[$i])>=128){   //汉字
                $i+=$jumpbit;
                $hanchar++;
            }
        }
        $count = $char + $hanchar*2;
        return $count;

     }

     /**
     *
     * 获取客户端IP
     * @return string
     */
     public static function getClientIp(){
        if(getenv('HTTP_CLIENT_IP')){
            $client_ip = getenv('HTTP_CLIENT_IP');
        }elseif(getenv('HTTP_X_FORWARDED_FOR')){
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        }elseif(getenv('REMOTE_ADDR')){
            $client_ip = getenv('REMOTE_ADDR');
        }else{
            $client_ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
        }
        return addslashes($client_ip);
    }
}