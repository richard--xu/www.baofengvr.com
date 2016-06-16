<?php
/**
* topics module的基类 
*
*/
class RootController extends BaseController
{
    /**
    * 设定的默认的每页显示数目
    */
    const LIMIT_DEFAULT = 20;
    
    /**
     * 初始化
     *
     * @return void
     **/
    public function init()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        parent::init();

        /*$userid = Common::getSession('USERID');
        $account = Bootstrap::$config['account'];
        if ($userid && !Common::getSession('adminFlag' . $userid)) {
            if (in_array($userid, $account['super'])) {//超级管理员
                $_SESSION['adminFlag'.$userid] = 1;
            } elseif (in_array($userid, $account['leadership'])) {//领导
                $_SESSION['adminFlag'.$userid] = 2;
            } elseif (in_array($userid, $account['judge'])) {//评审
                $_SESSION['adminFlag'.$userid] = 3;
            } elseif ($_SESSION["SCHOOLADMIN"] === true) { //学校
                $_SESSION['adminFlag'.$userid] = 4;
            }
        }
        
        if (!in_array($this->_action, array('index')) && Common::getSession('adminFlag'.$userid) != 4) {
            $id = $this->getIntRequest('id', 0);
            die("<script type='text/javascript'>alert('对不起,您没有权限进入此页面,请联系易班工作站老师!');window.location.href='http://" .$this->getRequest()->getServer('HTTP_HOST'). "/sites/topics/topic/index?id=" . ($id ? $id : 1) . "'</script>");
        }*/

        $this->setViewPath(MODULE_PATH . DS . 'Topics' . DS . 'views');
    }
}