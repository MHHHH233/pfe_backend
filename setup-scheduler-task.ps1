$logFile = "C:\xampp\htdocs\PFE\pfe_backend\setup-scheduler-task.log"
Start-Transcript -Path $logFile -Append
$Action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\PFE\pfe_backend\run-scheduler-task.bat"
$Trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable
$Task = Register-ScheduledTask -TaskName "Laravel Scheduler" -Action $Action -Trigger $Trigger -Settings $Settings -Force

Write-Host "Task 'Laravel Scheduler' has been created to run every minute." 
Stop-Transcript