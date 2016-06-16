<?php
/**
* 用户部分
*
*/

include_once(__DIR__.'/Root.php');

class UserController extends RootController
{
    /**
     * 注册
     *
     * @return
     **/
    public function registerAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();

        $param['userName'] = $this->getTrimedQuery('uname');
        $param['passWord'] = $this->getTrimedQuery('pw');
        $param['email']    = $this->getTrimedQuery('mail');
        $param['phone']    = $this->getTrimedQuery('phone');
        $result = $this->_checkRegister($param);
        if ($result !== true) {
            return $this->sendAjax($result, false);
        }
        $param['passWord']   = md5($param['passWord']);
        $param['addTime']    = time();
        $param['updateTime'] = time();
        $modelUser = new Model_Cinema_User();
        $result = $modelUser->save($param);
        //session开始持久化
        if ($result) {
            $_SESSION['uid']   = $result;
            $_SESSION['uname'] = $param['userName'];
            $ssid = Session::getInstance(2)->getSid();
        }
        return $result ? $this->sendAjax(array('ssid' => $ssid)) 
                       : $this->sendAjax('注册失败!', false);
    }

    /**
     * 登录
     *
     * @return
     **/
    public function loginAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $modelUser = new Model_Cinema_User();
        $userName = $this->getTrimedQuery('uname', '');
        $passWord = md5($this->getTrimedQuery('pw', ''));
        $userItem = $modelUser->select(array('userName' => $userName, 'password' => $passWord));
        if ($userItem) {
            $_SESSION['uid']   = $userItem['id'];
            $_SESSION['uname'] = $userItem['userName'];
            $ssid = Session::getInstance(2)->getSid();
            return $this->sendAjax(array('ssid' => $ssid));
        }
        return $this->sendAjax('用户名或密码错误!', false);
    }

    /**
     * 登出
     *
     * @return
     **/
    public function loginOutAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!isset($_SESSION['uid']) || !$_SESSION['uid']) {
            return $this->sendAjax('您还没登录, 不用登出!', false);
        }

        Session::getInstance(2)->clear();
        return $this->sendAjax('登出成功!');
    }

    /**
     * 评论
     *
     * @return
     **/
    public function commentAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!isset($_SESSION['uid']) || !$_SESSION['uid']) {
            return $this->sendAjax('请先登录才能评论!', false);
        }

        $pid     = $this->getIntQuery('pid', 0);
        $comment = $this->getTrimedQuery('comment', '');
        if (!$pid) {
            return $this->sendAjax('缺少作品信息,不能进行评论!', false);
        }

        if (!$comment) {
            return $this->sendAjax('评论内容不能为空!', false);
        }

        $modelUserComment = new Model_Cinema_UserComment();
        $add['userId']    = $_SESSION['uid'];
        $add['productId'] = $pid;
        $add['comment']   = $comment;
        $add['addTime']   = time();
        $result           = $modelUserComment->save($add);
        if (!$result) {
            return $this->sendAjax('评论失败!', false);
        }

        return $this->sendAjax('评论成功!');
    }

    /**
     * 评分
     *
     * @return
     **/
    public function starAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!isset($_SESSION['uid']) || !$_SESSION['uid']) {
            return $this->sendAjax('登录才能评分哦!', false);
        }

        $pid  = $this->getIntQuery('pid', 0);
        $star = $this->getTrimedQuery('star', 0);
        if (!$pid) {
            return $this->sendAjax('缺少作品信息,不能进行评分!', false);
        }

        if (!$star) {
            return $this->sendAjax('评分不能为零!', false);
        }

        $modelUserGiveStar = new Model_Cinema_UserGiveStar();
        $total = $modelUserGiveStar->getCount(array('userId' => $_SESSION['uid']));
        if ($total) {
            return $this->sendAjax('您已经给该作品评过分了!', false);
        }

        try {
            $connection = $modelUserGiveStar->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            $add['userId']     = $_SESSION['uid'];
            $add['productId']  = $pid;
            $add['star']       = $star;
            $add['addTime']    = time();
            $result            = $modelUserGiveStar->save($add);
            if (!$result) {
                $connection->rollback();
                return $this->sendAjax('评分失败!', false);
            }

            $field = array('avgStar' =>NEW Zend\Db\Sql\Predicate\Expression('ROUND(AVG(star), 1)'));
            //产品计算平均评分
            $avgStar = $modelUserGiveStar->select(array('productId' => $pid), $field);
            $edit['avgStar']    = $avgStar['avgStar'];
            $edit['updateTime'] = time();
            $modelProduct       = new Model_Cinema_Product();
            $result             = $modelProduct->update($edit, array('id' => $pid));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('统计用户评分失败!', false);
            }

            $connection->commit();
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('评分失败了!', false);
        }

        return $this->sendAjax('评分成功!');
    }

    /**
     * 用户上传头像
     *
     * @return
     **/
    public function avatarAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!isset($_SESSION['uid']) || !$_SESSION['uid']) {
            return $this->sendAjax('请先登录!', false);
        }
        //上传图片
        $uploadConfig = Bootstrap::$config['upload'];
        //循环建立目录
        if (!is_dir(PUB_PATH . '/' . trim($uploadConfig['avatar'], '/') . '/')) {
            try {
                Common::mkDirByPath(PUB_PATH. '/' . trim($uploadConfig['avatar'], '/') . '/', 0777);
            } catch (Exception $e) {
                return $this->sendAjax('建立目录失败!', false);
            }
        }

        $result = Common::uploadPic(PUB_PATH. '/'. trim($uploadConfig['avatar'], '/'). '/');
        if (!$result['result']) {
            return $this->sendAjax($result['msg'], false);
        }
        //更新用户表
        $modelUser = new Model_Cinema_User();
        $data      = array('avatar' => str_replace(PUB_PATH, '', $result['msg']));
        $result    = $modelUser->update($data, array('id' => $_SESSION['uid']));
        if (!$result) {
            return $this->sendAjax('上传头像失败!', false);
        }

        return $this->sendAjax('上传头像成功!');
    }

    /**
     * 字段检查
     *
     * @return
     **/
    private function _checkRegister($param = array())
    {
        if (is_array($param) && $param) {
            foreach ($param as $key => $value) {
                switch ($key) {
                    case 'userName':
                        if (!$value) {
                            return '用户名不能为空!';
                        }
                        break;
                    case 'passWord':
                        if (!$value) {
                            return '密码不能为空!';
                        }
                        break;
                    case 'email':
                        /*if (!$value) {
                            return '邮箱不能为空!';
                        }*/
                        break;
                    case 'phone':
                        /*if (!$value) {
                            return '手机号码不能为空!';
                        }*/
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        return true;
    }
          
}