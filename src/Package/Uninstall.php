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

        // 包信息
        $package = $packages->firstWhere('name', $packageName);
        if (!$package) {
            $output->writeln('<fg=red>Did you find the installation package</>');
            return;
        }

        // 依赖提醒
        $dependentPackages = $packages->filter(function ($package) use ($packageName) {
            return $package['dependencies'] && in_array($packageName, array_keys($package['dependencies']));
        });
        if (!$dependentPackages->isEmpty()) {
            $output->writeln('<fg=red>Error: Cannot uninstall ' . $packageName . '. It is depended upon by:</>');
            foreach ($dependentPackages as $dependentPackage) {
                $output->writeln(' - ' . $dependentPackage['name']);
            }
            return;
        }

        // 过滤依赖
        $dependencies = $dependencies->except($package['name']);
        $appJson['dependencies'] = $dependencies->toArray();

        // 过滤包
        $packages = $packages->filter(function ($item) use ($package) {
            if ($item['name'] == $package['name']) {
                return false;
            }
            return true;
        });
        $appLockJson['packages'] = $packages->toArray();


        // 建立文件索引
        $app = $package['app'];
        $appPath = app_path(ucfirst($app));
        $jsPath = base_path('web/src/pages/' . $app);
        $configPath = config_path($app . '.yaml');
        $files = [];
        if (is_dir($appPath)) {
            $files[] = $appPath;
        }
        if (is_dir($jsPath)) {
            $files[] = $jsPath;
        }
        if (is_file($configPath)) {
            $files[] = $configPath;
        }


        $filteredPhpDeps = self::filterDependencies('phpDependencies', $package['phpDependencies'] ?: [], $packages, $packageName);
        $filteredJsDeps = self::filterDependencies('jsDependencies', $package['jsDependencies'] ?: [], $packages, $packageName);

        Package::del($output, $files);
        Package::saveConfig($output, [$app], true);
        Package::saveJson($configFile, $appJson);
        Package::saveJson($configLockFile, $appLockJson);
        Package::composer($output, $filteredPhpDeps, true);
        Package::node($output, $filteredJsDeps, true);

        $io->success('Add Application Success');
    }

    private static function filterDependencies(string $name, array $currentDeps, Collection $allPackages, string $currentPackageName): array
    {
        foreach ($allPackages as $pkg) {
            if (isset($pkg[$name])) {
                foreach ($currentDeps as $dep => $version) {
                    if (isset($pkg[$name][$dep])) {
                        unset($currentDeps[$dep]);
                    }
                }
            }
        }
        return $currentDeps;
    }




}