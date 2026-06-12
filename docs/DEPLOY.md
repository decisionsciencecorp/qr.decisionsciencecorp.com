# Deploy — qr.decisionsciencecorp.com

## Multihost wiring (requested from Ada)

This site rides the standard multihost git-sync pipeline (same shape as
`decisionsciencecorp.com` and the other DSC vhosts).

### `/root/sites/qr.decisionsciencecorp.com.env` (multihost)

```bash
DOMAIN=qr.decisionsciencecorp.com
REPO=decisionsciencecorp/qr.decisionsciencecorp.com
BRANCH=main
WEB_ROOT=/var/www/qr.decisionsciencecorp.com/html
DB_PARENT=/var/www/qr.decisionsciencecorp.com
SRC_DIR=/root/repos/qr.decisionsciencecorp.com
WWW_USER=www-data
GITHUB_TOKEN=<set on host, never committed>
```

`DB_PARENT` is harmless even though there's no DB — keeps the env shape uniform
with sibling sites. Repo is **private**, so the token in `.env` matters.

### Cron pair (root crontab on multihost)

Same stagger as other sites — `sync.sh` every 2 minutes, `deploy.sh` on odd
minutes:

```cron
*/2 * * * * /root/sync.sh qr.decisionsciencecorp.com >> /var/log/sync-qr.decisionsciencecorp.com.log 2>&1   # AGENT_CRON qr.decisionsciencecorp.com sync
1-59/2 * * * * /root/deploy.sh qr.decisionsciencecorp.com >> /var/log/deploy-qr.decisionsciencecorp.com.log 2>&1  # AGENT_CRON qr.decisionsciencecorp.com deploy
```

### DNS

`qr.decisionsciencecorp.com` → A record at `64.95.10.156` (multihost).

### nginx vhost

Static `public/` docroot. No PHP, no app server. Standard TLS via certbot.

```nginx
server {
    listen 443 ssl http2;
    server_name qr.decisionsciencecorp.com;
    root /var/www/qr.decisionsciencecorp.com/html;
    index index.html;

    # SPA-style fallback not required — single index.html is fine.
    location / { try_files $uri $uri/ =404; }

    # Long-cache static assets; cache-bust by editing filenames if needed.
    location ~* \.(woff2?|svg|png|jpg|jpeg|gif|ico)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }

    # Standard certbot block follows.
}
```

### Definition of done (per `ada-delegation.mdc`)

1. DNS A record live.
2. `/root/sites/qr.decisionsciencecorp.com.env` exists with the values above.
3. First `/root/sync.sh qr.decisionsciencecorp.com` runs green.
4. Cron pair installed with `# AGENT_CRON …` tags.
5. `https://qr.decisionsciencecorp.com/` returns 200 with the app.
