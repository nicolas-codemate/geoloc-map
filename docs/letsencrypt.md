# Let's Encrypt Certificate Management

## Overview

FrankenPHP with Caddy automatically generates and renews Let's Encrypt certificates for your domain. These certificates are stored in Docker volumes to persist across container restarts and updates.

## The Problem

By default, Docker Compose volumes are deleted when running `docker compose down -v`. This causes a new Let's Encrypt certificate to be requested on each deployment, which can quickly hit Let's Encrypt's rate limits:
- **5 certificates per domain per week** (main limit)
- 50 certificates per registered domain per week

After 5 container recreations in a week, you'll be blocked from obtaining new certificates.

## The Solution: External Volumes

In production (`compose.prod.yaml`), the project uses **external volumes** that persist independently of the Docker Compose stack. These volumes are never deleted, even with `docker compose down -v`.

### Initial Setup

**Before your first production deployment**, create the external volumes:

```bash
docker volume create caddy_data
docker volume create caddy_config
```

These commands need to be run **only once** on your production server.

### Deploying with Persistent Certificates

Once the volumes are created, deploy normally:

```bash
# Build fresh production image
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache

# Start container (certificates will be requested on first run only)
SERVER_NAME=your-domain-name.example.com \
APP_SECRET=your-secret \
docker compose -f compose.yaml -f compose.prod.yaml up --wait
```

On the first deployment, Caddy will request a Let's Encrypt certificate and store it in the `caddy_data` volume.

On subsequent deployments, Caddy will:
1. Find the existing certificate in the volume
2. Use it if still valid
3. Automatically renew it when it's close to expiration (typically 30 days before)

### Safe Redeployment

You can now safely redeploy without requesting new certificates:

```bash
# Stop and remove containers (volumes are preserved)
docker compose -f compose.yaml -f compose.prod.yaml down

# Update your code
git pull

# Rebuild and restart (existing certificates will be reused)
docker compose -f compose.yaml -f compose.prod.yaml build --pull
docker compose -f compose.yaml -f compose.prod.yaml up --wait
```

**Important**: Never use `docker compose down -v` in production, as this would attempt to remove the volumes (though external volumes are protected).

## Verifying Certificate Persistence

Check that your certificates are stored in the external volume:

```bash
# List contents of caddy_data volume
docker run --rm -v caddy_data:/data alpine ls -la /data/caddy/certificates/

# Check certificate expiration
docker exec $(docker compose ps -q php) cat /data/caddy/certificates/acme-v02.api.letsencrypt.org-directory/*/your-domain.crt | openssl x509 -noout -dates
```

## Migrating Existing Deployments

If you already have a deployment with non-external volumes:

1. **Export existing certificates** (if any):
   ```bash
   docker run --rm -v geoloc-map_caddy_data:/source -v $(pwd)/backup:/backup alpine tar czf /backup/caddy_data.tar.gz -C /source .
   ```

2. **Create external volumes**:
   ```bash
   docker volume create caddy_data
   docker volume create caddy_config
   ```

3. **Import certificates to new volumes** (optional, only if you have valid certificates):
   ```bash
   docker run --rm -v caddy_data:/target -v $(pwd)/backup:/backup alpine tar xzf /backup/caddy_data.tar.gz -C /target
   ```

4. **Remove old stack** (this will remove old volumes):
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml down -v
   ```

5. **Deploy with new configuration**:
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml up --wait
   ```

## Backup and Restore

### Backup Certificates

It's good practice to backup your certificates periodically:

```bash
docker run --rm -v caddy_data:/source -v $(pwd)/backup:/backup alpine tar czf /backup/caddy_data_$(date +%Y%m%d).tar.gz -C /source .
```

### Restore Certificates

If you need to restore certificates:

```bash
docker run --rm -v caddy_data:/target -v $(pwd)/backup:/backup alpine sh -c "rm -rf /target/* && tar xzf /backup/caddy_data_YYYYMMDD.tar.gz -C /target"
```

## Troubleshooting

### Rate Limit Exceeded

If you've hit the Let's Encrypt rate limit:

1. **Wait**: Rate limits reset after one week
2. **Use staging**: Test with Let's Encrypt staging environment:
   ```bash
   CADDY_GLOBAL_OPTIONS="debug\nacme_ca https://acme-staging-v02.api.letsencrypt.org/directory" \
   docker compose -f compose.yaml -f compose.prod.yaml up
   ```
3. **Check volumes**: Ensure external volumes exist and contain certificates:
   ```bash
   docker volume ls | grep caddy
   docker volume inspect caddy_data
   ```

### Certificate Not Persisting

If certificates are still being requested on each restart:

1. **Verify external volumes are configured**:
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml config | grep -A 3 "volumes:"
   ```
   You should see `external: true` for caddy volumes.

2. **Check volume mounts**:
   ```bash
   docker inspect $(docker compose ps -q php) | grep -A 10 Mounts
   ```
   Verify `/data` and `/config` are mounted to external volumes.

3. **Check Caddy logs**:
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml logs php | grep -i cert
   ```

## Automatic Renewal

Caddy automatically handles certificate renewal:
- Certificates are renewed ~30 days before expiration
- No manual intervention required
- Renewal happens in the background while the server is running
- The new certificate is loaded without downtime

## Development vs Production

- **Development** (`compose.yaml`): Uses regular Docker volumes, certificates can be recreated freely
- **Production** (`compose.prod.yaml`): Uses external volumes, certificates persist indefinitely

This ensures you don't hit rate limits during development while maintaining certificate persistence in production.

## References

- [Let's Encrypt Rate Limits](https://letsencrypt.org/docs/rate-limits/)
- [Caddy Automatic HTTPS](https://caddyserver.com/docs/automatic-https)
- [Docker Volumes](https://docs.docker.com/storage/volumes/)
