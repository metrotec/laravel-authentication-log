# Upgrade Guide

## Upgrading from v3.x to v4.x

Version 4.x introduces new features that require additional database columns. This guide will help you upgrade your existing installation.

### What's New in v4.x

- Device fingerprinting
- Suspicious activity detection
- Session management
- Device trust management
- Webhook support
- Export functionality
- Enhanced query scopes

### Upgrade Steps

1. **Update the package:**
   ```bash
   composer update rappasoft/laravel-authentication-log
   ```

2. **Publish the upgrade migration:**
   ```bash
   php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
   ```

3. **Run the migrations:**
   ```bash
   php artisan migrate
   ```

   The upgrade migration (`*_add_new_features_to_authentication_log_table.php`) will:
   - Check if each new column already exists
   - Only add columns that don't exist
   - Preserve all existing data
   - Set safe default values for new columns

### New Database Columns

The following columns will be added to your `authentication_log` table:

- `device_id` (string, nullable, indexed) - Unique device fingerprint
- `device_name` (string, nullable) - Human-readable device name
- `is_trusted` (boolean, default: false) - Whether the device is trusted
- `last_activity_at` (timestamp, nullable) - Last activity timestamp
- `is_suspicious` (boolean, default: false) - Suspicious activity flag
- `suspicious_reason` (string, nullable) - Reason for suspicious flag

### Backward Compatibility

- **Laravel 10.x**: The package will continue to work with basic logging functionality. New features are disabled automatically.
- **Laravel 11.x and 12.x**: All new features are available.

### Rollback

If you need to rollback the upgrade migration:

```bash
php artisan migrate:rollback --step=1
```

This will remove the new columns. **Warning:** This will delete data in those columns.

### Troubleshooting

**Issue: Migration fails with "Column already exists"**

This shouldn't happen as the migration checks for column existence. If it does, you can manually check and skip adding existing columns.

**Issue: Existing logs don't have device_id**

This is expected. Only new authentication logs will have device fingerprints. Existing logs will have `null` values for new columns, which is safe.

**Issue: Features not working after upgrade**

- Ensure you're running Laravel 11.x or 12.x for new features
- Check that migrations ran successfully: `php artisan migrate:status`
- Clear config cache: `php artisan config:clear`

### Need Help?

If you encounter any issues during the upgrade, please:
1. Check the [documentation](https://rappasoft.com/docs/laravel-authentication-log)
2. Review the [changelog](CHANGELOG.md)
3. Open an issue on [GitHub](https://github.com/rappasoft/laravel-authentication-log/issues)

