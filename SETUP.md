# Setup Guide

## First-Time Setup

### 1. Install Dependencies (if not already done)

```powershell
# Using Docker (recommended for Windows with Sail)
docker run --rm `
    -v "${PWD}:/var/www/html" `
    -w /var/www/html `
    laravelsail/php83-composer:latest `
    composer install --ignore-platform-reqs
```

### 2. Configure Environment

Your `.env` file should already exist. Verify database settings:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=redirect_manager
DB_USERNAME=sail
DB_PASSWORD=password
```

### 3. Start Laravel Sail

```powershell
./vendor/bin/sail up -d
```

This starts:
- MySQL database
- Redis cache
- Mailpit (email testing)
- The Laravel application

### 4. Run Database Migrations

```powershell
./vendor/bin/sail artisan migrate
```

This creates:
- `redirects` table
- `redirect_logs` table
- `health_check_result_history_items` table

### 5. (Optional) Seed Sample Data

For development and testing, seed example redirects:

```powershell
./vendor/bin/sail artisan db:seed
```

This creates 15 sample redirects demonstrating all features. Run `sail artisan redirect:list` to see them.

### 6. Verify Installation

Check that the application is running:

```powershell
./vendor/bin/sail artisan redirect:list
```

If you seeded data, you'll see 15 sample redirects. Otherwise "No redirects found".

### 7. Run Health Checks

Verify all systems are operational:

```powershell
./vendor/bin/sail artisan health:check
```

All checks should pass (green). This validates:
- Database connectivity
- Cache functionality
- Environment configuration
- Disk space
- Application settings

## Testing the Application

### Run Automated Tests

```powershell
./vendor/bin/sail artisan test
```

All tests should pass, verifying:
- URL redirects work
- Wildcard redirects work
- Priority system works
- Analytics logging works
- Inactive redirects are ignored

### Create a Test Redirect

```powershell
# Add a test redirect
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/test `
  --destination=https://google.com `
  --status=302

# List redirects to verify
./vendor/bin/sail artisan redirect:list
```

### Test the Redirect

Visit `http://localhost/test` in your browser. You should be redirected to Google.

### Check Analytics

```powershell
./vendor/bin/sail artisan redirect:analytics --recent
```

You should see your test request logged.

## Daily Usage

### Starting the Application

```powershell
./vendor/bin/sail up -d
```

### Stopping the Application

```powershell
./vendor/bin/sail down
```

### Viewing Logs

```powershell
# Application logs
./vendor/bin/sail artisan pail

# Or Docker logs
./vendor/bin/sail logs -f
```

## Troubleshooting

### Ports Already in Use

If ports 80, 3306, or 6379 are already in use, modify `compose.yaml`:

```yaml
services:
    laravel.test:
        ports:
            - '8080:80'  # Changed from 80:80
```

Then access the app at `http://localhost:8080`

### Database Connection Issues

Ensure Sail is running:
```powershell
./vendor/bin/sail ps
```

Reset the database:
```powershell
./vendor/bin/sail artisan migrate:fresh
```

### Migrations Already Run

If you see "Table already exists" errors:
```powershell
./vendor/bin/sail artisan migrate:fresh
```

**Warning:** This will delete all data!

### Clear Cache Issues

```powershell
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
```

## Production Deployment

### Environment Configuration

1. Update `.env` for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use production database credentials
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

2. Run optimizations:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Run migrations:
```bash
php artisan migrate --force
```

### Performance Considerations

For high-traffic redirects:

1. **Cache redirects in memory**: Modify `HandleRedirects` middleware to cache active redirects
2. **Database indexes**: Already included in migrations
3. **Log rotation**: Set up a scheduled task to archive old logs:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Archive logs older than 90 days
    $schedule->call(function () {
        \App\Models\RedirectLog::where('created_at', '<', now()->subDays(90))->delete();
    })->daily();
}
```

4. **Queue logging**: For very high traffic, queue the analytics logging:
```php
// Modify HandleRedirects::logRedirect() to dispatch a job instead
dispatch(new LogRedirect($request, $redirect, $fullUrl));
```

## Backup Strategy

### Database Backup

```powershell
# Export redirects
./vendor/bin/sail exec mysql mysqldump -u sail -p redirect_manager redirects > redirects_backup.sql

# Export logs
./vendor/bin/sail exec mysql mysqldump -u sail -p redirect_manager redirect_logs > logs_backup.sql
```

### Restore from Backup

```powershell
./vendor/bin/sail exec -T mysql mysql -u sail -p redirect_manager < redirects_backup.sql
```

## Useful Sail Commands

```powershell
# Access MySQL CLI
./vendor/bin/sail mysql

# Access container shell
./vendor/bin/sail shell

# Run artisan commands
./vendor/bin/sail artisan [command]

# View running containers
./vendor/bin/sail ps

# Restart all containers
./vendor/bin/sail restart
```

## Next Steps

1. Read the [README.md](README.md) for feature overview
2. Check [COMMANDS.md](COMMANDS.md) for quick command reference
3. Add your first production redirects
4. Monitor analytics regularly

## Support

For Laravel Sail issues, see: https://laravel.com/docs/sail
For Laravel documentation: https://laravel.com/docs
