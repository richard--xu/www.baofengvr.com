<?php
/**
* topics module的基类 
*
*/
class RootController extends BaseController
{
    /**
    * 权限控制
    */
    private $right = array('Applytemplet' => array(1), 'Topic' => array(1), 'Applylist' => array(1, 2, 3, 4), 'Login' => array(1, 2, 3, 4));

    /**
    * 设定的默认的每页显示数目
    */
    const LIMIT_DEFAULT = 20;

    /**
    * default page Title
    *
    * @var string
    */
    public $pageTitle  = 'VR后台管理系统';

    /**
     * 初始化
     *
     * @return void
     **/
    public function init()
    {
        parent::init();

        //判断登录
        //$userid = Common::getSession('USERID');
        if ($this->_controller != 'Login' && !isset($_SESSION['uid'])) {
            $this->redirect('/admin/login/index');
        }
        //$account = Bootstrap::$config['account'];
        $this->setViewPath(MODULE_PATH . DS . 'Admin' . DS . 'views');
    }
}