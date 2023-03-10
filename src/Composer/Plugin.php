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
use Dux\App\AppHandler;

class Plugin implements PluginInterface, EventSubscriberInterface {

    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
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
        try {
            AppHandler::install($package->getName(), true);
        }catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }
}