<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
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
        .reservation-details {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #07F468;
            margin: 15px 0;
        }
        .payment-details {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0074D9;
            margin: 15px 0;
        }
        .reservation-details p, .payment-details p {
            margin: 8px 0;
            font-size: 15px;
        }
        .reservation-details strong, .payment-details strong {
            color: #252525;
            font-weight: 600;
            display: inline-block;
            width: 140px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            background-color: #07F468;
            color: #252525;
        }
        .payment-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            background-color: #0074D9;
            color: #ffffff;
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
        .invoice-button {
            display: inline-block;
            background-color: #0074D9;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
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
            .reservation-details strong, .payment-details strong {
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
            <h1>Payment Confirmation</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>Your payment has been received and your reservation is confirmed!</p>
            
            <div class="reservation-details">
                <p><strong>Reservation Number:</strong> {{ $numRes }}</p>
                <p><strong>Date:</strong> {{ $date }}</p>
                <p><strong>Time:</strong> {{ $time }}</p>
                <p><strong>Field/Court:</strong> {{ $terrain }}</p>
                <p><strong>Status:</strong> <span class="status-badge">{{ $status }}</span></p>
            </div>
            
            <div class="payment-details">
                <p><strong>Payment Method:</strong> <span class="payment-badge">{{ $payment_method }}</span></p>
                @if(isset($amount))
                <p><strong>Amount:</strong> {{ $amount }} {{ $currency }}</p>
                @endif
                <p><strong>Payment Status:</strong> Completed</p>
            </div>
            
            <p>Thank you for your payment. If you need to make any changes to your reservation, please contact us or log in to your account.</p>
            
            <div class="button-container">
                <a href="https://moulweb.com/profile" class="button">Manage Reservation</a>
                <p style="margin: 15px 0 5px;">Your invoice is attached to this email.</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Terrana FC. All rights reserved.</p>
            <p>Contact: info@terranafc.com | +212 600-000000</p>
        </div>
    </div>
</body>
</html> 