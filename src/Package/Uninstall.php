<?php

namespace Dux\Package;

use Dux\Handlers\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Uninstall
{
    public static function main(InputInterface $input, OutputInterface $output, SymfonyStyle $io, string $packageName): void
    {
        $configFile = base_path('app.json');
        $configLockFile = base_path('app.lock');
        $appJson = [];
        $appLockJson = [];
        if (is_file($configFile)) {
            $appJson = Package::getJson($configFile);
        }
        if (is_file($configLockFile)) {
            $appLockJson = Package::getJson($configLockFile);
        }


        $apps = collect();
        $dependencies = collect($appJson['dependencies'] ?: []);
        $packages = collect($appLockJson['packages'] ?: []);
        $composers = collect();
        $node = collect();
        $files = collect();


        // 找出除了要卸载的包之外所有的依赖
        $dependenciesToRemove = self::findDependencies($packageName, $packages)->push($packageName)->unique();
        $blockingPackages = collect([]);
        foreach ($dependenciesToRemove as $dependency) {
            $blockers = self::checkDependency($dependency, $packages);
            if ($blockers) {
                $blockingPackages->push(["package" => $dependency, "blockedBy" => $blockers]);
            }
        }

        dd($blockingPackages);

        $io->success('Add Application Success');
    }

    public static function findDependencies($packageName, $packages) {
        $package = $packages->firstWhere('name', $packageName);
        $directDependencies = array_keys($package['dependencies'] ?? []);

        $allDependencies = collect($directDependencies);
        foreach ($directDependencies as $dependency) {
            $allDependencies = $allDependencies->merge(self::findDependencies($dependency, $packages))->unique();
        }
        return $allDependencies;
    }

    public static function checkDependency($packageName, $packages) {
        $packagesDependingOn = $packages->filter(function ($package) use ($packageName) {
            return isset($package['dependencies'][$packageName]);
        });
        return $packagesDependingOn->map(function ($package) {
            return $package['name'];
        })->toArray();
    }

}