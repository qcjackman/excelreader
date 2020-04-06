<?php

namespace Qcjackman\Excelreader\Provider;

use Illuminate\Support\ServiceProvider;

class ExcelReaderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置资源
        $this->publishes([
            __DIR__.'/../../config/excelreader.php' => config_path('excelreader.php'),
        ]);
    }
}
