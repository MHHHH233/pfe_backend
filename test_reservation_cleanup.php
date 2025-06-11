<?php
// Load Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== Reservation Cleanup Test ===\n\n";

// Check total reservations
$totalCount = Reservation::count();
echo "Total reservations: {$totalCount}\n";

// Check pending reservations
$pendingCount = Reservation::where('etat', 'en attente')->count();
echo "Pending reservations: {$pendingCount}\n";

// Check pending reservations older than 1 hour
$oneHourAgo = Carbon::now()->subHour();
$oldPendingCount = Reservation::where('etat', 'en attente')
    ->where('created_at', '<', $oneHourAgo)
    ->count();
echo "Pending reservations older than 1 hour: {$oldPendingCount}\n";

// Show the oldest pending reservation
$oldestPending = Reservation::where('etat', 'en attente')->orderBy('created_at')->first();
if ($oldestPending) {
    echo "\nOldest pending reservation:\n";
    echo "ID: {$oldestPending->id_reservation}\n";
    echo "Created at: {$oldestPending->created_at}\n";
    echo "Date: {$oldestPending->date}\n";
    echo "Time: {$oldestPending->heure}\n";
    
    // Calculate how old this reservation is
    $ageInHours = Carbon::now()->diffInHours($oldestPending->created_at);
    echo "Age: {$ageInHours} hours\n";
}

// Check reservations with future dates
$futureReservations = Reservation::where('date', '>', now()->format('Y-m-d'))->count();
echo "\nFuture reservations: {$futureReservations}\n";

// Show the date format in the database
echo "\nDate format example:\n";
$sampleReservation = Reservation::first();
if ($sampleReservation) {
    echo "Date stored as: {$sampleReservation->date} (PHP type: " . gettype($sampleReservation->date) . ")\n";
    echo "Time stored as: {$sampleReservation->heure} (PHP type: " . gettype($sampleReservation->heure) . ")\n";
}

// Test the query used in the scheduler
$pastReservationsCount = Reservation::whereRaw("CONCAT(date, ' ', heure) < ?", [now()->format('Y-m-d H:i:s')])->count();
echo "\nPast reservations (date+time < now): {$pastReservationsCount}\n";

echo "\n=== End of Test ===\n"; 