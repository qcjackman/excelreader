<?php

return [
    // 允许上传的文件类型
    'allowed_ext' => ['xls', 'xlsx'],

    // 内容模板配置
    'templates' => [
        // 模板名称为索引
        'example' => [
            // 模板字段定义
            'fields' => [
                ['name' => 'index',  'text' => '序号', 'type' => 'number'],
                ['name' => 'name',   'text' => '姓名', 'type' => 'string'],
                ['name' => 'age',    'text' => '年龄', 'type' => 'number'],
                ['name' => 'sex',    'text' => '性别', 'type' => 'string', 'default' => '未知'],
                ['name' => 'birth',  'text' => '生日', 'type' => 'date',   'format' => 'Y-m-d'],
                ['name' => 'avatar', 'text' => '照片', 'type' => 'image'],
                ['name' => 'idcard', 'text' => '证件', 'type' => 'string'],
                ['name' => 'reg_at', 'text' => '注册时间', 'type' => 'time', 'format' => 'Y-m-d H:i:s'],
            ],

            // 读取的Sheet，可选first/active
            'worksheet' => 'first',

            // 表头所在行
            'headRow' => 1,

            // 数据读取起始行
            'startRow' => 2,

            // 是否返回行数据
            'rowsData' => true,

            // 是否返回列数据
            'colsData' => true,
        ],
    ],
];
