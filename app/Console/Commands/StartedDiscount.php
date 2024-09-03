<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Discount;
use Carbon\Carbon;

class StartedDiscount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:started-discount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //

        $todayDate = Carbon::now();

        $discounts = Discount::whereDate('start_date', $todayDate)
        ->orWhereDate('end_date', $todayDate)
        ->get();

        foreach($discounts as $discount){
            
            $start_date = Carbon::parse($discount->start_date);
            $end_date = Carbon::parse($discount->end_date);

            if ($start_date->isSameDay($todayDate)) {
                
                $discount->update([
                    'active' => 1
                ]);
                
            }

            if ($end_date->isSameDay($todayDate)) {
                
                $discount->update([
                    'active' => 0
                ]);
                
            }

        }

        return "command make it work succefully";

    }
}
