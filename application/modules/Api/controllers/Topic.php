<?php
/**
* 专题系统
*
*/

include_once(__DIR__.'/Root.php');

class TopicController extends RootController
{
    //分类
    private $classityItems;
    //活动ID
    private $id;

    //专题信息
    private $topicItem;

    public function init()
    {
        parent::init();
        if (!in_array($this->_action, array('deleteapply', 'downloadapply', 'superintendent'))) {
            $id = $this->getIntRequest('id', 0);
            if (!$id) {
                $this->_error('未知的专题!');
            }

            //查看活动是否存在或已经下线
            $topicInfo  = new Model_Topic_TopicInfo();
            $this->topicItem = $topicInfo->getItemById($id);
            if (!$this->topicItem) {
                $this->_error('该专题不存在!');
            }
            //专题下线
            if ($this->topicItem['onlineFlag'] == 2) {
                return $this->getRequest()->isXmlHttpRequest() ? $this->sendAjax('该专题已经下线!', false) : $this->_error('该专题已经下线!');
            }

            $this->id = $id;
            //读取专题分类
            $classity = new Model_Topic_TopicClassify();
            $this->classityItems = $classity->selectWith(
                                                         array('TopicInfo_id' => $id, 'parent' => 0, 'titleFlag' => 0),
                                                         array('id', 'name', 'description', 'applyFlag')
                                              );
            $this->getView()->assign(array('classityItems' => $this->classityItems, 'id' => $id, 'item' => $this->topicItem));
        }
    }

    /**
     * 首页
     *
     * @return
     **/
    public function indexAction()
    {
        $this->pageTitle = '活动首页';
        $applyInfo  = new Model_Topic_TopicApplyInfo();
        $totalItems = $applyInfo->selectWith(
                                             array('TopicInfo_id' => $this->id, 'delFlag' => 0), 
                                             array('total' => New Zend\Db\Sql\Predicate\Expression('COUNT(true)'), 'TopicClassify_id'), 
                                             array(), 
                                             'TopicClassify_id'
                                   );
        $totalItems = Common::convertArrBycolumn($totalItems, 'TopicClassify_id');
        $this->getView()->assign(array('totalItems' => $totalItems));
    }

    /**
     * 报名
     *
     * @return
     **/
    public function applyAction()
    {
        $this->pageTitle = '活动报名';
        $userId = Common::getSession('USERID');
        if (!$userId) {
            $this->_error('请先登录!', false);
        }

        //查找专题对应的表单ID
        $cid = $this->getIntQuery('cid', 0);
        if ($this->classityItems && !$cid) {
            return $this->_error('请先选择活动分类!');    
        }

        $relationItem = array();
        //活动分类和form表单
        $relation     = new Model_Topic_TopicAndAftRelation();
        $relationItem = $relation->select(array('TopicInfo_id' => $this->id, 'TopicClassify_id' => $cid), array('TopicApplyFormTemplet_id'));
        if ($relationItem && !(int)$relationItem['TopicApplyFormTemplet_id']) {
            $this->_error('报名表单配对出错,请联系客服,谢谢!');
        }
        //获取分类信息
        $classity     = new Model_Topic_TopicClassify();
        $classityItem = $classity->select(array('id' => $cid),array('id', 'name', 'applyFlag'));
        if (!$classityItem) {
            $this->_error('分类不存在!');
        }

        if (!$classityItem['applyFlag']) {
            $this->_error('该分类下面的报名活动已经结束!');
        }

        if (!$relationItem && !(int)$this->topicItem['TopicApplyFormTemplet_id']) {
            $this->_error('未找到对应的报名表单,请联系客服,谢谢!');
        }
        //查找报名表单
        $formId      = $relationItem ? $relationItem['TopicApplyFormTemplet_id'] : $this->topicItem['TopicApplyFormTemplet_id'];
        $templet     = new Model_Topic_TopicApplyFormTemplet();
        $templetItem = $templet->getItemById($formId);
        if (!$templetItem) {
            $this->_error('报名表单不存在,请联系客服,谢谢!');    
        }
        //设定使用的表单ID
        $_SESSION['templetId'] = $formId;
        //获取学校信息
        $schoolInfo = YbApiClient::factory('School')->getUserSchool($userId);
        if (!$schoolInfo['status'] || !isset($schoolInfo['data'][$userId]) || !$schoolInfo['data'][$userId]['isSchoolVerify']) {
            $this->_error('您还没有通过校方认证,不能报名!');    
        }

        $this->getView()->assign(array(
                                        'templetItem'  => $templetItem,
                                        'cid'          => $cid,
                                        'schoolName'   => $schoolInfo['data'][$userId]['school_name'],
                                        'classityItem' => isset($classityItem) ? $classityItem : array()
                                      )
                                );
    }

