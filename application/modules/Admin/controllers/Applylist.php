<?php
/**
* 后台申请列表
*/

include_once(__DIR__.'/Root.php');

class ApplylistController extends RootController
{
    //查看所有分类
    private static $super = array(
        '7339319' => 0,
        '1441662' => 0,
        '1467804' => 0
    );//文明风采管理员
    
    //查看指定分类
    private static $general = array(
        '7339345' => 1,//征文演讲类专家
        '7339357' => 2,//职业规划类专家
        '7339379' => 3,//摄影类专家
        '7339393' => 4,//微视频类专家
        '7339399' => 5,//校园情景剧类专家
        '7339409' => 6,//舞台类专家
        '7339421' => 7 //非舞台类专家
    );
    
    //导出数据, 设定不同分类多导出不同数据,第一层key为活动id, 第二层key为分类id
    private static $loadByType = array(
        1 => array(
                1 => array('主题'),
                2 => array('二级项目'),
                4 => array('分类', '作品说明'),
                6 => array('分类'),
                7 => array('作品说明')
             )
    );
    
    /**
     * 后台表单模板制作首页
     * 报名表列表
     *
     * @return void
     **/
    public function indexAction()
    {

        /**********************************获取参数******************************/
        //分页
        $page       = $this->getIntQuery('page', 1);
        $authorName = $this->getTrimedQuery('name', '');//作者姓名
        $identifier = $this->getTrimedQuery('identifier', ''); //作品编号
        $start      = $this->getTrimedQuery('start', ''); //发布日期最小时间
        $end        = $this->getTrimedQuery('end', ''); //发布日期最大时间
        $opusName   = $this->getTrimedQuery('opusName', ''); //作品名称
        $schoolName = $this->getTrimedQuery('schoolName', ''); //学校名称
        $activeName = $this->getIntQuery('activeName', ''); //活动名称
        $type       = $this->getIntQuery('type', ''); //类别
        $prize      = $this->getTrimedQuery('prize', ''); //奖励
        
        //指定帐号获取指定分类
        $userId = Common::getSession('USERID');
        $type   = array_key_exists($userId, self::$general) ? self::$general[$userId] : $type;
        $param['selectType']  = $_SESSION['adminFlag'.$userId] == 4 ? true : array_key_exists($userId, self::$super);

        $param['name']       = $authorName;
        $param['identifier'] = $identifier;
        $param['start']      = $start;
        $param['end']        = $end;
        $param['opusName']   = $opusName;
        $param['schoolName'] = $schoolName;
        $param['activeName'] = $activeName;
        $param['type']       = $type;
        $param['prize']      = $prize;
        /*********************************获取参数 END***************************/

        $topicInfo  = new Model_Topic_TopicInfo();
        $topicItems = $topicInfo->selectWith(array(), array('id', 'name'));
        //查找类别
        $classity      = new Model_Topic_TopicClassify();
        $classityItems = array();
        if ($topicItems) {
            $topicIds = Common::getColumnArray($topicItems, 'id');
            $classityItems = $classity->selectWith(
                                                    array('TopicInfo_id' => $topicIds, 'parent' => 0, 'titleFlag' => 0),
                                                    array('id', 'name', 'TopicInfo_id')
                                                  );
            //将分类按照专题分组
            $classityRowsOfIds = Common::convertToOneArrayBycolumn($classityItems, 'TopicInfo_id');
            //分类结合到专题中
            $tmpTopic = array();
            foreach ($topicItems as $key => $value) {
                $value['classityItems'] = array();
                if (isset($classityRowsOfIds[$value['id']])) {
                    $value['classityItems'] = $classityRowsOfIds[$value['id']];
                }
                $tmpTopic[$value['id']] = $value;
            }
            $topicItems = $tmpTopic;
        }

        $multiInfo = new Model_Topic_TopicApplyMultiInfo();
        //查询条件
        if ($authorName) {//查找作者姓名
            $authorItems = $multiInfo->selectWith(array('name' => $authorName));
            if ($authorItems) {
                $where['id'] = Common::getColumnArray($authorItems, 'TopicApplyInfo_id');
            }
        }
        
        $topicApplyInfo       = new Model_Topic_TopicApplyInfo();
        $param['schoolPrize'] = $topicApplyInfo->getSchoolPrize();
        $param['topicItems']  = $topicItems;
        $param['list']        = array();
        $param['pageList']    = '';
        $param['shortList']   = '';
        if (!$authorName || (isset($where['id']) && $where['id'])) {

            if ($identifier) {
                $where['identifier'] = $identifier;
            }

            if ($start || $end) {
                $where['addTime'] = $start && $end ? array('>=<' => array(strtotime($start.' 00:00:00'), strtotime($end.' 23:59:59'))) 
                                                                 : ($start ? array('>=' => strtotime($start.' 00:00:00')) 
                                                                           : array('<=' => strtotime($end.' 00:00:00'))) ;
            }

            if ($opusName) {
                $where['opusName'] = $opusName;    
            }
            
            if ($schoolName) {
                $where['schoolName'] = $schoolName;    
            }

            if ($activeName) {
                $where['TopicInfo_id'] = $activeName;
            }

            if ($type) {
                $where['TopicClassify_id'] = $type;
            }
            
            if ($prize) {
                $where['schoolPrize'] = $prize;
            }

            //如果是学校用户登录
            $userId = Common::getSession('USERID');
            if ($_SESSION['adminFlag'.$userId] == 4) {
                $schoolInfo = YbApiClient::factory('School')->getUserSchool($userId);
                if (!$schoolInfo['status'] || !isset($schoolInfo['data'][$userId]) || !$schoolInfo['data'][$userId]['isSchoolVerify']) {
                    $this->_error('您还没有通过校方认证,请先通过校方认证!');    
                }
                $where['schoolId'] = $schoolInfo['data'][$userId]['school_id'];
            }

            $where['delFlag'] = 0;
            $conditions['getCount'] = true;
            $conditions['limit']    = self::LIMIT_DEFAULT;
            $conditions['offset']   = ($page - 1) * self::LIMIT_DEFAULT;
            $conditions['where']    = $where;
            $topicApplyInfoItems    = $topicApplyInfo->getList($conditions);

            if ($topicApplyInfoItems['list']) {
                //获取申请ID
                $applyInfoIds = array();
                $classityIds  = array();
                foreach ($topicApplyInfoItems['list'] as $key => $value) {
                    $applyInfoIds[] = $value['id'];
                    if ($value['TopicClassify_id']) {
                        $classityIds[]  = $value['TopicClassify_id'];
                    }
                }

                //分类
                $param['classityList'] = array();
                if ($classityIds) {
                    $applyClassityItems = $classity->selectWith(
                                                                array('id' => $classityIds),
                                                                array('id', 'name')
                                                               );
                    $param['classityList'] = Common::convertArrBycolumn($applyClassityItems, 'id');
                }

                //作者信息
                $param['opusList'] = array();
                $opusItems         = $multiInfo->selectWith(array('TopicApplyInfo_id' => $applyInfoIds));
                if ($opusItems) {
                    foreach ($opusItems as $key => $value) {
                        $param['opusList'][$value['TopicApplyInfo_id']][] = $value['name'];
                    }
                }
            }
            $param['list']      = $topicApplyInfoItems['list'];
            $param['pageList']  = parent::pageBar($topicApplyInfoItems['count'], self::LIMIT_DEFAULT);
            $shortPager         = new Pagination_Short($topicApplyInfoItems['count'], self::LIMIT_DEFAULT);
            $param['shortList'] = $shortPager->getPagination();
        }

        $this->getView()->assign($param);
    }

