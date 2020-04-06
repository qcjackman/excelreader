<?php

return [
    // 允许上传的文件类型
    'allowed_ext' => ['xls', 'xlsx'],

    // 内容模板配置
    'templates' => [
        // 模板名称为索引
        'example' => [
            // 模板导入时的文件名
            'filename' => 'example_template',

            // 模板字段定义
            'fields' => [
                ['name' => 'index',  'text' => '序号', 'type' => 'number'],
                ['name' => 'name',   'text' => '姓名', 'type' => 'string'],
                ['name' => 'age',    'text' => '姓名', 'type' => 'number'],
                ['name' => 'sex',    'text' => '性别', 'type' => 'string', 'default'=>'未知'],
                ['name' => 'birth',  'text' => '生日', 'type' => 'date',   'format'=>'Y-m-d'],
                ['name' => 'avatar', 'text' => '照片', 'type' => 'image'],
                ['name' => 'idcard', 'text' => '证件', 'type' => 'string'],
                ['name' => 'time',   'text' => '时间', 'type' => 'time', 'format' => 'Y-m-d H:i:s'],
            ],
        ],
    ],
];
