<?php

namespace Komodo\RajaongkirLaravel\Commands;

use Illuminate\Console\Command;

class RajaongkirLaravelCommand extends Command
{
    public $signature = 'rajaongkir-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