    /**
     * 下载作品,包括批量和单个
     *
     * @return void
     **/
    public function downloadOpusAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        //设置程序执行时间不限制
        set_time_limit(0);
        ini_set("memory_limit","-1");

        $ids = $this->getRequest()->getQuery('id', '');
        if (!$ids) {
            $this->_error('参数错误!', false);
        }

        $ids = explode(',', $ids);
        if (is_array($ids)) {
            foreach ($ids as &$value) {
                $value = (int) $value;
                if (!$value) {
                    $this->_error('参数出错了!', false);
                }
            }
            unset($value);
        }

        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $applyInfoItems = $topicApplyInfo->selectWith(array('id' => $ids));
        //获取下载文件列表
        $downloadItems  = array();
        if ($applyInfoItems) {
            $classityIds = array();
            //获取下载信息
            foreach ($applyInfoItems as $key => $value) {
                if ($value['content']) {
                    $content = (array)json_decode($value['content']);
                    if (is_array($content) && $content) {
                        $item = array();
                        foreach ($content as $k => $val) {
                            if (is_object($val) && property_exists($val, 'type') && $val->type == 'fileupload'&& property_exists($val, 'val') && $val->val) {
                                //$item['opus']    = Bootstrap::$config['uploadRoot'] . $val->val;
                                //由于作品文件都放在了YDFS文件系统上了,所以用下面的下载
                                $item['opus'] = Bootstrap::$config['YDFSApi']['apiHost'] . $val->val;
                            } elseif (!is_object($val)) {
                                $item['content'][$k] = $val;
                            }
                        }

                        if (isset($item['opus']) && $item['opus']) {
                            $item['data']    = array(
                                                        'opusName'         => $value['opusName'],
                                                        'identifier'       => $value['identifier'],
                                                        'TopicClassify_id' => $value['TopicClassify_id'],
                                                        'schoolName'       => $value['schoolName'],
                                                        'id'               => $value['id'],
                                                        'schoolPrize'      => $value['schoolPrize'],
                                                        'status'           => $value['status'],
                                                        'cityPrize'        => $value['cityPrize'],
                                                   );
                            $downloadItems[] = $item;
                            $classityIds[]   = $value['TopicClassify_id'];
                        }
                    }
                }
            }

            if ($downloadItems) {
                //获取作者信息
                $multiInfo      = new Model_Topic_TopicApplyMultiInfo();
                $multiInfoItems = $multiInfo->selectWith(array('TopicApplyInfo_id' => $ids));
                $multiInfoData = array();
                if ($multiInfoItems) {
                    foreach ($multiInfoItems as $key => $value) {
                        $multiInfoData[$value['TopicApplyInfo_id']][] = $value['name'] . '(' . $value['schoolCardId'] . ')';
                    }
                }

                //获取分类信息
                if ($classityIds) {
                    $classity      = new Model_Topic_TopicClassify();
                    $classityItems = $classity->selectWith(array('id' => $classityIds),array('id', 'name'));
                    $classityData  = Common::convertArrBycolumn($classityItems, 'id');
                }
                
                //获取评论
                $commentObj   = new Model_Topic_TopicApplyInfoComment();
                $commentItems = $commentObj->selectWith(array('TopicApplyInfo_id' => $ids));
                $commentData  = array();
                if ($commentItems) {
                    foreach ($commentItems as $key => $value) {
                        $commentData[$value['TopicApplyInfo_id']][] = '用户评论:' . $value['comment'];
                    }
                }
            }
        }

