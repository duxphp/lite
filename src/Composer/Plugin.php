<?php
declare(strict_types=1);

namespace Dux\Composer;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Util\ProcessExecutor;

class Plugin implements PluginInterface, EventSubscriberInterface {

    protected Composer $composer;
    protected IOInterface $io;
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL => array(array('onPostPackageInstall', 0)),
        );
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        $type = $package->getType();
        if ($type !== 'dux-app') {
            return;
        }
        $process = new ProcessExecutor($this->io);
        $process->execute('php dux app:install ' . $package->getName());
    }
}