<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RichanFongdasen\Firestore\FirestoreService
 */
class Firestore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RichanFongdasen\Firestore\FirestoreService::class;
    }
}
