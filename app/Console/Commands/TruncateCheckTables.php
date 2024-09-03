<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateCheckTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:truncate-check-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
        truncate table check_products_views and check_users_visites
        daily for not to increase the size of DB
    ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //

        DB::table('check_visite_views')->truncate();
        DB::table('check_products_views')->truncate();
        $this->info('The tables has been truncated');

    }
}
