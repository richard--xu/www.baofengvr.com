<?php
class UploadhelperController extends BaseController {

    /**
    * 上传, 后面看要不要判断有没有登陆
    *
    */
    public function uploadAction() {
        Yaf_Dispatcher::getInstance()->disableView();

        $which = $this->getTrimedPost('which', '');
        $uploadConfig = Bootstrap::$config['upload'];
        if (!$which || !array_key_exists($which, $uploadConfig)) {
            return $this->sendAjax('参数错误!', false);
        }
        $max       = $this->getTrimedPost('max', '');
        $allowType = $this->getTrimedPost('type', '');
        $max       = $max ? $max : 5120000;
        $type      = array('gif','png','jpg','jpeg','bmp');
        if ($allowType) {
            $allowType = explode(';', $allowType);
            foreach ($allowType as &$value) {
                $value = trim($value, '*.');
            }
            unset($value);
            $type = $allowType;
        }
        //循环建立目录
        if (!is_dir(PUB_PATH . '/' . trim($uploadConfig[$which], '/') . '/')) {
            try {
                Common::mkDirByPath(PUB_PATH . '/' . trim($uploadConfig[$which], '/') . '/', 0777);
            } catch (Exception $e) {
                $this->sendAjax('建立目录失败!', false);
            }
        }

        $result = Common::uploadPic(PUB_PATH . '/' . trim($uploadConfig[$which], '/') . '/', $max, $type);
        return $this->sendAjax(str_replace(PUB_PATH, '', $result['msg']), $result['result']);
    }
}