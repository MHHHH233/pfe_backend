# Testing the Email Functionality

To test the email functionality for sending account details when a new reservation is created, follow these steps:

## Prerequisites
1. Make sure you've configured your email settings in the `.env` file as described in the `mail-configuration-guide.md` file.

## Testing Methods

### Method 1: Test Command
You can use the provided test command to send a sample email:

```bash
# Register the command (if it's not automatically registered)
php artisan make:command TestEmailCommand

# Run the test command
php artisan mail:test your-email@example.com
```

### Method 2: Create a Test Reservation
1. Set your `.env` file to use a testing mail driver like Mailtrap or log:
   ```
   MAIL_MAILER=log
   ```

2. Make a reservation request with the following criteria:
   - Do NOT include an `id_client` (leave it null)
   - Include an email address
   - Include a name
   
   Example API request to test (using Postman or similar tool):
   ```
   POST /api/user/v1/reservations
   
   {
     "id_terrain": 1,
     "date": "2023-12-31",
     "heure": "14:00:00",
     "email": "test@example.com",
     "guest_name": "Test User",
     "type": "client"
   }
   ```

3. Check the logs to see if the email was sent:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Method 3: Debug Mode
You can temporarily modify the ReservationController to always send the test email:

1. Edit `app/Http/Controllers/Api/User/V1/ReservationController.php`
2. Add this code after generating the reservation number:
   ```php
   // Testing email - remove in production
   try {
       Mail::to('your-email@example.com')->send(new AccountCreated(
           'Test User',
           'your-email@example.com',
           'TestPassword123',
           $numRes
       ));
       \Illuminate\Support\Facades\Log::info('Test email sent successfully');
   } catch (\Exception $e) {
       \Illuminate\Support\Facades\Log::error('Test email failed: ' . $e->getMessage());
   }
   ```

## Troubleshooting

### Common Issues:

1. **No emails are being sent:**
   - Check your `.env` file for correct configuration
   - Ensure your mail server is running and accessible
   - Look for error messages in `storage/logs/laravel.log`

2. **Connection errors:**
   - Verify your host, port, username, and password
   - Some mail providers require using App Passwords (like Gmail)
   - Check firewall settings

3. **Emails are sent but not received:**
   - Check spam folders
   - Verify the recipient email address is correct
   - Some mail providers may block automated emails

### Testing with Mailtrap

For development, [Mailtrap](https://mailtrap.io/) is an excellent service for testing emails without sending them to real users:

1. Create a free Mailtrap account
2. Get your SMTP credentials from the Mailtrap inbox
3. Configure your `.env` file with the Mailtrap credentials
4. All emails will be captured in your Mailtrap inbox 