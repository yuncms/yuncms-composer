<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;

/**
 * Plugin is the composer plugin that registers the Yii composer installer.
 *
 * @author Tongle XU <xutongle@gmail.com>
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {

    }

    /**
     * Listen events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoloadDump',
        ];
    }

    /**
     * @param \Composer\Script\Event $event
     */
    public function postAutoloadDump(ScriptEvent $event)
    {
        $manifest = new ManifestManager(
            $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir'), $vendorPath . '/yuncms/extensions.php'
        );
        $manifest->unlink()->build();
    }

}
