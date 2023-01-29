<?php
declare(strict_types=1);

namespace Dux\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Util\ProcessExecutor;

class Plugin implements PluginInterface, EventSubscriberInterface {

    protected $composer;
    protected $io;
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::PRE_POOL_CREATE => array(array('onPrePoolCreate', 0)),
        );
    }

    public function onPrePoolCreate(PrePoolCreateEvent $event)
    {
        $process = new ProcessExecutor($this->io);
        $process->execute('php dux app:install ' . $event->getName());
    }



}