<?php

/*
 * This file is part of the EasyWeChatComposer.
 *
 * (c) mingyoung <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace yuncms\composer;

class ManifestManager
{
    const PACKAGE_TYPE = 'yii2-extension';

    const EXTRA_FIELD = 'yuncms';
    const TRANSLATE_FILE = 'yuncms/translates.php';//全局语言包
    const MIGRATION_FILE = 'yuncms/migrations.php';//全局迁移
    const TASK_FILE = 'yuncms/tasks.php';//计划任务
    const EVENT_FILE = 'yuncms/events.php';//计划任务
    const FRONTEND_MODULE_FILE = 'yuncms/frontend.php';//后端配置文件
    const BACKEND_MODULE_FILE = 'yuncms/backend.php';//前端配置文件

    /**
     * The vendor path.
     *
     * @var string
     */
    protected $vendorPath;

    /**
     * @param string $vendorPath
     */
    public function __construct(string $vendorPath)
    {
        $this->vendorPath = $vendorPath;
    }

    /**
     * Build the manifest file.
     */
    public function build()
    {
        $packages = [];
        if (file_exists($installed = $this->vendorPath . '/composer/installed.json')) {
            $packages = json_decode(file_get_contents($installed), true);
        }
        $frontendManifest = [];
        $backendManifest = [];
        $translateManifest = [];
        $migrationManifest = [];
        $eventManifest = [];
        $taskManifest = [];
        foreach ($packages as $package) {
            if ($package['type'] === self::PACKAGE_TYPE && isset($package['extra'][self::EXTRA_FIELD]) && isset($package['extra'][self::EXTRA_FIELD]['id'])) {
                $extra = $package['extra'][self::EXTRA_FIELD];
                if (isset($extra['frontend']['class'])) {//处理前端模块
                    $frontendManifest[$extra['id']] = $extra['frontend'];
                }
                if (isset($extra['backend']['class'])) {//处理后端模块
                    $backendManifest[$extra['id']] = $extra['backend'];
                }
                if (isset($extra['translate'])) {//处理语言包
                    $translateManifest[$extra['id'] . '*'] = $extra['translate'];
                }
                if (isset($extra['migrationNamespace'])) {//迁移
                    $migrationManifest[] = $extra['migrationNamespace'];
                }
                if (isset($extra['events'])) {
                    foreach ($extra['events'] as $event) {
                        $eventManifest[] = $event;
                    }
                }
                if (isset($extra['tasks'])) {
                    foreach ($extra['tasks'] as $task) {
                        $taskManifest[] = $task;
                    }
                }
            }
        }

        //写清单文件
        $this->write(self::FRONTEND_MODULE_FILE, $frontendManifest);
        $this->write(self::BACKEND_MODULE_FILE, $backendManifest);
        $this->write(self::TRANSLATE_FILE, $translateManifest);
        $this->write(self::MIGRATION_FILE, $migrationManifest);
        $this->write(self::EVENT_FILE, $eventManifest);
        $this->write(self::TASK_FILE, $taskManifest);
    }

    /**
     * Write the manifest array to a file.
     * @param string $file
     * @param array $manifest
     */
    public function write($file, array $manifest)
    {
        $file = $this->vendorPath . '/' . $file;
        $array = var_export($manifest, true);
        file_put_contents($file, "<?php\n\nreturn $array;\n");
        $this->opcacheInvalidate($file);
    }

    /**
     * Disable opcache
     * @param string $file
     * @return void
     */
    protected function opcacheInvalidate($file)
    {
        // invalidate opcache of extensions.php if exists
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }
}
