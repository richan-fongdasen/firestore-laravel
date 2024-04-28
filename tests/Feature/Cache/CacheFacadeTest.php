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

test('it can put a new cache item into the store', function () {
    Cache::put('test-key', 'test value', 3600);

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can put a new cache item with expiration defined by DateTimeInterface', function () {
    $expiration = now()->addHours(1);

    Cache::put('test-key', 'test value', $expiration);

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can store a cache item forever', function () {
    Cache::forever('test-key', 'test value');

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can store a cache item if the given key does not exist in the store', function () {
    Cache::add('test-key', 'test value', 3600);

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it won\'t store a cache item if the given key has already existed in the store', function () {
    Cache::put('test-key', 'test value', 3600);
    Cache::add('test-key', 'new test value', 3600);

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can retrieve an item from the cache store', function () {
    Cache::put('test-key', 'test value', 3600);

    $value = Cache::get('test-key');

    expect($value)->toBe('test value');
})->group('CacheFacadeTest', 'FeatureTest');

test('it will returns the default value if the given cache key does not exist in the store', function () {
    $value = Cache::get('test-key', 'default value');

    expect($value)->toBe('default value');
})->group('CacheFacadeTest', 'FeatureTest');

test('it can determine if the given cache key exists in the cache store', function () {
    Cache::put('test-key', 'test value', 3600);

    $exists = Cache::has('test-key');

    expect($exists)->toBeTrue();
})->group('CacheFacadeTest', 'FeatureTest');

test('it can increment a cache value', function () {
    Cache::put('test-key', 10, 3600);

    $value = Cache::increment('test-key', 5);

    $savedValue = Cache::get('test-key');

    expect($value)->toBe(15)
        ->and($savedValue)->toBe(15);
})->group('CacheFacadeTest', 'FeatureTest');

test('it can decrement a cache value', function () {
    Cache::put('test-key', 10, 3600);

    $value = Cache::decrement('test-key', 5);

    $savedValue = Cache::get('test-key');

    expect($value)->toBe(5)
        ->and($savedValue)->toBe(5);
})->group('CacheFacadeTest', 'FeatureTest');

test('it can compute and store a cache value with an expiration time', function () {
    $value = Cache::remember('test-key', 3600, function () {
        return 'test value';
    });

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($value)->toBe('test value')
        ->and($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can compute and store a cache value forever', function () {
    $value = Cache::rememberForever('test-key', function () {
        return 'test value';
    });

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($value)->toBe('test value')
        ->and($document->exists())->toBeTrue()
        ->and($document->id())->toBe('test-key')
        ->and($document->data()['key'])->toBe('test-key')
        ->and($document->data()['value'])->toBe(serialize('test value'));
})->group('CacheFacadeTest', 'FeatureTest');

test('it can retrieve and delete a cache item from the store', function () {
    Cache::put('test-key', 'test value', 3600);

    $value = Cache::pull('test-key');

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($value)->toBe('test value')
        ->and($document->exists())->toBeFalse();
})->group('CacheFacadeTest', 'FeatureTest');

test('it can remove an item from the cache store', function () {
    Cache::put('test-key', 'test value', 3600);

    $removed = Cache::forget('test-key');

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($removed)->toBeTrue()
        ->and($document->exists())->toBeFalse();
})->group('CacheFacadeTest', 'FeatureTest');

test('putting a cache item with negative expiration value will remove the item from the store', function () {
    Cache::put('test-key', 'test value', 3600);
    Cache::put('test-key', 'test value', -1);

    $document = $this->firestore->collection('cache')->document('test-key')->snapshot();

    expect($document->exists())->toBeFalse();
})->group('CacheFacadeTest', 'FeatureTest');

test('it can clear the entire cache store', function () {
    Cache::put('test-key-1', 'test value 1', 3600);
    Cache::put('test-key-2', 'test value 2', 3600);

    Cache::flush();

    $documents = $this->firestore->collection('cache')->documents();

    expect(iterator_to_array($documents))->toBeEmpty();
})->group('CacheFacadeTest', 'FeatureTest');
