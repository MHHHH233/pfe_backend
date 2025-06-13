$projectPath = "C:\xampp\htdocs\PFE\pfe_backend"
Set-Location $projectPath

while ($true) {
    Start-Process -FilePath "php" -ArgumentList "artisan", "schedule:run" -NoNewWindow -Wait
    Start-Sleep -Seconds 60
} 