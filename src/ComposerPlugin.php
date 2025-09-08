<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;

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

    // Run package post-install logic
    public function onPostInstall(): void
    {
        Installer::install();
    }

    // Run package post-update logic
    public function onPostUpdate(): void
    {
        Installer::update();
    }
}