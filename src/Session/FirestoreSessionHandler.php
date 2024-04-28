<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore\Session;

use RichanFongdasen\Firestore\Cache\FirestoreStore;
use SessionHandlerInterface;

class FirestoreSessionHandler implements SessionHandlerInterface
{
    protected FirestoreStore $store;

    protected int $minutes;

    public function __construct(FirestoreStore $store, int $minutes)
    {
        $this->store = $store;
        $this->minutes = $minutes;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        return $this->store->forget($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        return $this->store->get($id, '');
    }

    public function write(string $id, string $data): bool
    {
        return $this->store->put($id, $data, $this->minutes * 60);
    }
}
