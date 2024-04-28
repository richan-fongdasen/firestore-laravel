<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use RichanFongdasen\Firestore\Cache\FirestoreStore;
use RichanFongdasen\Firestore\Session\FirestoreSessionHandler;
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
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();

        $this->app->scoped(FirestoreClient::class, function () {
            return new FirestoreClient([
                'projectId'   => config('firestore-laravel.project_id'),
                'credentials' => config('firestore-laravel.credentials'),
                'database'    => config('firestore-laravel.database'),
            ]);
        });

        $this->app->scoped(FirestoreStore::class, function (Application $app) {
            $firestore = $app->make(FirestoreClient::class);

            return new FirestoreStore(
                $firestore,
                (string) config('firestore-laravel.cache.collection', 'cache'),
                (string) config('firestore-laravel.cache.key_attribute', 'key'),
                (string) config('firestore-laravel.cache.value_attribute', 'value'),
                (string) config('firestore-laravel.cache.expiration_attribute', 'expired_at'),
                (string) config('cache.prefix', '')
            );
        });

        Session::extend('firestore', function (Application $app) {
            $firestore = $app->make(FirestoreClient::class);

            $store = new FirestoreStore(
                $firestore,
                (string) config('firestore-laravel.session.collection', 'sessions'),
                (string) config('firestore-laravel.cache.key_attribute', 'key'),
                (string) config('firestore-laravel.cache.value_attribute', 'value'),
                (string) config('firestore-laravel.cache.expiration_attribute', 'expired_at'),
                ''
            );

            return new FirestoreSessionHandler($store, (int) config('session.lifetime'));
        });

        $this->app->booting(function () {
            Cache::extend('firestore', function (Application $app) {
                $store = $app->make(FirestoreStore::class);

                return Cache::repository($store, config('cache.stores.firestore') ?? []);
            });
        });
    }
}
