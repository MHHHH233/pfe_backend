<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Terrana FC - Google Account Created</title>
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
        .credentials {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #07F468;
            margin: 15px 0;
        }
        .credentials p {
            margin: 8px 0;
            font-size: 15px;
        }
        .credentials strong {
            color: #252525;
            font-weight: 600;
            display: inline-block;
            width: 80px;
        }
        .google-info {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4285F4;
            margin: 15px 0;
        }
        .google-info img {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 8px;
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
            .credentials strong {
                width: 100%;
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="logo">TERRANA <span>FC</span></h2>
            <h1>Welcome to Terrana FC</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>Welcome to Terrana FC! Your account has been successfully created using your Google account.</p>
            
            <div class="google-info">
                <p><strong>Google Account:</strong> {{ $email }}</p>
            </div>
            
            <div class="credentials">
                <p><strong>Email:</strong> {{ $email }}</p>
                <p><strong>Password:</strong> {{ $password }}</p>
            </div>
            
            <p>For security reasons, we recommend changing your password after your first login.</p>
            
            <p>You can now enjoy all the features of Terrana FC:</p>
            <ul>
                <li>Book football fields</li>
                <li>Join tournaments</li>
                <li>Connect with other players</li>
                <li>Track your reservations</li>
            </ul>
            
            <a href="https://moulweb.com/profile" class="button">Login Now</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Terrana FC. All rights reserved.</p>
            <p>Contact: support@terranafc.com | +212 600-000000</p>
        </div>
    </div>
</body>
</html> 