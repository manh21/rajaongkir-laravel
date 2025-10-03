<?php

namespace Komodo\RajaOngkirLaravel\Commands;

use Illuminate\Console\Command;

class RajaOngkirLaravelCommand extends Command
{
    public $signature = 'rajaongkir-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
