## 安装
composer require qcjackman/excelreader

## 注册
在 `config/app.php` 中添加
```
Qcjackman\Excelreader\Provider\ExcelReaderServiceProvider::class,
```

## 发布配置
```
php artisan vendor:publish --provider=Qcjackman\Excelreader\Provider\ExcelReaderServiceProvider
```

## 使用

1. 在配置文件 excelreader.php 中参照example模板添加模板配置，示例：
```
'example' => [
    'name' => 'example_template',
    'fields' => [
        ['name' => 'index',  'text' => '序号', 'type' => 'number'],
        ['name' => 'name',   'text' => '姓名', 'type' => 'string'],
        ['name' => 'age',    'text' => '姓名', 'type' => 'number'],
        ['name' => 'sex',    'text' => '性别', 'type' => 'string', 'default'=>'未知'],
        ['name' => 'birth',  'text' => '生日', 'type' => 'date',   'format'=>'Y-m-d'],
        ['name' => 'avatar', 'text' => '照片', 'type' => 'image'],
    ],
],
```

2. 模板下载
```
$excelReader = new \Qcjackman\Excelreader\Reader\Reader('example', 1);
$excelReader->templateFile();
```

3. 读取Excel文件内容
```
$file = $request->file('file');

try {
    $reader = new \Qcjackman\Excelreader\Reader\Reader('example', 1);
    $result = $reader->setFile($file)->toArray('first', 2);

    // dd($result);
} catch(\Exception $e) {
    return api_result_self(-1, false, 'import failed! '.$e->getMessage());
}
```