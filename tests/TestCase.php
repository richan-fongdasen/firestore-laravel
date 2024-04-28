<?php

namespace RichanFongdasen\Firestore\Tests;

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use RichanFongdasen\Firestore\FirestoreLaravelServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'RichanFongdasen\\Firestore\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            CacheServiceProvider::class,
            FirestoreLaravelServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('cache.stores.firestore', [
            'driver'     => 'firestore',
        ]);

        /*
        $migration = include __DIR__.'/../database/migrations/create_firestore-laravel_table.php.stub';
        $migration->up();
        */
    }
}
