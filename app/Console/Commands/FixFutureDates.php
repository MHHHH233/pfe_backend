<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FixFutureDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:fix-dates {--id=0 : Fix a specific reservation ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix reservations with future created_at dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificId = (int)$this->option('id');
        
        if ($specificId > 0) {
            // Fix a specific reservation
            $reservation = Reservation::find($specificId);
            
            if (!$reservation) {
                $this->error("Reservation with ID $specificId not found");
                return Command::FAILURE;
            }
            
            $this->info("Found reservation ID: {$reservation->id_reservation}, Created at: {$reservation->created_at}");
            
            if ($this->confirm('Do you want to fix this reservation date?')) {
                // Convert the future date to the current year
                $oldDate = Carbon::parse($reservation->created_at);
                $newDate = Carbon::now()->subHours(2); // Set to 2 hours ago to make it eligible for cleanup
                
                // Update the reservation
                $reservation->created_at = $newDate;
                $reservation->updated_at = $newDate;
                $reservation->save();
                
                $this->info("Fixed reservation ID {$reservation->id_reservation}. New created_at: {$reservation->created_at}");
            }
            
            return Command::SUCCESS;
        }
        
        // Find reservations with future created_at dates (more than 1 day in the future)
        $now = Carbon::now();
        $futureReservations = Reservation::where('created_at', '>', $now->copy()->addDay())->get();
        
        $this->info("Found " . $futureReservations->count() . " reservations with future created_at dates");
        
        if ($futureReservations->count() > 0) {
            // Show some examples
            $this->info("Examples:");
            foreach ($futureReservations->take(5) as $reservation) {
                $this->info("ID: {$reservation->id_reservation}, Created at: {$reservation->created_at}");
            }
            
            if ($this->confirm('Do you want to fix these dates by changing the year to the current year?')) {
                $fixedCount = 0;
                
                foreach ($futureReservations as $reservation) {
                    // Convert the future date to the current year
                    $oldDate = Carbon::parse($reservation->created_at);
                    $newDate = Carbon::now()->setMonth($oldDate->month)
                                           ->setDay($oldDate->day)
                                           ->setHour($oldDate->hour)
                                           ->setMinute($oldDate->minute)
                                           ->setSecond($oldDate->second);
                    
                    // Update the reservation
                    $reservation->created_at = $newDate;
                    $reservation->updated_at = $newDate;
                    $reservation->save();
                    
                    $fixedCount++;
                }
                
                $this->info("Fixed $fixedCount reservations with future created_at dates");
            }
        }
        
        return Command::SUCCESS;
    }
} 