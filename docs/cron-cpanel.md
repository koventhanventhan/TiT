# cPanel Cron Jobs Configuration for TiT Education

For the Laravel backend to function correctly on cPanel shared hosting, you must configure the following cron jobs in your cPanel dashboard.

cPanel usually has a minimum cron interval of 15 minutes. The application has been designed to work perfectly within this constraint.

## Recommended Cron Jobs

Add the following commands to your cPanel Cron Jobs interface. **Note:** You must replace `/usr/local/bin/php` with the correct path to PHP 8.2 on your server, and `/home/titjaffn/public_html/...` with the actual path to your application.

### 1. The Laravel Scheduler (Run every 15 minutes)

```bash
*/15 * * * * /usr/local/bin/php /home/titjaffn/public_html/laravel_api/backend/artisan schedule:run >> /home/titjaffn/schedule.log 2>&1
```

*This single command handles everything, including Zoom reminders (`zoom:send-reminders`) and timetable syncing (`zoom:sync-timetable`). You do **not** need to add separate cron jobs for those commands, because `schedule:run` already triggers them automatically based on the schedule defined in `routes/console.php`.*

Logging the output to `schedule.log` is highly recommended for debugging.

### 2. Daily Cache Clearing (Run once a day at 2:00 AM)

```bash
0 2 * * * /usr/local/bin/php /home/titjaffn/public_html/laravel_api/backend/artisan optimize:clear >> /dev/null 2>&1
```

*This ensures the application cache is cleared daily. Do not modify this unless instructed by the system administrator.*
