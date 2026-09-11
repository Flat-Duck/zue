# Deploying on the company network

One server, private network, self-signed certificate, no mail. This is what has
to be true on that box for the application to do its job, and how to check.

## Services

| What                | How it runs                          | If it stops                                   |
|---------------------|--------------------------------------|-----------------------------------------------|
| Web (nginx + PHP)   | the web server                       | nothing loads                                 |
| MySQL               | the database service                 | nothing loads                                 |
| Redis               | the redis service                    | sessions, cache and the queue stop            |
| Queue worker        | `systemd/zue-queue.service`          | approvals, notifications and backups stop, pages still load |
| Reverb              | `systemd/zue-reverb.service`         | live notifications stop, pages still load     |
| Scheduler           | `crontab`                            | backups and the nightly sync stop             |

The **System status** page (Maintenance → System status) shows whether each of
these is alive, and `/health` answers 200 or 503 for a monitoring tool. With
no mail on the network, that page and `storage/logs/laravel-*.log` are the
only places a failure is reported. Somebody has to look.

## Environment

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<host>

LOG_CHANNEL=daily            # rotated, 14 days kept
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=reverb

FORCE_HTTPS=true             # defaults on in production
SESSION_SECURE_COOKIE=true   # defaults on in production
CSP_ENFORCE=true
HSTS_ENABLED=false           # leave off while the certificate is self-signed

BACKUP_PATH=/var/www/zue/storage/app/backups   # a mounted share, when there is one
```

## Every deploy

```
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
sudo systemctl restart zue-queue zue-reverb
```

Never run `config:cache` on a development machine: a cached config ignores
`.env.testing` and the test suite will empty the development database. The
suite refuses to run in that state, but do not rely on that.

## Backups

Nightly, to `BACKUP_PATH`, each one a pair: `backup_<stamp>.sql` and
`backup_<stamp>.files.tar.gz` (the uploaded signatures). The newest verified
dump is never deleted by retention.

**Until `BACKUP_PATH` points at another machine, a disk failure loses the data
and the backups together.** Mounting a network share and setting that one
value is the single most important thing left to do on this box.

## Restore drill

Do this once before go-live and once a quarter, on a copy of the server:

1. `php artisan migrate:fresh --force`
2. Maintenance → Server backups → Restore on the newest `.sql`
3. `tar -xzf backup_<stamp>.files.tar.gz -C storage/app/public`
4. Sign in as a manager, open a time sheet, check a signature appears on the printed sheet.

Write down how long it took. That number is the recovery time.