    /**
     * 报名处理
     *
     * @return
     **/
    public function doApplyAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }
        //判断是否登录
        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录!', false);
        }

        //查找使用的报名表单
        //$templetId = Common::getSession('templetId');
        $templetId = $this->getIntPost('fid', 0);
        if (!$templetId) {
           return $this->sendAjax('报名表单丢失,请联系客服,谢谢!', false); 
        }
        $templet     = new Model_Topic_TopicApplyFormTemplet();
        $templetItem = $templet->getItemById($templetId);
        if (!$templetItem) {
            return $this->sendAjax('报名表单不存在,请联系客服,谢谢!', false);    
        }

        $formItems = json_decode($templetItem['content']);
        if (!$formItems || !is_array($formItems)) {
            return $this->sendAjax('报名表单出错,请联系客服,谢谢!', false);
        }
        //获取学校信息
        $schoolInfo = YbApiClient::factory('School')->getUserSchool($userId);
        if (!$schoolInfo['status'] || !isset($schoolInfo['data'][$userId]) || !$schoolInfo['data'][$userId]['isSchoolVerify']) {
            return $this->sendAjax('您还没有通过校方认证, 不能报名!', false);
        }

        //预先为提交数据赋值
        $data['userId']           = $userId;
        $data['TopicInfo_id']     = $this->id;
        $data['TopicClassify_id'] = $this->getIntPost('cid', 0);
        $data['schoolId']         = $schoolInfo['data'][$userId]['school_id'];
        $data['schoolName']       = $schoolInfo['data'][$userId]['school_name'] ? $schoolInfo['data'][$userId]['school_name'] : '';
        $data['addTime']          = time();
        $data['content']          = array();

        /****************开始获取form值**********************/
        foreach ($formItems as $key => $value) {
            $value = (array)$value;
            if (!isset($value['ele']) && isset($value['elements'])) {//为表TopicApplyMultiInfo组织值
                //获取学生姓名
                $studentNames = $this->getRequest()->getPost(trim($value['elements'][0]->name, '[]'), false);
                //获取学生学籍号码
                $studentCards = $this->getRequest()->getPost(trim($value['elements'][1]->name, '[]'), false);
                //将参加学校信息插入数据库中
                if (!$studentCards || !$studentNames || !is_array($studentNames) || !is_array($studentCards) || count($studentCards) != count($studentNames)) {
                    return $this->sendAjax('学生的学籍卡信息有误!', false);
                }
            } else {
                if (!$this->getTrimedPost($value['name'], '') && $value['must']) {
                    return $this->sendAjax('请正确填写'.$value['title'], false);
                }

                if (in_array($value['name'], $templet->getFormHasColumnInDatabase())) {
                    $data[$value['name']] = $this->getTrimedPost($value['name'], '');
                } else {
                    $data['content'][$value['name']] = $value['ele'] == 'fileupload' 
                                                            ? array('type' => 'fileupload', 'val' => $this->getTrimedPost($value['name'], ''))
                                                                : $this->getTrimedPost($value['name'], '');
                }
            }
        }
        /****************获取form值 END**********************/

        $data['content']    = $data['content'] ? json_encode($data['content']) : '';
        //生成作品编码
        $data['identifier'] = 'HD'.date('YmdHis').$userId;

        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        try {
            //事务开启
            $connection = $topicApplyInfo->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            //插入表
            $result = $topicApplyInfo->save($data);
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('报名失败,请稍后重试!', false);
            }

            if (isset($studentNames) && $studentNames) {
                $studentData = array();
                foreach ($studentNames as $key => $value) {
                    $tmp   = array();
                    $tmp[] = $result;
                    $tmp[] = $value;
                    $tmp[] = $studentCards[$key];
                    array_push($studentData, $tmp);
                }
                
                $multiInfo = new Model_Topic_TopicApplyMultiInfo();
                $result    = $multiInfo->insertMore(array('TopicApplyInfo_id', 'name', 'schoolCardId'), $studentData);
                if ($result === false) {
                    $connection->rollback();
                    return $this->sendAjax('报名失败了,请稍后重试!', false);
                }
            }
            //提交数据
            $connection->commit();
            return $this->sendAjax(array('msg' => '报名成功!', 'num' => $data['identifier'], 'id' => $this->id, 'cid' => $data['TopicClassify_id']));
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('修改失败!', false);
        }
    }

    /**
     * 我的报名信息
     *
     * @return
     **/
    public function applySuccessAction()
    {
        $num = $this->getTrimedQuery('num', '');
        $this->getView()->assign(array('num'  => $num));
    }

    /**
     * 我的报名信息
     *
     * @return
     **/
    public function myApplyInfoAction()
    {
        $userId = Common::getSession('USERID');
        if (!$userId) {
            $this->_error('请先登录,谢谢!');
        }

        //获取学校信息
        $schoolInfo = YbApiClient::factory('School')->getUserSchool($userId);
        if (!$schoolInfo['status'] || !isset($schoolInfo['data'][$userId]) || !$schoolInfo['data'][$userId]['isSchoolVerify']) {
            $this->_error('您还没有通过校方认证, 不能进入此页面!');
        }

        //分页
        $page = $this->getIntQuery('page', 1);
        $conditions['getCount'] = true;
        $conditions['limit']    = self::LIMIT_DEFAULT;
        $conditions['offset']   = ($page - 1) * self::LIMIT_DEFAULT;
        $conditions['where']    = array('userId' => $userId, 'delFlag' => 0);

        $topicApplyInfo      = new Model_Topic_TopicApplyInfo();
        $topicApplyInfoItems = $topicApplyInfo->getList($conditions);
        if ($topicApplyInfoItems['list']) {
            //获取申请ID
            $applyInfoIdItems = array();
            $classityIds      = array();
            foreach ($topicApplyInfoItems['list'] as $key => $value) {
                $applyInfoIdItems[] = $value['id'];
                $classityIds[]      = $value['TopicClassify_id'];
            }

            //分类
            $classity = new Model_Topic_TopicClassify();
            $classityList = $classity->selectWith(
                                                    array('id' => $classityIds),
                                                    array('id', 'name')
                                                 );
            $param['classityList'] = Common::convertArrBycolumn($classityList, 'id');

            //作者信息
            $param['opusList'] = array();
            $multiInfo = new Model_Topic_TopicApplyMultiInfo();
            $opusItems = $multiInfo->selectWith(array('TopicApplyInfo_id' => $applyInfoIdItems));
            if ($opusItems) {
                foreach ($opusItems as $key => $value) {
                    $param['opusList'][$value['TopicApplyInfo_id']][] = $value['name'];
                }
            }
        }

        $TopicSchoolInfo = new Model_Topic_TopicSchoolInfo();
        $schoolInfoItem  = $TopicSchoolInfo->select(array('schoolId' => $schoolInfo['data'][$userId]['school_id']));

        $param['schoolInfoItem'] = $schoolInfoItem;
        $param['list']           = $topicApplyInfoItems['list'];
        $param['pageList']       = parent::pageBar($topicApplyInfoItems['count'], self::LIMIT_DEFAULT);
        $this->getView()->assign($param);
    }

    /**
     * 处理学校负责人信息
     *
     * @return
     **/
    public function superintendentAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }
        
        //获取学校信息
        $schoolInfo = YbApiClient::factory('School')->getUserSchool($userId);
        if (!$schoolInfo['status'] || !isset($schoolInfo['data'][$userId]) || !$schoolInfo['data'][$userId]['isSchoolVerify']) {
            return $this->sendAjax('您还没有通过校方认证, 不能执行此操作!', false);
        }

        //查找信息是否已经存在
        $TopicSchoolInfo = new Model_Topic_TopicSchoolInfo();
        $schoolInfoItem  = $TopicSchoolInfo->select(array('schoolId' => $schoolInfo['data'][$userId]['school_id']));

        //获取值
        $name    = $this->getTrimedPost('name', '');
        $tel     = $this->getIntPost('tel', 0);
        $summary = $this->getTrimedPost('summary', '');
        if (!$name) {
            return $this->sendAjax('请填写负责人姓名!', false);    
        }

        if (!$tel || !Common::isPhone($tel)) {
            return $this->sendAjax('请正确填写负责人手机号码!', false);
        }
        
        $set['superintendentName'] = $name;
        $set['superintendentTel']  = $tel;
        $set['summaryPath']        = $summary;
        if ($schoolInfoItem) { //更新
            $set['updateTime'] = time();
            $result = $TopicSchoolInfo->update($set, array('schoolId' => $schoolInfo['data'][$userId]['school_id']));
        } else {
            $set['schoolId']   = $schoolInfo['data'][$userId]['school_id'];
            $set['schoolName'] = $schoolInfo['data'][$userId]['school_name'];
            $set['updateTime'] = time();
            $set['addTime']    = time();
            $result = $TopicSchoolInfo->insert($set);
        }

        if ($result === false) {
            return $this->sendAjax('更新信息失败!', false);
        }
        return $this->sendAjax('更新信息成功!');
    }

    /**
     * 删除我的报名信息
     *
     * @return
     **/
    public function deleteApplyAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }
        //申请ID
        $id = $this->getIntQuery('id', 0);
        //验证申请ID是否是登录用户的
        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $applyItem      = $topicApplyInfo->getItemById($id, array('userId', 'TopicInfo_id', 'status'));
        if (!$applyItem || $applyItem['userId'] != $userId) {
            return $this->sendAjax('非法的删除操作!', false);
        }

        if ($applyItem['status'] != 0) {
            return $this->sendAjax('已经评选过的作品,不能删除!', false);
        }
        //更新删除状态
        $result = $topicApplyInfo->update(array('delFlag' => 1), array('id' => $id));
        if ($result === false) {
            return $this->sendAjax('删除失败!', false);
        }

        return $this->sendAjax('删除成功!');
    }

    /**
     * 下载我的报名作品
     *
     * @return
     **/
    public function downloadApplyAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $userId = Common::getSession('USERID');
        if (!$userId) {
            $this->_error('请先登录,谢谢!');
        }
        //申请ID
        $id = $this->getIntQuery('id', 0);
        //验证申请ID是否是登录用户的
        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $applyItem      = $topicApplyInfo->getItemById($id, array('userId', 'TopicInfo_id', 'content', 'opusName', 'identifier'));
        if (!$applyItem || $applyItem['userId'] != $userId) {
            $this->_error('非法的删除操作!');
        }

        //根据conent获取下载路径
        $downloadPath = '';
        $content = (array)json_decode($applyItem['content']);
        if ($content && is_array($content)) {
            foreach ($content as $key => $value) {
                if (is_object($value) && property_exists($value, 'type') && $value->type == 'fileupload'&& property_exists($value, 'val') && $value->val) {
                    //$downloadPath = Bootstrap::$config['uploadRoot'] . $value->val;
                    //由于作品文件都放在了YDFS文件系统上了,所以用下面的下载
                    $downloadPath = Bootstrap::$config['YDFSApi']['apiHost'] . $value->val;
                }
            }
        }
        
        if (!$downloadPath || !@fopen($downloadPath, 'r')) {
            $this->_error('未找到要下载的作品!');
        }

        //清除缓存  
        header("Pragma: no-cache");  
        //设置过期时间  
        header("Expires: 0");  
        header("Cache-Component: must-revalidate, post-check=0, pre-check=0");  
        //设置下载的字符集  
        header("Content-type:application/octet-stream;charset=utf-8");
        $name = $applyItem['opusName'].'-'.$applyItem['identifier'].(substr($downloadPath, strrpos($downloadPath, '.')));
        //用以解决中文不能显示出来的问题
        $name = iconv("utf-8","gb2312//TRANSLIT//IGNORE",$name);
        $fp   = fopen($downloadPath,"rb");
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Content-Disposition: attachment; filename=".$name);
        $buffer = 1024;
        //向浏览器返回数据
        while (!feof($fp)) {
            $file_con=fread($fp,$buffer);
            echo $file_con;
        }

        fclose($fp);
    }

    /**
      * some
      *
      * @param 
      * @param 
      * @param
      * @return
      **/
     public function downloadAction()
     {
        $userId = Common::getSession('USERID');
        //下载
        header("Content-type:text/html;charset=utf-8");
        //用以解决中文不能显示出来的问题
        $name     = iconv("utf-8", "gb2312", '学生作品信息-' . date('Y-m-d-H:i:s') . '.zip');
        $fp       = fopen(Bootstrap::$config['YDFSApi']['apiHost'] . '/' . Bootstrap::$config['YDFSApi']['app'] . '/' . $userId . '/upload/files/opus/1882072864568cb6287f211.zip' ,"rb");
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        header('Cache-Control: max-age=0');
        Header("Accept-Ranges: bytes");
        Header("Content-Disposition: attachment; filename=".$name);
        $buffer    = 1024;
        //向浏览器返回数据
        while (!feof($fp)) {
            $file_con=fread($fp,$buffer);
            echo $file_con;
        }
        fclose($fp);
     }
          
}