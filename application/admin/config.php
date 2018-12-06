<?php

//配置文件
return [
    'url_common_param'       => true,
    'url_html_suffix'        => '',
    'controller_auto_search' => true,

    /*订单操作*/
    'CONVERT_ACTION'=>[
        'pay'=> '付款',
        'pay_cancel'=>'取消付款',
        'confirm'=>'确认订单',
        'cancel'=>'取消确认',
        'invalid'=>'作废订单',
        'remove'=>'删除订单',
        'delivery'=>'确认发货',
        'delivery_confirm'=>'确认收货',
    ],

];


