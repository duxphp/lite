<?php

namespace Dux\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class Installer extends LibraryInstaller
{
    protected $type = 'dux-app';

    public function getInstallPath(PackageInterface $package) {
        list($vendor, $dir) = explode('/', $package->getName());
        return parent::getInstallPath($package);
    }

}