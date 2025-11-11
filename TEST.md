# Testing Guide for v1.4.0

## Prerequisites
- Flarum scheduler must be configured in crontab:
```bash
* * * * * cd /path/to/flarum && php flarum schedule:run >> /dev/null 2>&1
```

## Testing Steps

### 1. Admin Panel Configuration
1. Go to Admin → Extensions → Image Dimensions
2. You should see new settings:
   - **Enable Scheduled Checks** (checkbox)
   - **Check Frequency** (dropdown: daily/weekly/monthly)
   - **Check Mode** (dropdown: fast/default/full)
   - **Batch Size** (number input, default: 100)
   - **Email Recipients** (text input for comma-separated emails)

### 2. Enable Scheduled Checks
1. Check "Enable Scheduled Checks"
2. Set frequency to "Weekly"
3. Set mode to "Fast" (recommended for large forums)
4. Set batch size to 50-100
5. Add your email: `admin@example.com`
6. Save settings

### 3. Verify Scheduler Registration
```bash
php flarum schedule:list
```
You should see:
```
image-dimensions:scheduled-check | [frequency] | Scheduled automatic check...
```

### 4. Manual Test (Optional)
The scheduled command runs automatically, but you can test manually:
```bash
# This will only run if enabled in admin panel
php flarum image-dimensions:scheduled-check
```

### 5. Check Email
After the scheduled run, you should receive an email report with:
- Discussion IDs checked
- Posts with correct images
- Posts with wrong/missing dimensions
- Any errors encountered

## Check Modes Explained

- **Fast**: Only verifies width/height attributes exist (fastest, recommended for regular checks)
- **Default**: Verifies attributes + checks if image URLs are valid (moderate speed)
- **Full**: Verifies exact dimensions by downloading images (slowest, most thorough)

## Troubleshooting

### Scheduler not running
- Verify crontab is configured
- Check `php flarum schedule:list` shows the command
- Ensure "Enable Scheduled Checks" is checked in admin panel

### No email received
- Check email recipients are correctly formatted
- Verify Flarum mail configuration is working
- Check server logs for errors

### Command takes too long
- Reduce batch size
- Use "Fast" mode instead of "Full"
- Consider running less frequently (monthly instead of weekly)
