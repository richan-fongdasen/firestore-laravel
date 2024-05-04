<?php

declare(strict_types=1);

use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;
use RichanFongdasen\Firestore\Cache\FirestoreStore;

beforeEach(function () {
    $this->firestore = new FirestoreClient([
        'projectId' => 'firestore-emulator',
        'database'  => '(default)',
    ]);
    $this->store = new FirestoreStore(
        firestore: $this->firestore,
        collection: 'cache',
        keyAttribute: 'key',
        valueAttribute: 'value',
        expirationAttribute: 'expired_at',
        prefix: 'prefix_'
    );

    $this->firestore->collection('cache')->document('prefix_first-test-key')->set([
        'key'        => 'prefix_first-test-key',
        'value'      => serialize('first test value'),
        'expired_at' => new Timestamp(now()->addHours(4)),
    ]);

    $this->firestore->collection('cache')->document('prefix_second-test-key')->set([
        'key'        => 'prefix_second-test-key',
        'value'      => serialize('second test value'),
        'expired_at' => new Timestamp(now()->addHours(5)),
    ]);
});

afterEach(function () {
    clearFirestoreCollection('cache');
});

test('it can retrieve an item from the cache by key', function () {
    $value = $this->store->get('first-test-key');

    expect($value)->toBe('first test value');
})->group('FirestoreStoreTest', 'FeatureTest');

test('expired cache key will always return null', function () {
    $ref = $this->firestore->collection('cache')->document('prefix_second-test-key');
    $ref->update([
        [
            'path'  => 'expired_at',
            'value' => now()->subHours(5)->getTimestamp(),
        ],
    ]);

    $value = $this->store->get('second-test-key');

    expect($value)->toBeNull();

    $values = $this->store->many(['first-test-key', 'second-test-key']);

    expect($values)->toBe([
        'first-test-key'  => 'first test value',
        'second-test-key' => null,
    ]);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can retrieve multiple items from the cache by key', function () {
    $values = $this->store->many(['first-test-key', 'second-test-key', 'not-exists']);

    expect($values)->toBe([
        'first-test-key'  => 'first test value',
        'second-test-key' => 'second test value',
        'not-exists'      => null,
    ]);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can put a new cache item into the store', function () {
    $this->store->put('new-test-key', 'new test value', 3600);

    $value = $this->store->get('new-test-key');

    expect($value)->toBe('new test value');
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can put some cache items into the store at once', function () {
    $this->store->putMany([
        'new-test-key-1' => 'new test value 1',
        'new-test-key-2' => 'new test value 2',
    ], 3600);

    $values = $this->store->many(['new-test-key-1', 'new-test-key-2']);

    expect($values)->toBe([
        'new-test-key-1' => 'new test value 1',
        'new-test-key-2' => 'new test value 2',
    ]);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can add a new cache item when the given key does not exist in the store', function () {
    $value = $this->store->add('new-test-key', 'new test value', 3600);

    expect($value)->toBeTrue();

    $value = $this->store->get('new-test-key');
})->group('FirestoreStoreTest', 'FeatureTest');

test('it will not add a new cache item when the given key already exists in the store', function () {
    $value = $this->store->add('first-test-key', 'new test value', 3600);

    expect($value)->toBeFalse();

    $value = $this->store->get('first-test-key');

    expect($value)->toBe('first test value');
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can increment the cache value', function () {
    $this->store->put('increment-test-key', 10, 3600);

    $result = $this->store->increment('increment-test-key', 50);

    $savedValue = $this->store->get('increment-test-key');

    expect($result)->toBe(60)
        ->and($savedValue)->toBe(60);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can decrement the cache value', function () {
    $this->store->put('decrement-test-key', 100, 3600);

    $result = $this->store->decrement('decrement-test-key', 30);

    $savedValue = $this->store->get('decrement-test-key');

    expect($result)->toBe(70)
        ->and($savedValue)->toBe(70);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can save a new cache item forever', function () {
    $this->store->forever('forever-test-key', 'forever test value');

    $value = $this->store->get('forever-test-key');

    expect($value)->toBe('forever test value');
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can forget an existing cache item', function () {
    $this->store->forget('first-test-key');

    $value = $this->store->get('first-test-key');

    expect($value)->toBeNull();
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can flush the whole cache store', function () {
    $this->store->flush();

    $values = $this->store->many(['first-test-key', 'second-test-key']);

    expect($values)->toBe([
        'first-test-key'  => null,
        'second-test-key' => null,
    ]);
})->group('FirestoreStoreTest', 'FeatureTest');

test('it set and get the cache prefix value', function () {
    expect($this->store->getPrefix())->toBe('prefix_');

    $this->store->setPrefix('new_prefix_');

    expect($this->store->getPrefix())->toBe('new_prefix_');
})->group('FirestoreStoreTest', 'FeatureTest');

test('it can returns the FirestoreClient instance', function () {
    $firestore = $this->store->getClient();

    expect($firestore)->toBeInstanceOf(FirestoreClient::class);
})->group('FirestoreStoreTest', 'FeatureTest');
