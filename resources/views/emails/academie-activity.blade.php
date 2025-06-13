<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Academy Activity</title>
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
        .activity-details {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #07F468;
            margin: 15px 0;
        }
        .activity-details h2 {
            color: #252525;
            margin-top: 0;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .activity-details p {
            margin: 8px 0;
            font-size: 15px;
        }
        .dates {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
        }
        .date-badge {
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            width: 45%;
        }
        .date-badge p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        .date-badge strong {
            font-size: 16px;
            display: block;
            margin-top: 5px;
            color: #252525;
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
            .dates {
                flex-direction: column;
            }
            .date-badge {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="logo">TERRANA <span>FC</span></h2>
            <h1>New Academy Activity</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>We're excited to announce a new activity at our academy:</p>
            
            <div class="activity-details">
                <h2>{{ $title }}</h2>
                <p>{{ $description }}</p>
                
                <div class="dates">
                    <div class="date-badge">
                        <p>Start Date</p>
                        <strong>{{ $dateStart }}</strong>
                    </div>
                    <div class="date-badge">
                        <p>End Date</p>
                        <strong>{{ $dateEnd }}</strong>
                    </div>
                </div>
            </div>
            
            <p>Don't miss this opportunity! Visit your academy portal for more details.</p>
            
            <a href="http://localhost:8000/events" class="button">View Details</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Terrana FC. All rights reserved.</p>
            <p>Contact: academy@terranafc.com | +212 600-000000</p>
        </div>
    </div>
</body>
</html> 