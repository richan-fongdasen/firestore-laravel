<?php

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    config()->set('session.driver', 'firestore');
    $this->firestore = app(FirestoreClient::class);

    Session::start();
});

afterEach(function () {
    clearFirestoreCollection('sessions');
});

test('it can put an item into the session data store', function () {
    Session::put('name', 'John Doe');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('name', 'John Doe');
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can push an item into the array session values', function () {
    Session::push('names', 'John Doe');
    Session::push('names', 'Jane Doe');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('names', ['John Doe', 'Jane Doe']);
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can retrieve and delete an item from the session store', function () {
    Session::put('name', 'John Doe');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('name', 'John Doe');

    $name = Session::pull('name');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->not->toHaveKey('name');
    expect($name)->toBe('John Doe');
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can increment a session item\'s value', function () {
    Session::put('counter', 1);
    Session::increment('counter', 10);
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('counter', 11);
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can decrement a session item\'s value', function () {
    Session::put('counter', 10);
    Session::decrement('counter', 5);
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('counter', 5);
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can retrieve all session data', function () {
    Session::put('name', 'John Doe');
    Session::put('age', 30);
    Session::save();

    $sessionData = Session::all();

    expect($sessionData)->toHaveKey('name', 'John Doe')
        ->toHaveKey('age', 30);
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can retrieve a single item from the session store', function () {
    Session::put('name', 'John Doe');
    Session::save();

    $name = Session::get('name');

    expect($name)->toBe('John Doe');
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can retrieve a portion of the session data, described by the given keys', function () {
    Session::put('name', 'John Doe');
    Session::put('age', 30);
    Session::put('gender', 'male');
    Session::save();

    $sessionData = Session::only(['name', 'gender']);

    expect($sessionData)->toHaveKey('name', 'John Doe')
        ->toHaveKey('gender', 'male')
        ->not->toHaveKey('age');
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can retrieve a portion of the session data, excluding the given keys', function () {
    Session::put('name', 'John Doe');
    Session::put('age', 30);
    Session::put('gender', 'male');

    $sessionData = Session::except(['gender', 'age']);

    expect($sessionData)->toHaveKey('name', 'John Doe')
        ->not->toHaveKey('age')
        ->not->toHaveKey('gender');
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can check if the session store has a specific item', function () {
    Session::put('name', 'John Doe');
    Session::save();

    expect(Session::has('name'))->toBeTrue()
        ->and(Session::has('age'))->toBeFalse();
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can check if an item with the given key exists in the session store', function () {
    Session::put('name', 'John Doe');
    Session::put('gender', null);
    Session::save();

    expect(Session::exists('name'))->toBeTrue()
        ->and(Session::exists('gender'))->toBeTrue()
        ->and(Session::exists('age'))->toBeFalse();
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can check if an item with the given key is missing from the session store', function () {
    Session::put('name', 'John Doe');
    Session::put('gender', null);
    Session::save();

    expect(Session::missing('name'))->toBeFalse()
        ->and(Session::missing('gender'))->toBeFalse()
        ->and(Session::missing('age'))->toBeTrue();
})->group('FirestoreSessionTest', 'FeatureTest');

test('it can remove an item from the session store', function () {
    Session::put('name', 'John Doe');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->toHaveKey('name', 'John Doe');

    Session::forget('name');
    Session::save();

    $sessionData = unserialize(unserialize(
        $this->firestore->collection('sessions')
            ->document(session()->getId())
            ->snapshot()
            ->data()['value']
    ));

    expect($sessionData)->not->toHaveKey('name');
})->group('FirestoreSessionTest', 'FeatureTest');
