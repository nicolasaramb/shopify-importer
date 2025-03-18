<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CronSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener estadísticas
        $totalProducts = Product::count();
        $lastSyncDate = Product::max('last_synced_at');
        
        // Create Carbon instance if we have a date
        $lastSync = $lastSyncDate ? Carbon::parse($lastSyncDate) : null;
        
        // Format for display
        $lastSyncFormatted = $lastSync ? $lastSync->format('Y-m-d') : 'N/A';
        $lastSyncTime = $lastSync ? $lastSync->format('H:i') : '';
        
        // Count products in last sync
        $productsInLastSync = 0;
        if ($lastSync) {
            $productsInLastSync = Product::whereDate('last_synced_at', $lastSync->toDateString())->count();
        }

        // Obtener los últimos productos importados
        $latestProducts = Product::orderBy('last_synced_at', 'desc')
                                ->take(10)
                                ->get();
        
        // Obtener información del próximo cron
        $cronInfo = CronSchedule::where('job_name', 'SyncProductsJob')->first();
        $nextCronRun = 'No programado';
        $nextCronRunTime = '';
        
        if ($cronInfo && $cronInfo->next_run) {
            $nextRun = Carbon::parse($cronInfo->next_run);
            $nextCronRun = $nextRun->format('Y-m-d');
            $nextCronRunTime = $nextRun->format('H:i');
        }

        return view('dashboard', compact(
            'totalProducts', 
            'lastSync',
            'lastSyncFormatted', 
            'lastSyncTime', 
            'productsInLastSync', 
            'latestProducts',
            'nextCronRun',
            'nextCronRunTime'
        ));
    }
}