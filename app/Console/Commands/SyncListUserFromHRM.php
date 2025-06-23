<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\SyncListUserFromHRMController;
use GuzzleHttp\Client;

class SyncListUserFromHRM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrm:sync-list-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync list users from HRM every hour';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new SyncListUserFromHRMController(new Client());
        $response = $controller->syncListUser(new Request());
        $this->info('User sync: ' . $response->getContent());
        return 0;
    }
}
