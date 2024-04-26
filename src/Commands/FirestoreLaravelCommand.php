<?php

declare(strict_types=1);

namespace RichanFongdasen\Firestore\Commands;

use Illuminate\Console\Command;

class FirestoreLaravelCommand extends Command
{
    public $signature = 'firestore-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
