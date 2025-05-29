<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tournament Announcement</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #252525;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .header {
            text-align: center;
            padding: 20px;
            background-color: #252525;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            margin: 0;
        }
        .logo span {
            color: #07F468;
        }
        .header h1 {
            color: #ffffff;
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            background-color: white;
            padding: 25px 20px;
        }
        .tournament-details {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #07F468;
            margin: 15px 0;
        }
        .tournament-details h2 {
            color: #252525;
            margin-top: 0;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .tournament-details p {
            margin: 8px 0;
            font-size: 15px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-item strong {
            display: block;
            margin-bottom: 5px;
            color: #252525;
        }
        .award {
            background-color: #07F468;
            color: #252525;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 5px;
        }
        .button {
            display: inline-block;
            background-color: #07F468;
            color: #252525;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f3f3f3;
            font-size: 14px;
            color: #777;
        }
        @media screen and (max-width: 480px) {
            .content {
                padding: 20px 15px;
            }
            .details-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="logo">TERRANA <span>FC</span></h2>
            <h1>New Tournament</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>We're excited to announce a new tournament:</p>
            
            <div class="tournament-details">
                <h2>{{ $tournamentName }}</h2>
                <p>{{ $description }}</p>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Tournament Type</strong>
                        {{ $type }}
                    </div>
                    
                    <div class="detail-item">
                        <strong>Team Capacity</strong>
                        {{ $capacity }} teams
                    </div>
                    
                    <div class="detail-item">
                        <strong>Start Date</strong>
                        {{ $dateStart }}
                    </div>
                    
                    <div class="detail-item">
                        <strong>End Date</strong>
                        {{ $dateEnd }}
                    </div>
                    
                    <div class="detail-item">
                        <strong>Entry Fee</strong>
                        {{ $fee }}
                    </div>
                    
                    <div class="detail-item">
                        <strong>Prize</strong>
                        <span class="award">{{ $award }}</span>
                    </div>
                </div>
            </div>
            
            <p>Register your team early to secure your spot!</p>
            
            <a href="{{ url('/tournaments') }}" class="button">Register Now</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Terrana FC. All rights reserved.</p>
            <p>Contact: tournaments@terranafc.com | +212 600-000000</p>
        </div>
    </div>
</body>
</html> 