        if (!$downloadItems) {
            $this->_error('未找到要下载的作品!');
        }
        
        //生成临时文件夹,为生成excel和zip文件准备
        $tmpDir = Bootstrap::$config['uploadRoot'].Bootstrap::$config['upload']['opus'].'/'.date('YmdHis').Common::getSession('USERID');
        if (!is_dir($tmpDir)) {
            try {
                Common::mkDirByPath($tmpDir, 0777);
            } catch (Exception $e) {
                $this->sendAjax('建立目录失败!', false);
            }
        }
        //生成excel
        $zipItems = array();
        $fixField = array('作品名称', '作品编号', '类别');
        $userid = Common::getSession('USERID');
        if (!in_array($_SESSION['adminFlag'.$userid], array(3))) {
            $fixField = array_merge($fixField, array('学校名称', '作者姓名', '校内奖项'));
        }
        $fixField = array_merge($fixField, array('是否晋级', '市级奖项', '评语'));

        foreach ($downloadItems as $key => $value) {

            //将作品从YDFS文件服务器下载下来
            try{
                $opus = Common::curlGetContents($value['opus'], $tmpDir.substr($value['opus'], strrpos($value['opus'], '/')));
            } catch(Exception $e) {
                $this->_error('下载作品失败!');
            }

            //生成excel
            $head         = isset($value['content']) ? array_merge($fixField, array_keys($value['content'])) : $fixField;
            $tmpExcelPath = $tmpDir. '/' . $value['data']['opusName'] . '-' . $value['data']['identifier'] . '.csv';
            $fp           = fopen($tmpExcelPath, 'w+');
            //改变编码
            $head = $this->_setCharToGbkForExcel($head);
            /*foreach ($head as &$val) {
                $val = iconv('utf-8', 'gbk', $val);
            }
            unset($val);*/
            
            // 写入列头
            $result = fputcsv($fp, $head);
            if ($result === false) {
                $this->_error('下载出错!');
            }

            //写入内容
            $row = array();
            array_push($row, str_replace("\"", "\"\"", $value['data']['opusName']));
            array_push($row, str_replace("\"", "\"\"", $value['data']['identifier']));
            array_push($row,
                       isset($classityData[$value['data']['TopicClassify_id']]) ?  str_replace("\"", "\"\"", $classityData[$value['data']['TopicClassify_id']]['name']) : ''
                      );//类别
            if (!in_array($_SESSION['adminFlag'.$userid], array(3))) {
                array_push($row, str_replace("\"", "\"\"", $value['data']['schoolName']));
                array_push($row,
                           isset($multiInfoData[$value['data']['id']]) ?  str_replace("\"", "\"\"", join("\r\n", $multiInfoData[$value['data']['id']])) : ''
                          );//作者姓名
                array_push($row, str_replace("\"", "\"\"", $value['data']['schoolPrize'])); //校内奖项
            }
            array_push($row,  $value['data']['status'] == 1 
                                                        ? '晋级' 
                                                        : ($value['data']['status'] == 2 
                                                                ? '不晋级'
                                                                : '评选中')); //是否晋级
            $cityPrize = array(1 => '一等奖', 2 => '二等奖', 3 => '三等奖');
            //市级奖项
            array_push($row,  isset($cityPrize[$value['data']['cityPrize']]) ? $cityPrize[$value['data']['cityPrize']] : '无');
            //评语
            array_push($row,
                       isset($commentData[$value['data']['id']]) ?  str_replace("\"", "\"\"", join("\r\n", $commentData[$value['data']['id']])) : ''
                      );//评语
            if (isset($value['content']) && $value['content'] && is_array($value['content'])) { //自定义部分
                foreach ($value['content'] as $val) {
                    array_push($row, str_replace("\"", "\"\"", $val));
                }
            }
            //改变编码
            $row = $this->_setCharToGbkForExcel($row);
            $result = fputcsv($fp, $row);
            fclose($fp);
            if ($result === false) {
                $this->_error('下载出错了!');
            }
            
            $zipItems[$value['data']['opusName'] . '-' . $value['data']['identifier']] = array($value['opus'], $tmpExcelPath);
        }

