# Email Configuration Guide

## Overview
The reservation system now includes email notification functionality that sends account details to users when a new account is created during reservation. To make this work, you need to configure your email settings in the `.env` file.

## Configuration Steps

1. **Open your `.env` file** in the root of your project.

2. **Add or update these mail configuration settings**:

```
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Reservation System"
```

3. **Replace the values with your actual mail provider details**:

### For Gmail:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.gmail@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your.gmail@gmail.com
MAIL_FROM_NAME="Reservation System"
```

Note: For Gmail, you'll need to use an "App Password" instead of your regular Gmail password. You can generate one at: https://myaccount.google.com/apppasswords

### For Mailtrap (Testing):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=from@example.com
MAIL_FROM_NAME="Reservation System"
```

### For Local Testing:
If you want to test without sending real emails:
```
MAIL_MAILER=log
```
This will log emails to `storage/logs` instead of sending them.

## Testing Your Email Configuration

You can test your email configuration using the built-in test command:

```bash
php artisan mail:test youremail@example.com
```

This will send a test email to the specified address and display the current mail configuration.

## Troubleshooting

If emails are not being sent:

1. Check your `.env` file for correct mail settings.
2. Make sure your mail provider allows SMTP connections.
3. For Gmail, make sure you're using an App Password.
4. Check your application logs (`storage/logs/laravel.log`).
5. Verify firewall settings aren't blocking outgoing SMTP connections.
6. Check if your hosting provider allows sending emails via SMTP.

## Additional Resources

- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [Gmail App Passwords](https://support.google.com/accounts/answer/185833)
- [Mailtrap for Testing](https://mailtrap.io/) 