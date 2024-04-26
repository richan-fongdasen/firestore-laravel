<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore;

use RichanFongdasen\Firestore\Commands\FirestoreLaravelCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FirestoreLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('firestore-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_firestore-laravel_table')
            ->hasCommand(FirestoreLaravelCommand::class);
    }
}
