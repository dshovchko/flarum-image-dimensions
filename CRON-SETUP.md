# Cron Setup for Scheduled Checks

## Add to www-nobody crontab

Option 1 - Edit manually:
```bash
sudo crontab -u www-nobody -e
```

Add this line:
```
* * * * * cd /usr/local/servers/zabytki.in.ua/flarum-dev && php flarum schedule:run >> /dev/null 2>&1
```

Option 2 - Add via command:
```bash
(sudo crontab -u www-nobody -l 2>/dev/null; echo "* * * * * cd /usr/local/servers/zabytki.in.ua/flarum-dev && php flarum schedule:run >> /dev/null 2>&1") | sudo crontab -u www-nobody -
```

## Verify

Check scheduled tasks:
```bash
php flarum schedule:list
```

You should see:
```
image-dimensions:scheduled-check | [your frequency] | Scheduled automatic check...
```

## Test manually

```bash
php flarum schedule:run
```

This will run all scheduled tasks that are due.
