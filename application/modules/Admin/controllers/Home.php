<?php
/**
* 后台首页
*/

include_once(__DIR__.'/Root.php');

class HomeController extends RootController
{
    /**
     * 后台默认首页
     *
     * @return void
     * @author richard 
     **/
    public function indexAction()
    {
		if (!isset($_SESSION['userId'])) {
            return $this->redirect('/admin/login/index');
        }

        $this->getView()->assign(array(
                                    'newbieGuide' => $newbieGuide,
                                    'AdminUserRole_id' => $_SESSION['AdminUserRole_id']
                                ));
        
    }
}