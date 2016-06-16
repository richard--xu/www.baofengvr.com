<?php
/**
* 后台首页
*/

include_once(__DIR__.'/Root.php');

class LoginController extends RootController
{
    /**
     * 后台登录
     *
     * @return void
     * @author richard 
     **/
    public function indexAction()
    {
        if (isset($_SESSION['uid'])) {
            return $this->redirect('/admin/login/home');
        }
    }

    /**
     * 处理登录
     *
     * @return void
     * @author richard 
     **/
    public function loginAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        //判断输入数据
        if (!($username = $this->getTrimedPost('username', ''))) {
            return $this->sendAjax('用户名不能为空', false);
        }
        if (!($password = $this->getTrimedPost('password', ''))) {
            return $this->sendAjax('密码不能为空', false);
        }

        $user       = new Model_Cinema_User();
        $where      = array(
                            'userName' => $username, 
                            'password' => md5($password), 
                            'isAdmin' => array('>' => 0)
                      );
        $userInfo   = $user->select($where, array('id', 'avatar', 'isAdmin'));
        if (!$userInfo) {
            return $this->sendAjax('账号密码错误!', false);
        }

        //设置session
        $_SESSION['uid']     = $userInfo['id'];
        $_SESSION['uname']   = $username;
        $_SESSION['avatar']  = $userInfo['avatar'];
        $_SESSION['isAdmin'] = $userInfo['isAdmin'];
        return $this->sendAjax('登录成功');
    }

    /**
     * 登出
     * 
     * @return void
     **/
    public function loginoutAction()
    {
        unset($_SESSION['uid']);
        unset($_SESSION['uname']);
        $this->redirect('http://'. $this->getRequest()->getServer('HTTP_HOST') .'/admin/login/index');
    }

    /**
     * 后台登录
     *
     * @return void
     * @author richard 
     **/
    public function homeAction()
    {
        if (!isset($_SESSION['uid'])) {
            return $this->redirect('/admin/login/index');
        }
    }
}