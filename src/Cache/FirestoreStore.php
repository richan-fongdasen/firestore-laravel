<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore\Cache;

use DateTimeInterface;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;

class FirestoreStore implements Store
{
    use InteractsWithTime;

    public function __construct(
        protected FirestoreClient $firestore,
        protected string $collection,
        protected string $keyAttribute,
        protected string $valueAttribute,
        protected string $expirationAttribute,
        protected string $prefix
    ) {
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $document = $this->firestore
            ->collection($this->collection)
            ->document($this->prefix . $key)
            ->snapshot();

        if (
            ! $document->exists() ||
            ! isset($document->data()[$this->valueAttribute]) ||
            $this->isExpired($document->data())
        ) {
            return $default;
        }

        return $this->unserialize(
            $document->data()[$this->valueAttribute]
        );
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @return array
     */
    public function many(array $keys)
    {
        if (count($keys) === 0) {
            return [];
        }

        $prefixedKeys = array_map(function ($key) {
            return $this->prefix . $key;
        }, $keys);

        $documents = $this->firestore->collection($this->collection)
            ->where($this->keyAttribute, 'in', $prefixedKeys)
            ->documents();

        $now = Carbon::now();

        return array_merge(
            collect(array_flip($keys))->map(function () {
                // Set the default value to null
            })->all(),
            collect($documents)->mapWithKeys(function (mixed $document) use ($now) {
                $key = Str::replaceFirst($this->prefix, '', $document->id());

                if (
                    ($document === null) ||
                    ! $document->exists() ||
                    ! isset($document->data()[$this->valueAttribute]) ||
                    $this->isExpired($document->data(), $now)
                ) {
                    return [$key => null];
                }

                return [$key => $this->unserialize(
                    $document->data()[$this->valueAttribute]
                )];
            })->all()
        );
    }

    /**
     * Determine if the given item is expired.
     */
    protected function isExpired(array $item, ?DateTimeInterface $expiration = null): bool
    {
        if ($expiration === null) {
            $expiration = Carbon::now();
        }

        $value = data_get($item, $this->expirationAttribute);

        // If the expiration attribute is not set or not an instance of Timestamp, then consider it as expired.
        if (! ($value instanceof Timestamp)) {
            return true;
        }

        return $expiration->getTimestamp() >= $value->get()->getTimestamp();
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $this->firestore
            ->collection($this->collection)
            ->document($this->prefix . $key)
            ->set([
                $this->keyAttribute        => $this->prefix . $key,
                $this->valueAttribute      => $this->serialize($value),
                $this->expirationAttribute => $this->toTimestamp($seconds),
            ]);

        return true;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param int $seconds
     *
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        if (count($values) === 0) {
            return true;
        }

        $bulkWriter = $this->firestore->bulkWriter([
            'isThrottlingEnabled' => true,
        ]);
        $expiration = $this->toTimestamp($seconds);

        collect($values)->each(function ($value, $key) use ($bulkWriter, $expiration) {
            $bulkWriter->set(
                $this->firestore
                    ->collection($this->collection)
                    ->document($this->prefix . $key),
                [
                    $this->keyAttribute        => $this->prefix . $key,
                    $this->valueAttribute      => $this->serialize($value),
                    $this->expirationAttribute => $expiration,
                ]
            );
        });

        $bulkWriter->commit();

        return true;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     */
    public function add(string $key, mixed $value, int $seconds): bool
    {
        return ($this->get($key) === null) && $this->put($key, $value, $seconds);
    }

    /**
     * Increment the value of an item in the cache.
     * You can only increment / decrement a cache value once per second.
     * Ref: https://cloud.google.com/firestore/docs/manage-data/add-data#increment_a_numeric_value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $ref = $this->firestore
            ->collection($this->collection)
            ->document($this->prefix . $key);

        $result = $ref->update([[
            'path'  => $this->valueAttribute,
            'value' => FieldValue::increment($value),
        ]]);

        return data_get($result, 'transformResults.0.integerValue', false);
    }

    /**
     * Decrement the value of an item in the cache.
     * You can only increment / decrement a cache value once per second.
     * Ref: https://cloud.google.com/firestore/docs/manage-data/add-data#increment_a_numeric_value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, Carbon::now()->addYears(5)->getTimestamp());
    }

    /**
     * Get a lock instance.
     */
    public function lock(string $name, int $seconds = 0, ?string $owner = null): FirestoreLock
    {
        return new FirestoreLock($this, $this->prefix . $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     */
    public function restoreLock(string $name, string $owner): FirestoreLock
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function forget($key)
    {
        $this->firestore
            ->collection($this->collection)
            ->document($this->prefix . $key)
            ->delete();

        return true;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        collect($this->firestore->collection($this->collection)->documents())
            ->each(function ($document) {
                $document->reference()->delete();
            });

        return true;
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
     */
    protected function toTimestamp(int $seconds): Timestamp
    {
        return $seconds > 0
            ? new Timestamp(Carbon::now()->addSeconds($seconds))
            : new Timestamp(Carbon::now());
    }

    /**
     * Serialize the value.
     */
    protected function serialize(mixed $value): mixed
    {
        return match (gettype($value)) {
            'boolean', 'double', 'integer', 'NULL' => $value,
            default => serialize($value),
        };
    }

    /**
     * Unserialize the value.
     */
    protected function unserialize(mixed $value): mixed
    {
        return match (gettype($value)) {
            'boolean', 'double', 'integer', 'NULL' => $value,
            default => unserialize($value),
        };
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the FirestoreClient instance.
     */
    public function getClient(): FirestoreClient
    {
        return $this->firestore;
    }
}
