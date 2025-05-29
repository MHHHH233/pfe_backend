<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckReservationLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:check-limits {date? : The date to check (YYYY-MM-DD format, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check users who have exceeded the daily reservation limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ?? Carbon::today()->format('Y-m-d');
        
        // Get all clients with their reservation count for the specified date
        $clientsWithCounts = DB::table('reservation')
            ->select('id_client', DB::raw('COUNT(*) as reservation_count'))
            ->where('date', $date)
            ->where('etat', '!=', 'annuler')
            ->whereNotNull('id_client')
            ->groupBy('id_client')
            ->having('reservation_count', '>', 2)
            ->get();
            
        if ($clientsWithCounts->isEmpty()) {
            $this->info("No users have exceeded the daily reservation limit for $date");
            return Command::SUCCESS;
        }
        
        $this->info("Users who have exceeded the daily reservation limit for $date:");
        $this->table(
            ['Client ID', 'Reservation Count'],
            $clientsWithCounts->map(function($item) {
                return [
                    'Client ID' => $item->id_client,
                    'Reservation Count' => $item->reservation_count
                ];
            })
        );
        
        if ($this->confirm('Do you want to cancel excess reservations to enforce the limit?')) {
            foreach ($clientsWithCounts as $client) {
                // Get all reservations for this client on this date
                $reservations = Reservation::where('id_client', $client->id_client)
                    ->where('date', $date)
                    ->where('etat', '!=', 'annuler')
                    ->orderBy('created_at', 'desc') // Keep the most recent reservations
                    ->get();
                
                // Cancel all reservations beyond the limit of 2
                $toCancel = $reservations->slice(2);
                foreach ($toCancel as $reservation) {
                    $reservation->etat = 'annuler';
                    $reservation->save();
                    $this->info("Cancelled reservation #{$reservation->id_reservation} for client #{$client->id_client}");
                }
                
                Log::info("Enforced reservation limit: Cancelled " . count($toCancel) . " excess reservations for client #{$client->id_client}");
            }
        }
        
        return Command::SUCCESS;
    }
}
