<?php

namespace Dux\Composer;

use Composer\Installer\BinaryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\PartialComposer;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
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

        return $promise->then(static function () use ($binaryInstaller, $installPath, $package, $repo): void {
            $binaryInstaller->installBinaries($package, $installPath);
            if (!$repo->hasPackage($package)) {
                $repo->addPackage(clone $package);
                $this->process->execute('php dux app:install ' . $package->getName());
            }

        });
    }

    public function supports($packageType)
    {
        return $packageType === 'dux-app';
    }

    public function copyDir($source, $destination, $child = 1)
    {
        if(!is_dir($source)){
            return 0;
        }
        if(!is_dir($destination)){
            mkdir($destination,0777);
        }
        $handle= dir($source);
        while($entry = $handle->read()) {
            if(($entry!=".")&&($entry!="..")){
                if(is_dir($source."/".$entry)){
                    if($child){
                        $this -> xCopy($source."/".$entry,$destination."/".$entry,$child);
                    }
                }else{
                    copy($source."/".$entry,$destination."/".$entry);
                }
            }
        }
        return 1;
    }
}