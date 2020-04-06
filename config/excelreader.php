<?php


return [
    'fields' => [
        [
            ['name'=>'index','text'=>'序号','type'=>'number'],
            ['name'=>'code','text'=>'学号','type'=>'number','unique'=>true],
            ['name'=>'name','text'=>'姓名'],
            ['name'=>'sex','text'=>'性别','type'=>'string','default'=>'未知'],
            ['name'=>'birth','text'=>'出生日期','type'=>'date','format'=>'Y-m-d'],
            ['name'=>'image','text'=>'照片','type'=>'image'],
            ['name'=>'idcard','text'=>'身份证号','type'=>'string'],
            ['name'=>'time','text'=>'登录时间','type'=>'time'],
        ],
    ],
];
