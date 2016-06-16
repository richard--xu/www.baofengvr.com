<?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{

    /**
     * 全局配置文件
     */
    static public $config;

    /**
     * load
     *
     * @param
     * @return
     **/
    public function _initLoad()
    {
        $loader = Yaf_Loader::getInstance(LOCAL_LIB.DS, GLOBAL_LIB.DS);
        $loader->registerLocalNamespace(array('Widget', 'LastError', 'Session', 'Rds', 'Model'));
    }
    
    /**
     * 初始化一些php配置
     * 如：时区、报错、编码、程序突然出错中止设置等
     *
     * @param
     * @return
     **/
    public function _initPHP()
    {
        //设置网站时区
        date_default_timezone_set('Etc/GMT' . TIME_ZONE);
        //设置编码
        ini_set('default_charset', 'UTF-8');
        mb_internal_encoding('UTF-8');
        //关闭自动对字符串操作
        ini_set('magic_quotes_gpc', 'off');

        //是否开启错误
        if (Yaf_Application::app()->getConfig()->project->mode) {
            //open error output
            ini_set('display_errors', 1);
            error_reporting(E_ALL | E_STRICT);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        //系统致命错误处理
        register_shutdown_function(array('LastError', 'handler'));
    }

    /**
     * 初始化config信息
     *
     * @param
     * @return
     **/
    public function _initConfig()
    {
        $deploy  = include_once INC_PATH . DS . 'config' . DS . 'deploy.php';
        $configs = include_once INC_PATH . DS . 'config' . DS . 'configs.php';
        self::$config = array_merge($deploy, $configs);
    }

    /**
     * initialize View
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        //doesn't allow use short tag in template
        Yaf_Dispatcher::getInstance()
                      ->setView(new Yaf_View_Simple(APP_VIEW, array('short_tag' => false)));
    }

    /**
     * 初始化插件
     *
     * @param $dispatcher object of Yaf_Dispatcher
     * @return void
     **/
    public function _initPlugins(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new HookPlugin());
    }

    /**
     * 初始化session或cookie信息
     *
     * @param
     * @return
     **/
    public function _initSession()
    {
        $sessionType = strpos($_SERVER['REQUEST_URI'], '/api/') === 0 ? 2: 1;
        //初始化session
        $_SESSION    = Session::getInstance($sessionType);
    }
}