<?php
return array(
    /*********************************************************************************************************************************
     * 设置页面加载时的css和js
     * 按照modules / controller / action 三级来,子级继承父级
     * 如果不想继承父级的css,请加上'jsNotInherit'  => true,或者'cssNotInherit' => true,
     * 对于module里的想要选择性的使用最外面的文件的情况可以配置成 array('路径或者文件名' => '_top'(标志最外面) 或者 'full'(完整路径)) 
     */
    'version' => '0.0.5',
    '__'      => array(
                    'js'  => array('jquery-1.11.1.min', 'wdialog', 'knockout-3.4.0'),
                    'css' => array('wdialog')
                 ), //基础
    'admin'   => array(
                    '__'    => array(
                                  'js'  => array('json2' => '_top', 'jquery.validate' => '_top', 'juicer-min'=> '_top'),
                                  'css' => array('base', 'main'),
                                  //'jsNotInherit'  => true,
                                  //'cssNotInherit' => true,
                               ), //基础
                    'product' => array(
                                          '__'  => array(
                                                      'js'  => array('product', 'uploadify/jquery.uploadify' => '_top'),
                                                      'css' => array('/front/js/uploadify/uploadify' => 'full')
                                                   ), //基础
                                      ), //controller级
                    'category' => array(
                                          '__'    => array(
                                                        'js'  => array('category'),
                                                        'css' => array()
                                                     ), //基础
                                      ), //controller级
                    'advertisement' => array(
                                          '__'    => array(
                                                        'js'  => array('advertisement', 'uploadify/jquery.uploadify' => '_top'),
                                                        'css' => array('/front/js/uploadify/uploadify' => 'full')
                                                     ), //基础
                     
                                      ), //controller级
                    'topic' => array(
                                    '__' => array(
                                                'js'  => array('hySelected' => '_top', 'topic'),
                                                'css' => array()
                                            ),
                    )

    ),//modules级
);