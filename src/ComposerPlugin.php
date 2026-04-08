<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
use Tamedevelopers\Support\Installer;

final class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void {}
    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
            ScriptEvents::POST_UPDATE_CMD  => 'onPostUpdate',
        ];
    }

    /**
     * Handle post-install commands.
     * @param Event $event
     * @return void
     */
    public function onPostInstall(Event $event): void
    {
        Installer::install();
    }

    /**
     * Handle post-update commands.
     * @param Event $event
     * @return void
     */
    public function onPostUpdate(Event $event): void
    {
        Installer::update();
    }
}