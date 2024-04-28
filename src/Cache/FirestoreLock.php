<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore\Cache;

use Illuminate\Cache\Lock;

class FirestoreLock extends Lock
{
    protected FirestoreStore $firestore;

    public function __construct(FirestoreStore $firestore, string $name, int $seconds, ?string $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->firestore = $firestore;
    }

    public function acquire(): bool
    {
        $seconds = ($this->seconds > 0) ? $this->seconds : 86400;

        return $this->firestore->add($this->name, $this->owner, $seconds);
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            return $this->firestore->forget($this->name);
        }

        return false;
    }

    /**
     * Release this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->firestore->forget($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        return (string) $this->firestore->get($this->name);
    }
}
