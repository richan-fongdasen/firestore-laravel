<?php

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config()->set('cache.default', 'firestore');
    $this->firestore = app(FirestoreClient::class);
});

afterEach(function () {
    clearFirestoreCollection('cache');
});

test('it can acquire lock and release lock with firestore', function () {
    $lock = Cache::lock('test-lock-key', 120);

    expect($lock->get())->toBeTrue();

    $document = $this->firestore->collection('cache')->document('test-lock-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-lock-key')
        ->and($document->data()['key'])->toBe('test-lock-key')
        ->and($document->data()['value'])->toBe(serialize($lock->owner()))
        ->and($document->data()['expired_at'])->toBeInstanceOf('Google\Cloud\Core\Timestamp')
        ->and($document->data()['expired_at']->get()->getTimestamp())->toBeGreaterThan(now()->addSeconds(9)->getTimestamp());

    $lock->release();

    $document = $this->firestore->collection('cache')->document('test-lock-key')->snapshot();

    expect($document->exists())->toBeFalse();
})->group('CacheLockTest', 'FeatureTest');

test('the lock can be released by force', function () {
    $lock = Cache::lock('test-lock-key', 120);

    expect($lock->get())->toBeTrue();

    $document = $this->firestore->collection('cache')->document('test-lock-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-lock-key')
        ->and($document->data()['key'])->toBe('test-lock-key')
        ->and($document->data()['value'])->toBe(serialize($lock->owner()))
        ->and($document->data()['expired_at'])->toBeInstanceOf('Google\Cloud\Core\Timestamp')
        ->and($document->data()['expired_at']->get()->getTimestamp())->toBeGreaterThan(now()->addSeconds(9)->getTimestamp());

    $lock->forceRelease();

    $document = $this->firestore->collection('cache')->document('test-lock-key')->snapshot();

    expect($document->exists())->toBeFalse();
})->group('CacheLockTest', 'FeatureTest');
