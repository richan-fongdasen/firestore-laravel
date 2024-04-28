<?php

use RichanFongdasen\Firestore\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function clearFirestoreCollection(string $collection = 'cache'): void
{
    $firestore = app(\Google\Cloud\Firestore\FirestoreClient::class);
    $documents = $firestore->collection($collection)->documents();

    foreach ($documents as $document) {
        $document->reference()->delete();
    }
}
