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

    const TRANSLATE_FILE = 'yuncms/i18n.php';//全局语言包

    const MIGRATION_FILE = 'yuncms/migrations.php';//全局迁移

    const CRON_FILE = 'yuncms/cron.php';//计划任务

    const FRONTEND_MODULE_FILE = 'yuncms/frontend.php';//后端配置文件

    const BACKEND_MODULE_FILE = 'yuncms/backend.php';//前端配置文件

    const CONSOLE_FILE = 'yuncms/console.php';//控制台配置文件

    /**
     * The vendor path.
     *
     * @var string
     */
    protected $vendorPath;

    /**
     * The manifest path.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * @param string $vendorPath
     * @param string $manifestPath
     */
    public function __construct(string $vendorPath, string $manifestPath)
    {
        $this->vendorPath = $vendorPath;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Remove manifest file.
     *
     * @return $this
     */
    public function unlink()
    {
        if (file_exists($this->manifestPath)) {
            @unlink($this->manifestPath);
        }

        return $this;
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
        $this->write($this->map($packages));
    }

    /**
     * @param array $packages
     *
     * @return array
     */
    public function map(array $packages): array
    {
        $manifest = [];
        foreach ($packages as $package) {
            if ($package['type'] === self::PACKAGE_TYPE && isset($package['extra'][self::EXTRA_FIELD])) {
                $extra = $package['extra'][self::EXTRA_FIELD];

                if (isset($extra['frontend']['class'])) {//处理前端模块
                    $manifest['frontend'][$extra['id']] = $extra['frontend'];
                }

                if (isset($extra['backend']['class'])) {//处理后端模块
                    $manifest['backend'][$extra['id']] = $extra['backend'];
                }

                if (isset($extra['i18n'])) {//处理语言包
                    $manifest['i18n'][$extra['id'] . '*'] = $extra['i18n'];
                }

                if (isset($extra['migrationNamespace'])) {//迁移
                    $manifest['migrationNamespaces'][] = $extra['migrationNamespace'];
                }

                if (isset($extra['events'])) {
                    foreach ($extra['events'] as $event) {
                        $manifest['events'][] = $event;
                    }
                }

                if (isset($extra['tasks'])) {
                    foreach ($extra['tasks'] as $task) {
                        $manifest['tasks'][] = $task;
                    }
                }

//                if (isset($package['extra'][self::EXTRA_FIELD])) {
//                    $manifest[$package['name']] = [
//                        self::EXTRA_FIELD => $package['extra'][self::EXTRA_FIELD] ?? [],
//                    ];
//                }
            }
        }
        return $manifest;
    }

    /**
     * Write the manifest array to a file.
     *
     * @param array $manifest
     */
    public function write(array $manifest)
    {
        $array = var_export($manifest, true);
        file_put_contents($this->manifestPath, "<?php\n\nreturn $array;\n");
        $this->opcacheInvalidate($this->manifestPath);
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