        if (!$zipItems) {
            $this->_error('未找到要下载的作品信息!');
        }

        //开始压缩
        $zip    = new ZipArchive();
        $tmpZip = array();
        //将作品和作品信息压缩在一起
        foreach ($zipItems as $key => $value) {
            if (is_array($value) && $value) {
                $zip->open($tmpDir . '/' . $key.'.zip', ZipArchive::OVERWRITE);
                foreach ($value as $k => $val) {
                    //改变编码
                    $fileName = $this->_setCharToGbkForExcel(substr($val, strrpos($val, '/')+1));
                    $zip->addFile($val, $fileName);
                }
                $zip->close();
                $tmpZip[] = $tmpDir . '/' . $key.'.zip';
            }
        }

        if (!$tmpZip) {
            $this->_error('未找到学生的作品!');
        }
        //将所有zip文件压在一起
        $zip->open($tmpDir . '/学生作品信息.zip', ZipArchive::OVERWRITE);
        foreach ($tmpZip as $value) {
            //改变编码
            $fileName = $this->_setCharToGbkForExcel(substr($value, strrpos($value, '/')+1));
            $zip->addFile($value, $fileName);
        }
        $zip->close();

        //下载
        header("Content-type:text/html;charset=utf-8");
        //改变编码,用以解决中文不能显示出来的问题
        $name     = $this->_setCharToGbkForExcel('学生作品信息-' . date('Y-m-d-H:i:s') . '.zip');
        $fp       = fopen($tmpDir . '/学生作品信息.zip',"rb");
        $fileSize = filesize($tmpDir . '/学生作品信息.zip');
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$fileSize);
        Header("Content-Disposition: attachment; filename=".$name);
        $buffer    = 1024;
        $fileCount = 0;
        //向浏览器返回数据
        while (!feof($fp) && $fileCount<$fileSize) {
            $file_con=fread($fp,$buffer);
            $fileCount+=$buffer;
            echo $file_con;
            //flush(); //输出缓冲 
            //ob_flush();
        }
        fclose($fp);

        //删除临时的文件tmpDir
        if ($handle = opendir($tmpDir)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    unlink($tmpDir . '/' . $item);
                }
            }
            closedir($handle);
            rmdir($tmpDir);
        }
    }

    /**
     * 单个下载作品
     *
     * @return
     **/
    public function downloadApplyAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        //设置程序执行时间不限制
        set_time_limit(0);
        ini_set("memory_limit","-1");

        $userId = Common::getSession('USERID');
        if (!$userId) {
            $this->_error('请先登录,谢谢!');
        }
        //申请ID
        $id = $this->getIntQuery('id', 0);
        //验证申请ID是否是登录用户的
        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $applyItem      = $topicApplyInfo->getItemById($id, array('userId', 'TopicInfo_id', 'content', 'opusName', 'identifier'));

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
        $name = $this->_setCharToGbkForExcel($name);
        $fp   = @fopen($downloadPath,"rb");
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
     * 导出数据,包括批量和单个
     *
     * @return void
     **/
    public function downloadDataAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $userId = Common::getSession('USERID');
        if (!$userId) {
            $this->_error('请先登录,谢谢!');
        }

        $authorName = $this->getTrimedQuery('name', '');//作者姓名
        $identifier = $this->getTrimedQuery('identifier', ''); //作品编号
        $start      = $this->getTrimedQuery('start', ''); //发布日期最小时间
        $end        = $this->getTrimedQuery('end', ''); //发布日期最大时间
        $opusName   = $this->getTrimedQuery('opusName', ''); //作品名称
        $schoolName = $this->getTrimedQuery('schoolName', ''); //学校名称
        $activeName = array_key_exists($userId, self::$general) ? 1 : $this->getIntQuery('activeName', ''); //活动名称
        $type       = array_key_exists($userId, self::$general) ? self::$general[$userId] : $this->getIntQuery('type', 0); //类别
        $prize      = $this->getTrimedQuery('prize', ''); //奖励

        $multiInfo = new Model_Topic_TopicApplyMultiInfo();
        //查询条件
        if ($authorName) {//查找作者姓名
            $authorItems = $multiInfo->selectWith(array('name' => $authorName));
            if ($authorItems) {
                $where['id'] = Common::getColumnArray($authorItems, 'TopicApplyInfo_id');
            }
        }

        if ($authorName && (!isset($where['id']) || !$where['id'])) {
            $this->_error('未找到要下载的数据!');
        }

        if ($identifier) {
            $where['identifier'] = $identifier;
        }

        if ($start || $end) {
            $where['addTime'] = $start && $end ? array('>=<' => array(strtotime($start.' 00:00:00'), strtotime($end.' 23:59:59'))) 
                                                             : ($start ? array('>=' => strtotime($start.' 00:00:00')) 
                                                                       : array('<=' => strtotime($end.' 00:00:00'))) ;
        }

        if ($opusName) {
            $where['opusName'] = $opusName;    
        }
        
        if ($schoolName) {
            $where['schoolName'] = $schoolName;    
        }

        if ($activeName) {
            $where['TopicInfo_id'] = $activeName;
        }

        if ($type) {
            $where['TopicClassify_id'] = $type;    
        }

        if ($prize) {
            $where['schoolPrize'] = $prize;
        }

        $topicApplyInfo      = new Model_Topic_TopicApplyInfo();
        $where['delFlag']    = 0;
        $conditions['where'] = $where;
        $applyInfoItems      = $topicApplyInfo->getList($conditions);
        if (!$applyInfoItems['list']) {
            $this->_error('未曾找到要下载的数据!');
        }

        $classity      = new Model_Topic_TopicClassify();
        $classityItems = $classity->selectWith(array('parent' => 0),array('id', 'name'));
        $classityData  = Common::convertArrBycolumn($classityItems, 'id');

        //获取下载信息
        $i      = 0;
        $keys   = array_keys($applyInfoItems['list']);
        $endKey = end($keys);
        $multiInfoData = array(); //作者姓名
        foreach ($applyInfoItems['list'] as $key => $value) {
            $item[] = array(
                            'opusName'         => $value['opusName'],
                            'identifier'       => $value['identifier'],
                            'TopicClassify_id' => $value['TopicClassify_id'],
                            'schoolName'       => $value['schoolName'],
                            'id'               => $value['id'],
                            'schoolPrize'      => $value['schoolPrize'],
                            'status'           => $value['status'],
                            'cityPrize'        => $value['cityPrize'],
                            'tutorName'        => $value['tutorName'],
                            'tutorPhone'       => $value['tutorPhone'],
                            'content'          => $value['content']
                         );
            $applyInfoIds[] = $value['id'];
            $i++;

            //每达到200个搜一下作者姓名
            if ($key == $endKey || $i >= 199) {
                $multiInfoItems = $multiInfo->selectWith(array('TopicApplyInfo_id' => $applyInfoIds));
                if ($multiInfoItems) {
                    foreach ($multiInfoItems as $key => $value) {
                        $multiInfoData[$value['TopicApplyInfo_id']]['name'][]         = $value['name'];
                        $multiInfoData[$value['TopicApplyInfo_id']]['schoolCardId'][] = $value['schoolCardId'];
                    }
                }
                //重新初始化
                $i = 0;
                $applyInfoIds = array();
            }
        }

        $format = $this->getIntQuery('format', 1);
        header('Content-Type: application/vnd.ms-excel;charset=gbk');
        header('Content-Disposition: attachment;filename="' . $this->_setCharToGbkForExcel('活动作品数据-' . date('Y-m-d H:i:s')).'.csv"');
        header('Cache-Control: max-age=0');

        $fp     = fopen('php://output', 'a');
        
        $head   = $format == 2 ? array('序号', '作品名称', '作者姓名', '学校名称', '指导老师', '指导老师手机', '复赛获奖情况', '校内奖项') //格式B
                               : array('类别', '作品名称', '作者姓名', '学校名称', '指导老师', '指导老师手机', '复赛获奖情况', '全国决赛获奖等级', '学生学籍号', '校内奖项');//格式A
        //加入自定义数据显示
        if ($type && $activeName && isset(self::$loadByType[$activeName]) && isset(self::$loadByType[$activeName][$type])) {
            $head = array_merge($head, self::$loadByType[$activeName][$type]);
        }
        //将utf-8转为gbk,用以解决中文不能显示出来的问题
        $head = $this->_setCharToGbkForExcel($head);

        $result = fputcsv($fp, $head);
        if ($result === false) {
            $this->_error('下载出错!');
        }
        
        foreach ($item as $key => $value) {
            //写入内容
            $row = array();
            if ($format == 2) {
                array_push($row, str_replace("\"", "\"\"", $value['identifier']));
            } else {
                array_push($row,
                       isset($classityData[$value['TopicClassify_id']]) ?  str_replace("\"", "\"\"", $classityData[$value['TopicClassify_id']]['name']) : ''
                      );//类别
            }
            array_push($row, str_replace("\"", "\"\"", $value['opusName']));
            array_push($row,
                       isset($multiInfoData[$value['id']]) ? str_replace("\"", "\"\"", join("\r\n", $multiInfoData[$value['id']]['name'])) : ''
                      );//作者姓名
            array_push($row,  str_replace("\"", "\"\"", $value['schoolName']));
            array_push($row,  str_replace("\"", "\"\"", $value['tutorName'])); //指导老师
            array_push($row,  "\t" . str_replace("\"", "\"\"", $value['tutorPhone'])); //指导老师手机
            array_push($row, ''); //复赛获奖情况
            if ($format == 1) {
                array_push($row, ''); //全国决赛获奖等级
                array_push($row, 
                           isset($multiInfoData[$value['id']]) ? str_replace("\"", "\"\"", "\t" . join("\r\n", $multiInfoData[$value['id']]['schoolCardId'])) : ''
                ); //学生学籍号
            }
            array_push($row, str_replace("\"", "\"\"", $value['schoolPrize'])); //复赛获奖情况
            
            //加入自定义数据显示
            if ($type && $activeName && isset(self::$loadByType[$activeName]) && isset(self::$loadByType[$activeName][$type])) {
                $content = (array)json_decode($value['content']);
                foreach (self::$loadByType[$activeName][$type] as $k => $val) {
                    $add = isset($content[$val]) ? $content[$val] : '';
                    array_push($row, str_replace("\"", "\"\"", $add));
                }
            }
            //将utf-8转为gbk,用以解决中文不能显示出来的问题
            $row = $this->_setCharToGbkForExcel($row);
            $result = fputcsv($fp, $row);
            if ($result === false) {
                $this->_error('下载出错了!');
            }
        }

        fclose($fp);
        die();
    }

    /**
     * 导出负责人,包括批量和单个
     *
     * @return void
     **/
    public function downloadLeaderAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();

        $TopicSchoolInfo = new Model_Topic_TopicSchoolInfo();
        $schoolInfoItems = $TopicSchoolInfo->selectWith();
        if (!$schoolInfoItems) {
            $this->_error('未找到相关负责人信息!');
        }
        header('Content-Type: application/vnd.ms-excel;charset=gbk');
        header('Content-Disposition: attachment;filename="'.$this->_setCharToGbkForExcel('学校专题负责人信息-' . date('Y-m-d H:i:s')).'.csv"');
        header('Cache-Control: max-age=0');

        $fp     = fopen('php://output', 'a');
        $head   = array('学校名称', '负责人姓名', '负责人手机号码');
        //将utf-8转为gbk,用以解决中文不能显示出来的问题
        $head = $this->_setCharToGbkForExcel($head);

        $result = fputcsv($fp, $head);
        if ($result === false) {
            $this->_error('下载出错!');
        }

        foreach ($schoolInfoItems as $key => $value) {
            //写入内容
            $row = array();
            array_push($row, str_replace("\"", "\"\"", $value['schoolName']));
            array_push($row, str_replace("\"", "\"\"", $value['superintendentName']));
            array_push($row, str_replace("\"", "\"\"", "\t" . $value['superintendentTel']));
            //将utf-8转为gbk,用以解决中文不能显示出来的问题
            $row = $this->_setCharToGbkForExcel($row);
            $result = fputcsv($fp, $row);
            if ($result === false) {
                $this->_error('下载出错了!');
            }
        }

        fclose($fp);
    }

    /**
     * 导出学校初赛总结
     *
     * @return void
     **/
    public function downloadSummerAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();

        $TopicSchoolInfo = new Model_Topic_TopicSchoolInfo();
        $schoolInfoItems = $TopicSchoolInfo->selectWith();
        if (!$schoolInfoItems) {
            $this->_error('未找到相关初赛总结!');
        }

        //判断是否有初赛总结
        $zipItems = array();
        foreach ($schoolInfoItems as $key => $value) {
            if ($value['summaryPath']) {
                $wholePath = Bootstrap::$config['uploadRoot'].$value['summaryPath'];
                if (file_exists($wholePath)) {
                    $allName = $value['schoolName'] . '-';
                    $allName .= $value['superintendentName']. '-';
                    $allName .= $value['superintendentTel']. '-';
                    $allName .= substr($value['summaryPath'], strrpos($value['summaryPath'], '.'));
                    //将utf-8转为gbk,用以解决中文不能显示出来的问题
                    $fileName = $this->_setCharToGbkForExcel($allName);
                    $zipItems[$fileName] = $wholePath;
                }
            }
        }

        if (!$zipItems) {
            $this->_error('未找到相关初赛总结文件!');
        }

        $tmpDir = Bootstrap::$config['uploadRoot'].Bootstrap::$config['upload']['topicSummary'].'/'.date('YmdHis').Common::getSession('USERID');
        if (!is_dir($tmpDir)) {
            Common::mkDirByPath($tmpDir);
        }
        $name = '初赛总结-' . date('Y-m-d H:i:s').'.zip';
        //开始压缩
        $zip = new ZipArchive();
        $zip->open($tmpDir . '/' . $name, ZipArchive::OVERWRITE);
        //将作品和作品信息压缩在一起
        foreach ($zipItems as $key => $value) {
            $zip->addFile($value, $key);
        }

        $zip->close();

        header("Content-type:text/html;charset=utf-8");
        $fp       = fopen($tmpDir . '/' . $name,"rb");
        $fileSize = filesize($tmpDir . '/' . $name);
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$fileSize);
        Header('Content-Disposition: attachment; filename="'. $this->_setCharToGbkForExcel($name). '"');
        $buffer    = 1024;
        $fileCount = 0;
        //向浏览器返回数据
        while (!feof($fp) && $fileCount<$fileSize) {
            $file_con=fread($fp,$buffer);
            $fileCount+=$buffer;
            echo $file_con;
        }
        fclose($fp);

        //删除临时的文件tmpDir
        if ($handle = opendir($tmpDir)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    unlink($tmpDir . '/' . $item);
                }
            }
            closedir($handle);
            rmdir($tmpDir);
        }
    }

    /**
     * 是否晋级和市级奖励更改
     *
     * @return json
     **/
    public function setPrizeAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }

        $columnData = array(1 => 'status', 2 => 'cityPrize', 3 => 'schoolPrize');

        $id     = $this->getIntPost('id', 0); //作品申请ID
        $column = $this->getIntPost('column', 0); //要修改的是晋级(1)还是市级奖励(2)
        $data   = $this->getTrimedPost('data', false);
        if (!$id || !isset($columnData[$column]) || $data === false) {
            return $this->sendAjax('参数错误!', false);
        }

        $realColumn = $columnData[$column];
        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $result         = $topicApplyInfo->update(array($realColumn => $data), array('id' => $id));

        if ($result === false) {
            return $this->sendAjax('更新失败!', false);
        }
        return $this->sendAjax('更新成功!');
    }

    /**
     * 查看详情
     *
     * @return json
     **/
    public function showAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }

        $id = $this->getIntPost('id', 0); //作品申请ID
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }

        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $item           = $topicApplyInfo->select(array('id' => $id));
        if (!$item) {
            return $this->sendAjax('未找到该专题信息!', false);
        }
        //获取评论
        $commentObj   = new Model_Topic_TopicApplyInfoComment();
        $commentItems = $commentObj->selectWith(array('TopicApplyInfo_id' => $id));
        if ($commentItems) {
            foreach ($commentItems as $key => &$value) {
                $value['addTime'] = date('Y-m-d H:i:s', $value['addTime']);
            }
            unset($value);
        }
        $item['comment'] = $commentItems;
        unset($item['content']);
        return $this->sendAjax(json_encode($item));
    }

    /**
     * 处理评语
     *
     * @return json
     **/
    public function commentAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }

        $id      = $this->getIntPost('id', 0); //作品申请ID
        $comment = $this->getTrimedPost('content', ''); //作品申请ID
        if (!$id || !$comment) {
            return $this->sendAjax('参数错误!', false);
        }

        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $item           = $topicApplyInfo->select(array('id' => $id));
        if (!$item) {
            return $this->sendAjax('未找到该专题!', false);
        }
        $data['TopicApplyInfo_id'] = $id;
        $data['userId']            = $userId;
        $data['username']          = Common::getSession('USERNAME');
        $data['comment']           = $comment;
        $data['addTime']           = time();

        $commentObj = new Model_Topic_TopicApplyInfoComment();
        $result     = $commentObj->insert($data);
        if ($result === false) {
            return $this->sendAjax('评论失败!', false);
        }

        return $this->sendAjax('评论成功!');
    }

    /**
     * 删除作品
     *
     * @return json
     **/
    public function deleteAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $userId = Common::getSession('USERID');
        if (!$userId) {
            return $this->sendAjax('请先登录,谢谢!', false);
        }

        if (!in_array($_SESSION['adminFlag'.$userId], array(1))) {
            return $this->sendAjax('您没有删除的权限!');
        }

        $id = $this->getIntPost('id', 0); //作品申请ID
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }
        
        $topicApplyInfo = new Model_Topic_TopicApplyInfo();
        $item           = $topicApplyInfo->select(array('id' => $id));
        if (!$item) {
            return $this->sendAjax('未找到该专题!', false);
        }
        try {
            //事务开启
            $connection = $topicApplyInfo->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //删除数据库
            $result = $topicApplyInfo->delItemById($id);
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除该作品失败!', false);
            }
            //删除作品作者
            $multiInfo = new Model_Topic_TopicApplyMultiInfo();
            $result = $multiInfo->delItems(array('TopicApplyInfo_id' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除该作品失败!', false);
            }
            //提交数据
            $connection->commit();

            //log日志
            $errorlog = new ErrorLog();
            $msg      = '用户ID:' . $userId . '; 时间:'.date('Y-m-d H:i:s').'; 删除作品:' . $item['opusName'] . '作品信息为:' . var_export($item, true) . "\t\n";
            $errorlog->doLogForSql($msg, 'applyInfoDelete');
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('修改失败!', false);
        }
        //删除文件
        /*$content = (array)json_decode($item['content']);
        if ($content && is_array($content)) {
            foreach ($content as $key => $value) {
                if (is_object($value) && property_exists($value, 'type') && $value->type == 'fileupload'&& property_exists($value, 'val') && $value->val) {
                    //由于作品文件都放在了YDFS文件系统上了,所以用下面的下载
                    $downloadPath = Bootstrap::$config['YDFSApi']['apiHost'] . $value->val;
                }
            }
        }*/

        return $this->sendAjax('删除成功!');
    }

    /**
    * 将传入的字符或一维数组的值变成GBK编码
    * 方便放入excel中
    *
    * @param mix(string|array)
    * @return mix(string|array)
    */
    public function _setCharToGbkForExcel($chars)
    {
        if (!$chars) {
            return $chars;
        }

        if (is_string($chars)) {
            return iconv('utf-8', 'gbk//TRANSLIT//IGNORE', $chars);
        }

        if (is_array($chars)) {
            $charsTemp = array();
            foreach ($chars as $key => $value) {
                if (is_array($value)) {
                    return false;
                }

                $charsTemp[$key] = iconv('utf-8', 'gbk//TRANSLIT//IGNORE', $value);
            }
            return $charsTemp;
        }

        return $chars;
    }
        
}