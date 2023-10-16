<?php

namespace Dux\Package;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class Uninstall
{
    public static function main(InputInterface $input, OutputInterface $output, HelperInterface $helper, SymfonyStyle $io, string $packageName): void
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

        $packagesDeps = self::getUninstallableDependencies($packageName, $packages);
        $phpDeps = self::findOtherDependencies('phpDependencies', $packagesDeps, $packages);
        $jsDeps = self::findOtherDependencies('jsDependencies', $packagesDeps, $packages);


        $unPackages = $packages->filter(function ($item) use ($packagesDeps) {
            if ($packagesDeps->contains($item['name'])) {
                return true;
            }
            return false;
        });

        $dependencies = $dependencies->except($packagesDeps->toArray());
        $appJson['dependencies'] = $dependencies->toArray();

        $packages = $packages->filter(function ($item) use ($packagesDeps) {
            if ($packagesDeps->contains($item['name'])) {
                return false;
            }
            return true;
        });
        $appLockJson['packages'] = $packages->toArray();


        if ($unPackages->isEmpty()) {
            $output->writeln('<fg=red>No uninstallable dependencies found</>');
            return;
        }

        $output->writeln('<info>Finding dependencies to uninstall:</info>');

        $unPackages->map(function ($item) use ($output) {
            $output->writeln(' - <info>' . $item['name'] . '</info>');
        });

        $question = new ConfirmationQuestion('Do you want to continue? [Y/n] ', true);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<fg=red>Action canceled.</>');
            return;
        }

        $files = $unPackages->map(function ($item) use ($apps) {
            $arr = [];
            $app = $item['app'];
            if (!$app) {
                return [];
            }
            $apps->add(ucfirst($app));
            $appPath = app_path(ucfirst($app));
            $jsPath = base_path('web/src/pages/' . $app);
            $configPath = config_path($app . '.yaml');

            if (is_dir($appPath)) {
                $arr[] = $appPath;
            }
            if (is_dir($jsPath)) {
                $arr[] = $jsPath;
            }
            if (is_file($configPath)) {
                $arr[] = $configPath;
            }
            return $arr;
        });

        Package::del($output, $files);
        Package::saveJson($configFile, $appJson);
        Package::saveJson($configLockFile, $appLockJson);
        Package::composer($output, $phpDeps->toArray(), true);
        Package::node($output, $jsDeps->toArray(), true);

        $io->success('Add Application Success');
    }


    public static function findOtherDependencies($name, $packagesNames, $allPackages): Collection
    {
        $phpDependenciesToCheck = $packagesNames->map(function ($packageName) use ($name, $allPackages) {
            $package = $allPackages->firstWhere('name', $packageName);
            return isset($package[$name]) ? collect($package[$name])->keys() : collect();
        })->flatten()->unique();

        $remainingPackages = $allPackages->filter(function ($package) use ($packagesNames) {
            return !$packagesNames->contains($package['name']);
        });

        $allPhpDependencies = $remainingPackages->map(function ($package) use ($name) {
            return isset($package[$name]) ? collect($package[$name])->keys() : collect();
        })->flatten()->unique();

        return $phpDependenciesToCheck->diff($allPhpDependencies);
    }

    public static function getUninstallableDependencies($packageName, $packages): Collection
    {
        $allDependencies = self::findAllDependenciesOfPackage($packageName, $packages);

        $safeDependencies = $allDependencies->filter(function ($dependencyName) use ($packages, $packageName) {
            $dependentPackages = $packages->filter(function ($package) use ($dependencyName) {
                return isset($package['dependencies'][$dependencyName]);
            })->pluck('name');

            return $dependentPackages->diff([$packageName])->isEmpty();
        });

        return collect([$packageName])->concat($safeDependencies);
    }

    public static function findAllDependenciesOfPackage($name, $packages)
    {
        $package = $packages->firstWhere('name', $name);
        $directDependencies = isset($package['dependencies']) ? collect($package['dependencies'])->keys() : collect();
        $indirectDependencies = collect();
        foreach ($directDependencies as $dependency) {
            $indirectDependencies = $indirectDependencies->concat(self::findAllDependenciesOfPackage($dependency, $packages));
        }
        return $directDependencies->concat($indirectDependencies)->unique();
    }

}