<?php

namespace Dux\Composer;

use Composer\Installer\BinaryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\PartialComposer;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Dux\App;
use React\Promise\PromiseInterface;

class Installer extends LibraryInstaller
{
    private $process;

    public function __construct(IOInterface $io, PartialComposer $composer, ?string $type = 'library', ?Filesystem $filesystem = null, ?BinaryInstaller $binaryInstaller = null) {
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
        $this->process = new ProcessExecutor($io);
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->initializeVendorDir();
        $downloadPath = $this->getInstallPath($package);

        // remove the binaries if it appears the package files are missing
        if (!Filesystem::isReadable($downloadPath) && $repo->hasPackage($package)) {
            $this->binaryInstaller->removeBinaries($package);
        }

        $promise = $this->installCode($package);
        if (!$promise instanceof PromiseInterface) {
            $promise = \React\Promise\resolve(null);
        }

        $binaryInstaller = $this->binaryInstaller;
        $installPath = $this->getInstallPath($package);

        $process = $this->process;
        return $promise->then(static function () use ($binaryInstaller, $installPath, $package, $repo, $process): void {
            $binaryInstaller->installBinaries($package, $installPath);
            if (!$repo->hasPackage($package)) {
                $repo->addPackage(clone $package);
                $process->execute('php dux app:install ' . $package->getName());
            }

        });
    }

    public function supports($packageType)
    {
        return $packageType === 'dux-app';
    }
}