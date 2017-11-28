<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\composer;

use yii\composer\Installer;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * @author Tongle Xu <xutongle@gmail.com>
 */
class LibraryInstaller extends Installer
{
    const EXTRA_FIELD = 'yuncms';
    const TRANSLATE_FILE = 'yuncms/i18n.php';//全局语言包
    const MIGRATION_FILE = 'yuncms/migrations.php';//全局迁移
    const CRON_FILE = 'yuncms/cron.php';//计划任务
    const FRONTEND_MODULE_FILE = 'yuncms/frontend.php';//后端配置文件
    const BACKEND_MODULE_FILE = 'yuncms/backend.php';//前端配置文件
    const CONSOLE_FILE = 'yuncms/console.php';//控制台配置文件


    /**
     * @inheritdoc
     */
    public function supports($packageType)
    {
        return $packageType === 'yii2-extension';
    }

    /**
     * @inheritdoc
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // install the package the normal composer way
        parent::install($repo, $package);
        $this->addExtension($package);
    }

    /**
     * @inheritdoc
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);
        $this->removeExtension($initial);
        $this->addExtension($target);
    }

    /**
     * @inheritdoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // uninstall the package the normal composer way
        parent::uninstall($repo, $package);
        $this->removeExtension($package);
    }

    /**
     * 安装扩展
     * @param PackageInterface $package
     */
    protected function addExtension(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (isset($extra[self::EXTRA_FIELD]['name'])) {
            //处理前端模块
            if (isset($extra[self::EXTRA_FIELD]['frontend']['class'])) {
                $modules = $this->loadConfig(self::FRONTEND_MODULE_FILE);
                $modules[$extra[self::EXTRA_FIELD]['name']] = $extra[self::EXTRA_FIELD]['frontend'];
                $this->saveConfig($modules, self::FRONTEND_MODULE_FILE);
            }
            //处理后端模块
            if (isset($extra[self::EXTRA_FIELD]['backend']['class'])) {
                $backendModules = $this->loadConfig(self::BACKEND_MODULE_FILE);
                $backendModules[$extra[self::EXTRA_FIELD]['name']] = $extra[self::EXTRA_FIELD]['backend'];
                $this->saveConfig($backendModules, self::BACKEND_MODULE_FILE);
            }
            //处理迁移
            if (isset($extra[self::EXTRA_FIELD]['migrationNamespace'])) {
                $migrations = $this->loadConfig(self::MIGRATION_FILE);
                $migrations[] = $extra[self::EXTRA_FIELD]['migrationNamespace'];
                $migrations = array_unique($migrations);
                $this->saveConfig($migrations, self::MIGRATION_FILE);
            }
            //处理语言包
            if (isset($extra[self::EXTRA_FIELD]['name']) && isset($extra[self::EXTRA_FIELD]['i18n'])) {
                $translates = $this->loadConfig(self::TRANSLATE_FILE);
                $translateName = $extra[self::EXTRA_FIELD]['name'] . '*';
                $translates[$translateName] = $extra[self::EXTRA_FIELD]['i18n'];
                $this->saveConfig($translates, self::TRANSLATE_FILE);
            }
            //处理定时任务
            if (isset($extra[self::EXTRA_FIELD]['name']) && isset($extra[self::EXTRA_FIELD]['cron'])) {
                $cron = $this->loadConfig(self::CRON_FILE);
                $cronName = $extra[self::EXTRA_FIELD]['name'];
                $cron[$cronName] = $extra[self::EXTRA_FIELD]['cron'];
                $this->saveConfig($cron, self::CRON_FILE);
            }
        }
    }

    /**
     * 删除扩展
     * @param PackageInterface $package
     */
    protected function removeExtension(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (isset($extra[self::EXTRA_FIELD]['name'])) {
            //删除前端模块
            $modules = $this->loadConfig(self::FRONTEND_MODULE_FILE);
            unset($modules[$extra[self::EXTRA_FIELD]['name']]);
            $this->saveConfig($modules, self::FRONTEND_MODULE_FILE);

            //删除后端模块
            $backendModules = $this->loadConfig(self::BACKEND_MODULE_FILE);
            unset($backendModules[$extra[self::EXTRA_FIELD]['name']]);
            $this->saveConfig($backendModules, self::BACKEND_MODULE_FILE);

            //删除迁移
            $migrations = $this->loadConfig(self::MIGRATION_FILE);
            if (isset($extra[self::EXTRA_FIELD]['migrationNamespace'])) {
                foreach ($migrations as $id => $migration) {
                    if ($migration == $extra[self::EXTRA_FIELD]['migrationNamespace']) {
                        unset($migrations[$id]);
                    }
                }
                asort($migrations);
                $this->saveConfig($migrations, self::MIGRATION_FILE);
            }

            //删除翻译
            $translates = $this->loadConfig(self::TRANSLATE_FILE);
            $translateName = $extra[self::EXTRA_FIELD]['name'] . '*';
            unset($translates[$translateName]);
            $this->saveConfig($translates, self::TRANSLATE_FILE);

            //删除计划任务
            $cron = $this->loadConfig(self::CRON_FILE);
            $cronName = $extra[self::EXTRA_FIELD]['name'] . '*';
            unset($cron[$cronName]);
            $this->saveConfig($cron, self::CRON_FILE);
        }
    }

    /**
     * 加载配置
     * @param string $file
     * @return array|mixed
     */
    protected function loadConfig($file)
    {
        $file = $this->vendorDir . '/' . $file;
        if (!is_file($file)) {
            return [];
        }
        $this->opcacheInvalidate($file);
        return require($file);
    }

    /**
     * 保存配置到文件
     * @param array $config
     * @param string $file
     */
    protected function saveConfig(array $config, $file)
    {
        $file = $this->vendorDir . '/' . $file;
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $array = var_export($config, true);
        file_put_contents($file, "<?php\n\nreturn $array;\n");
        $this->opcacheInvalidate($file);
    }

    /**
     * @param $file
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