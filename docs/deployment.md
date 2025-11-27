# Deployment Guide

This guide explains how to deploy Geoloc-Map in production using the pre-built Docker image from DockerHub.

## Architecture Overview

```
GitHub Repository (main branch)
        ↓
    GitHub Actions (CI/CD)
        ↓
    Build & Push to DockerHub
        ↓
    nicolascodemate/geoloc-map:latest
        ↓
    Production Server (docker compose pull)
```

**Key Points:**
- ✅ Images are built automatically by GitHub Actions
- ✅ Production servers pull pre-built images (no build required)
- ✅ Faster deployments, guaranteed tested images
- ✅ Consistent across all environments

---

## Prerequisites

### On Production Server

- Docker Engine 20.10+ installed
- Docker Compose v2+ installed
- Domain name pointing to your server
- Ports 80 and 443 open
- Git installed (to clone compose files)

### Check Prerequisites

```bash
# Check Docker
docker --version

# Check Docker Compose
docker compose version

# Check ports are open
sudo netstat -tulpn | grep -E ':80|:443'
```

---

## Initial Deployment

### Step 1: Prepare the Server

```bash
# Create deployment directory
mkdir -p /opt/geoloc-map
cd /opt/geoloc-map

# Clone repository (for compose files and configs)
git clone https://github.com/nicolascodemate/geoloc-map.git .

# Or download only necessary files
curl -O https://raw.githubusercontent.com/nicolascodemate/geoloc-map/main/compose.yaml
curl -O https://raw.githubusercontent.com/nicolascodemate/geoloc-map/main/compose.prod.yaml
curl -O https://raw.githubusercontent.com/nicolascodemate/geoloc-map/main/geoloc.example.json
```

### Step 2: Create External Volumes

**Critical for Let's Encrypt certificate persistence:**

```bash
docker volume create caddy_data
docker volume create caddy_config
```

Verify volumes:
```bash
docker volume ls | grep caddy
```

### Step 3: Configure Environment

Create your production environment file:

```bash
nano .env.prod.local
```

**Minimal configuration:**
```bash
# Server configuration
SERVER_NAME=https://your-domain.example.com
APP_SECRET=$(openssl rand -hex 32)

# Geolocation configuration (path to external JSON file)
GEOLOC_OBJECTS=/app/geoloc.json
```

### Step 4: Configure Geolocation Objects

Create your geolocation configuration:

```bash
cp geoloc.example.json geoloc.json
nano geoloc.json
```

**Example configuration:**
```json
[
  {
    "mapName": "fleet",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 12,
    "refresh_interval": 10000,
    "objects": [
      {
        "name": "Vehicle 1",
        "url": "https://your-gps-api.com/location",
        "query_params": {
          "vehicle_id": "001",
          "api_key": "your-api-key"
        },
        "latitude_json_path": "location.lat",
        "longitude_json_path": "location.lng"
      }
    ]
  }
]
```

See [Configuration Guide](configuration.md) for detailed examples.

### Step 5: Deploy

```bash
# Pull the latest production image
docker compose -f compose.yaml -f compose.prod.yaml pull

# Start the application
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Step 6: Verify Deployment

```bash
# Check container status
docker compose -f compose.yaml -f compose.prod.yaml ps

# Check logs
docker compose -f compose.yaml -f compose.prod.yaml logs -f php

# Test access
curl https://your-domain.example.com/fleet
```

---

## Updating to Latest Version

When a new version is pushed to DockerHub:

### Simple Update

```bash
cd /opt/geoloc-map

# Pull latest image and restart
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Update with Configuration Changes

```bash
cd /opt/geoloc-map

# Stop containers
docker compose -f compose.yaml -f compose.prod.yaml down

# Update compose files (if needed)
git pull

# Update configuration
nano geoloc.json

# Pull and restart
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

---

## Configuration Management

### Modifying Geolocation Configuration

The `geoloc.json` file can be modified without rebuilding the image:

```bash
# Edit configuration
nano geoloc.json

# Clear Symfony cache (without restart)
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear

# Or restart containers
docker compose -f compose.yaml -f compose.prod.yaml restart
```

Changes are visible immediately after cache clear!

### Environment Variables

If you need to change environment variables in `.env.prod.local`:

```bash
# Edit environment
nano .env.prod.local

# Restart required for env vars
docker compose -f compose.yaml -f compose.prod.yaml restart
```

---

## Monitoring

### View Logs

```bash
# Follow logs in real-time
docker compose -f compose.yaml -f compose.prod.yaml logs -f php

# View last 100 lines
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=100 php

# Check for errors
docker compose -f compose.yaml -f compose.prod.yaml logs php | grep -i error
```

### Check Certificate Status

```bash
# View certificates
docker run --rm -v caddy_data:/data alpine ls -la /data/caddy/certificates/

# Check expiration
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  find /data/caddy/certificates -name "*.crt" -exec openssl x509 -noout -dates -in {} \;
```

### Health Checks

```bash
# Container status
docker compose -f compose.yaml -f compose.prod.yaml ps

# Resource usage
docker stats $(docker compose -f compose.yaml -f compose.prod.yaml ps -q)

# Test endpoints
curl -I https://your-domain.example.com/mapName
```

---

## Backup and Restore

### Backup

```bash
# Backup configuration
tar czf geoloc-map-backup-$(date +%Y%m%d).tar.gz \
  .env.prod.local \
  geoloc.json \
  compose.yaml \
  compose.prod.yaml

