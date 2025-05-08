<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your New Account Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4a6fdc;
            margin: 0;
            font-size: 28px;
        }
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .credentials {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #4a6fdc;
            margin: 15px 0;
        }
        .credentials p {
            margin: 8px 0;
        }
        .credentials strong {
            color: #3a559f;
        }
        .button {
            display: inline-block;
            background-color: #4a6fdc;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Platform!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>Thank you for making a reservation with us! We have created an account for you to manage your reservations and access other services on our platform.</p>
            
            <p>Your reservation number is: <strong>{{ $numRes }}</strong></p>
            
            <div class="credentials">
                <p><strong>Your Account Details:</strong></p>
                <p><strong>Email:</strong> {{ $email }}</p>
                <p><strong>Password:</strong> {{ $password }}</p>
            </div>
            
            <p>For security reasons, we recommend changing your password after your first login. You can update your profile information and manage your reservations through your account.</p>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <a href="{{ url('/login') }}" class="button">Login to Your Account</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Our Reservation Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 