<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $numRes }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #252525;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #252525;
            margin: 0;
        }
        .logo span {
            color: #07F468;
        }
        .invoice-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin: 20px 0;
            border-bottom: 1px solid #eeeeee;
            padding-bottom: 10px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .invoice-details .label {
            font-weight: bold;
            width: 40%;
        }
        .reservation-details {
            background-color: #f8f8f8;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
        }
        .reservation-details table {
            width: 100%;
        }
        .reservation-details td {
            padding: 8px 0;
        }
        .reservation-details .label {
            font-weight: bold;
            width: 40%;
        }
        .payment-details {
            margin-bottom: 30px;
        }
        .payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-details th {
            background-color: #f3f3f3;
            text-align: left;
            padding: 10px;
            font-weight: bold;
        }
        .payment-details td {
            padding: 10px;
            border-bottom: 1px solid #eeeeee;
        }
        .payment-total {
            margin-top: 20px;
            text-align: right;
        }
        .payment-total table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .payment-total td {
            padding: 5px 10px;
        }
        .payment-total .total-label {
            font-weight: bold;
            text-align: left;
        }
        .payment-total .total-value {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background-color: #07F468;
            color: #252525;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="logo">TERRANA <span>FC</span></h2>
            <p>123 Sport Street, Casablanca, Morocco</p>
            <p>Email: info@terranafc.com | Phone: +212 600-000000</p>
        </div>
        
        <h1 class="invoice-title">INVOICE</h1>
        
        <div class="invoice-details">
            <table>
                <tr>
                    <td class="label">Invoice Number:</td>
                    <td>INV-{{ $numRes }}</td>
                </tr>
                <tr>
                    <td class="label">Date Issued:</td>
                    <td>{{ date('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td class="label">Client:</td>
                    <td>{{ $name }}</td>
                </tr>
            </table>
        </div>
        
        <div class="reservation-details">
            <table>
                <tr>
                    <td class="label">Reservation Number:</td>
                    <td>{{ $numRes }}</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td>{{ $date }}</td>
                </tr>
                <tr>
                    <td class="label">Time:</td>
                    <td>{{ $time }}</td>
                </tr>
                <tr>
                    <td class="label">Field/Court:</td>
                    <td>{{ $terrain }}</td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td><span class="status-badge">{{ $status }}</span></td>
                </tr>
            </table>
        </div>
        
        <div class="payment-details">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $terrain }} - {{ $date }} at {{ $time }}</td>
                        <td>1</td>
                        <td>{{ $amount }} {{ strtoupper($currency) }}</td>
                        <td>{{ $amount }} {{ strtoupper($currency) }}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="payment-total">
                <table>
                    <tr>
                        <td class="total-label">Total:</td>
                        <td class="total-value">{{ $amount }} {{ strtoupper($currency) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing Terrana FC</p>
            <p>This invoice was automatically generated and does not require a signature.</p>
            <p>&copy; {{ date('Y') }} Terrana FC. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 