# Backup Let's Encrypt certificates
docker run --rm \
  -v caddy_data:/source \
  -v $(pwd):/backup \
  alpine tar czf /backup/caddy-certs-$(date +%Y%m%d).tar.gz -C /source .
```

### Restore

```bash
# Restore configuration
tar xzf geoloc-map-backup-YYYYMMDD.tar.gz

# Restore certificates (if needed)
docker run --rm \
  -v caddy_data:/target \
  -v $(pwd):/backup \
  alpine tar xzf /backup/caddy-certs-YYYYMMDD.tar.gz -C /target
```

---

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose -f compose.yaml -f compose.prod.yaml logs php

# Verify volumes exist
docker volume ls | grep caddy

# Check .env.prod.local syntax
cat .env.prod.local

# Validate geoloc.json
cat geoloc.json | jq '.'
```

### Certificate Issues

```bash
# Check certificate storage
docker run --rm -v caddy_data:/data alpine ls -la /data/caddy/certificates/

# Force certificate renewal (only if needed)
docker compose -f compose.yaml -f compose.prod.yaml down
docker run --rm -v caddy_data:/data alpine rm -rf /data/caddy/certificates/
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

See [Let's Encrypt Documentation](letsencrypt.md) for detailed certificate management.

### Configuration Not Applied

```bash
# Clear Symfony cache
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear

# Verify mounted file
docker compose -f compose.yaml -f compose.prod.yaml exec php cat /app/geoloc.json

# Check file permissions
ls -la geoloc.json
```

### Port Already in Use

```bash
# Check what's using the ports
sudo netstat -tulpn | grep -E ':80|:443'

# Stop conflicting services
sudo systemctl stop apache2  # or nginx, etc.

# Or change ports in compose.yaml (dev only)
```

---

## Best Practices

### Security

1. **Protect sensitive files:**
   ```bash
   chmod 600 .env.prod.local
   chmod 600 geoloc.json  # May contain API keys
   ```

2. **Use strong secrets:**
   ```bash
   # Generate secure APP_SECRET
   openssl rand -hex 32
   ```

3. **Regular updates:**
   ```bash
   # Schedule weekly updates
   0 3 * * 0 cd /opt/geoloc-map && docker compose -f compose.yaml -f compose.prod.yaml pull && docker compose -f compose.yaml -f compose.prod.yaml up -d
   ```

### Performance

1. **Monitor resource usage:**
   ```bash
   docker stats $(docker compose -f compose.yaml -f compose.prod.yaml ps -q)
   ```

2. **Optimize refresh intervals:**
   - Don't set `refresh_interval` too low (< 5000ms)
   - Balance between real-time data and API rate limits

3. **Clean up unused images:**
   ```bash
   docker image prune -a
   ```

### Reliability

1. **Enable automatic restart:**
   Already configured with `restart: unless-stopped`

2. **Monitor disk space:**
   ```bash
   df -h
   docker system df
   ```

3. **Regular backups:**
   - Schedule daily configuration backups
   - Weekly certificate backups
   - Test restore procedures

---

## Production Deployment Checklist

- [ ] Domain DNS configured and propagated
- [ ] Ports 80 and 443 open and accessible
- [ ] Docker and Docker Compose installed
- [ ] External volumes created (`caddy_data`, `caddy_config`)
- [ ] `.env.prod.local` created with `SERVER_NAME` and `APP_SECRET`
- [ ] `geoloc.json` configured with your maps
- [ ] Sensitive files have restricted permissions (600)
- [ ] Image pulled from DockerHub
- [ ] Containers started successfully
- [ ] Certificates obtained from Let's Encrypt
- [ ] Application accessible via HTTPS
- [ ] Maps displaying correctly
- [ ] Monitoring configured
- [ ] Backup strategy in place
- [ ] Update procedure documented for your team

---

## Advanced: Building Locally

If you need to build the production image locally (rarely needed):

```bash
# Uncomment build section in compose.prod.yaml
nano compose.prod.yaml

# Build
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache

# Deploy
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

**Note:** This is not recommended as the CI/CD pipeline builds and tests images before publishing to DockerHub.

---

## CI/CD Pipeline

The project uses GitHub Actions to automatically:

1. **On push to `main`:**
   - Build production Docker image
   - Tag as `nicolascodemate/geoloc-map:latest`
   - Push to DockerHub
   - Multi-platform support (amd64, arm64)

2. **On tag push (v*):**
   - Build and tag with version (e.g., `v1.2.3`)
   - Create semantic version tags (e.g., `1`, `1.2`, `1.2.3`)
   - Push all tags to DockerHub

See `.github/workflows/dockerhub.yml` for pipeline details.

---

## Support

- **Documentation:** [README.md](../README.md)
- **Configuration:** [configuration.md](configuration.md)
- **Certificates:** [letsencrypt.md](letsencrypt.md)
- **Issues:** [GitHub Issues](https://github.com/nicolascodemate/geoloc-map/issues)

---

## Quick Reference

### Common Commands

```bash
# Deploy/Update
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Stop
docker compose -f compose.yaml -f compose.prod.yaml down

# Logs
docker compose -f compose.yaml -f compose.prod.yaml logs -f php

# Clear cache
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear

# Restart
docker compose -f compose.yaml -f compose.prod.yaml restart

# Shell access
docker compose -f compose.yaml -f compose.prod.yaml exec php sh
```

### File Locations

- Configuration: `/opt/geoloc-map/geoloc.json`
- Environment: `/opt/geoloc-map/.env.prod.local`
- Certificates: Docker volume `caddy_data`
- Logs: `docker compose logs`
