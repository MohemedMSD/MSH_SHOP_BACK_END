<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\Visite;
use App\Models\CheckVisiteView;

class TrachHomePageVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentMonth = Carbon::today();
        $MonthVisites = Visite::whereMonth('duration', $currentMonth->month)
        ->first();

        $checkUserVisite = CheckVisiteView::where('user_agent', $request->header('User-Agent'))
        ->where('ip_adress', $request->ip())
        ->where('check_type', 'visites')
        ->orderBy('visited_at', 'desc')
        ->first();

        if(isset($checkUserVisite)){

            $now = Carbon::now();
            
            if($now->diffinMinutes($checkUserVisite->visited_at) >= 45){

                if (isset($MonthVisites)) {
                
                    $MonthVisites->increment('count');
                    
                }else{
    
                    Visite::create([
                        'duration' => $currentMonth,
                        'count' => 1
                    ]);
    
                }

            }
            
            $checkUserVisite->update([
                'visited_at' => now()
            ]);

        }else{

            CheckVisiteView::create([
                'ip_adress' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'check_type' => 'visites',
                'visited_at' => now()
            ]);

            if (isset($MonthVisites)) {
                
                $MonthVisites->increment('count');
                
            }else{

                Visite::create([
                    'duration' => $currentMonth,
                    'count' => 1
                ]);

            }


        }

        return $next($request);
    }
}
