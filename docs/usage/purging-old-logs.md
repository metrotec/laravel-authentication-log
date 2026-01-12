---
title: Purging and Exporting Logs
weight: 4
---

## Purging Old Logs

You may clear the old authentication log records using the `authentication-log:purge` Artisan command:

```
php artisan authentication-log:purge
```

Records that are older than the number of days specified in the `purge` option in your `config/authentication-log.php` will be deleted.

```php
'purge' => 365,
```

You can also schedule the command at an interval:

```php
$schedule->command('authentication-log:purge')->monthly();
```

## Exporting Logs

Export authentication logs to CSV or JSON format using the `authentication-log:export` command:

### Basic Export

```bash
# Export all logs to CSV
php artisan authentication-log:export --format=csv

# Export all logs to JSON
php artisan authentication-log:export --format=json
```

### Filtered Export

```bash
# Export logs from last 30 days
php artisan authentication-log:export --format=csv --days=30

# Export logs for specific user
php artisan authentication-log:export --format=json --user=1

# Specify output file
php artisan authentication-log:export --format=csv --output=logs.csv

# Combine filters
php artisan authentication-log:export --format=csv --days=7 --user=1 --output=user-logs.csv
```

### Export Options

- `--format`: Export format (`csv` or `json`) - default: `csv`
- `--days`: Export logs from last N days
- `--user`: Filter by user ID
- `--output`: Output file path (default: `authentication-logs-{format}-{timestamp}.{ext}`)

### Export Contents

The export includes:
- Log ID
- User information (type, ID, email)
- IP address and user agent
- Device information (ID, name, trusted status)
- Login/logout timestamps
- Success status
- Suspicious activity flags
- Location data (if available)
