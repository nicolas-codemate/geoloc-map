# Configuration Guide

This guide explains how to configure geolocation objects for your maps using either environment variables or external JSON files.

## Configuration Methods

 **Environment Variable** (GEOLOC_OBJECTS) - Simple, inline JSON or path to json file

## Option 1: Environment Variable (Inline JSON)

### Usage

Set the `GEOLOC_OBJECTS` environment variable with inline JSON:

```bash
# In .env.prod.local
GEOLOC_OBJECTS='[{"mapName":"my_car","default_latitude":48.8575,"default_longitude":2.3514,"default_zoom_level":12,"refresh_interval":5000,"objects":[{"name":"My Car","enable_sandbox":true}]}]'
```

### Pros & Cons

✅ **Pros:**
- Simple for small configurations
- No additional files needed
- Works well for environment-based deployments

❌ **Cons:**
- Hard to read and maintain for complex configurations
- Difficult to validate JSON syntax
- No IDE syntax highlighting
- Multiline formatting is tricky

## Option 2: External JSON File ⭐ Recommended

### Setup

1. **Copy the example file:**
   ```bash
   cp geoloc.example.json geoloc.json
   ```

2. **Edit your configuration:**
   ```bash
   nano geoloc.json
   # Or use your preferred editor
   ```

3. **Enable the volume mount** in `compose.prod.yaml` (uncomment the geoloc.json line)

### File Structure

```json
[
  {
    "mapName": "my_car",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 12,
    "refresh_interval": 10000,
    "time_ranges": [
      {
        "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "startTime": "08:00",
        "endTime": "18:00"
      }
    ],
    "objects": [
      {
        "name": "My Vehicle",
        "url": "https://your-gps-api.example.com/location",
        "query_params": {
          "device_id": "your_device_id",
          "api_key": "your_api_key"
        },
        "latitude_json_path": "data.latitude",
        "longitude_json_path": "data.longitude"
      }
    ]
  }
]
```

### Pros & Cons

✅ **Pros:**
- **Easy to read and edit** - Proper JSON formatting with indentation
- **IDE support** - Syntax highlighting and validation
- **Better for complex configs** - Multiple maps, time ranges, objects
- **Easier debugging** - Clear structure and comments possible
- **Runtime modification** - Edit without rebuilding image

❌ **Cons:**
- Requires file on the host system
- Needs cache clear to see changes

## Configuration Parameters

### Map Configuration

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `mapName` | string | ✅ | Unique identifier for the map (used in URL) |
| `default_latitude` | float | ✅ | Default map center latitude |
| `default_longitude` | float | ✅ | Default map center longitude |
| `default_zoom_level` | integer | ✅ | Default zoom level (1-20) |
| `refresh_interval` | integer | ✅ | Refresh interval in milliseconds |
| `custom_message` | string | ❌ | Message displayed when no data is available (default: "Aucune donnée de géolocalisation") |
| `time_ranges` | array | ❌ | Visibility time windows (see below) |
| `objects` | array | ✅ | Array of geolocatable objects |

### Object Configuration

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Display name for the object |
| `url` | string | ⚠️ | API endpoint URL (not needed for sandbox) |
| `query_params` | object | ❌ | Query parameters for the API request |
| `latitude_json_path` | string | ⚠️ | JSON path to latitude in API response |
| `longitude_json_path` | string | ⚠️ | JSON path to longitude in API response |
| `enable_sandbox` | boolean | ❌ | Use fake random coordinates for testing |

⚠️ Required unless `enable_sandbox` is `true`

### Time Range Configuration

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `days` | array | ❌ | Days when object is visible (Monday-Sunday). If omitted, applies to all days. |
| `startTime` | string | ✅ | Start time (HH:MM format, 24h) |
| `endTime` | string | ✅ | End time (HH:MM format, 24h) |

**Note:** Outside time ranges, the map shows default coordinates instead of object positions.

## Modifying Configuration at Runtime

### For External JSON File Users

After editing `geoloc.json`, you have **two options** to apply changes:

#### Option 1: Clear Cache (Faster)

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear
```

The application continues running, changes are visible immediately after cache clear.

#### Option 2: Restart Container

```bash
docker compose -f compose.yaml -f compose.prod.yaml restart
```

More disruptive but ensures a clean state.

### For Environment Variable Users

If using `GEOLOC_OBJECTS` environment variable:

1. Edit `.env.prod.local`
2. Restart the container (environment variables are read at startup):
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml restart
   ```

## Development vs Production

### Development (compose.yaml)

```yaml
environment:
  GEOLOC_OBJECTS: ${GEOLOC_OBJECTS:-[]}
```

No bind mount by default. You can:
- Use `GEOLOC_OBJECTS` env var with inline JSON
- Or set `GEOLOC_OBJECTS=/app/geoloc.json` and add a bind mount in `compose.override.yaml`

### Production (compose.prod.yaml)

To use an external JSON file, uncomment the volume mount in `compose.prod.yaml`:

```yaml
volumes:
  - ./frankenphp/certs:/etc/caddy/certs:ro
  # Uncomment the line below to use external JSON config file
  - ./geoloc.json:/app/geoloc.json:ro
```

Then set in `.env.prod.local`:

```bash
GEOLOC_OBJECTS=/app/geoloc.json
```

## Examples

### Example 1: Sandbox Mode (Testing)

Perfect for testing without real API:

```json
[
  {
    "mapName": "demo",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 12,
    "refresh_interval": 5000,
    "objects": [
      {
        "name": "Test Vehicle 1",
        "enable_sandbox": true
      },
      {
        "name": "Test Vehicle 2",
        "enable_sandbox": true
      }
    ]
  }
]
```

Access at: `https://your-domain.com/demo`

### Example 2: Real GPS Tracker

```json
[
  {
    "mapName": "fleet",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 10,
    "refresh_interval": 30000,
    "objects": [
      {
        "name": "Vehicle 001",
        "url": "https://gps-api.example.com/position",
        "query_params": {
          "vehicle_id": "001",
          "api_key": "your-secret-key"
        },
        "latitude_json_path": "location.lat",
        "longitude_json_path": "location.lon"
      }
    ]
  }
]
```

### Example 3: Time-Restricted Tracking

Only show vehicle position during business hours:

```json
[
  {
    "mapName": "company_car",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 12,
    "refresh_interval": 10000,
    "custom_message": "Véhicule non disponible en dehors des heures de service",
    "time_ranges": [
      {
        "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "startTime": "08:00",
        "endTime": "18:00"
      }
    ],
    "objects": [
      {
        "name": "Company Vehicle",
        "url": "https://tracker.example.com/api/location",
        "query_params": {
          "device": "company_car_01"
        },
        "latitude_json_path": "position.latitude",
        "longitude_json_path": "position.longitude"
      }
    ]
  }
]
```

Outside Monday-Friday 8:00-18:00, the map shows default coordinates (48.8575, 2.3514) with the custom message.

### Example 4: Multiple Maps

```json
[
  {
    "mapName": "fleet",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 10,
    "refresh_interval": 30000,
    "objects": [
      {
        "name": "Truck 1",
        "enable_sandbox": true
      },
      {
        "name": "Truck 2",
        "enable_sandbox": true
      }
    ]
  },
  {
    "mapName": "personal",
    "default_latitude": 45.764043,
    "default_longitude": 4.835659,
    "default_zoom_level": 15,
    "refresh_interval": 60000,
    "objects": [
      {
        "name": "My Car",
        "url": "https://my-gps.example.com/location",
        "query_params": {
          "id": "my_car"
        },
        "latitude_json_path": "lat",
        "longitude_json_path": "lng"
      }
    ]
  }
]
```

Access at:
- `https://your-domain.com/fleet`
- `https://your-domain.com/personal`

## Troubleshooting

### Changes Not Visible

**Problem:** Modified `geoloc.json` but no changes appear

**Solutions:**
1. Clear Symfony cache: `docker compose exec php bin/console cache:clear`
2. Check file is mounted: `docker compose exec php cat /app/geoloc.json`
3. Restart container: `docker compose restart`

### Invalid JSON Error

**Problem:** Application shows error about invalid JSON

**Solutions:**
1. Validate your JSON: https://jsonlint.com/
2. Check for:
   - Missing commas
   - Trailing commas (not allowed in JSON)
   - Unescaped quotes in strings
   - Incorrect brackets/braces matching

### Map Not Found (404)

**Problem:** Accessing `https://domain.com/mapName` returns 404

**Solutions:**
1. Check `mapName` matches exactly (case-sensitive)
2. Verify configuration is loaded: check logs or clear cache
3. Ensure JSON file is properly mounted

### File Not Mounted

**Problem:** `docker compose exec php cat /app/geoloc.json` shows error

**Solutions:**
1. Check `geoloc.json` exists in project root
2. Verify volume mount in `compose.prod.yaml`
3. Check file permissions (should be readable)

## Security Considerations

### Protecting API Keys

The `geoloc.json` file may contain sensitive information (API keys, tokens):

1. **Never commit** `geoloc.json` to version control (already in `.gitignore`)
2. **Use file permissions** on the host:
   ```bash
   chmod 600 geoloc.json
   ```
3. **Consider environment variables** for secrets:
   - Store API keys in `.env.prod.local`
   - Reference them in your API requests if your API supports it
4. **Read-only mount** - The file is mounted as `:ro` (read-only) in the container

### Backup Configuration

Always backup your `geoloc.json`:

```bash
# Create backup
cp geoloc.json geoloc.json.backup

# Or with timestamp
cp geoloc.json "geoloc.json.backup.$(date +%Y%m%d_%H%M%S)"
```

## Migration from Environment Variable

If you're currently using `GEOLOC_OBJECTS` environment variable and want to migrate:

1. **Extract current config:**
   ```bash
   # From .env.prod.local, copy the value of GEOLOC_OBJECTS
   echo $GEOLOC_OBJECTS | jq '.' > geoloc.json
   ```

2. **Validate the file:**
   ```bash
   cat geoloc.json | jq '.'
   ```

3. **Update deployment:**
   - Keep `GEOLOC_OBJECTS` in `.env.prod.local` as backup
   - The application will use `geoloc.json` (higher priority)

4. **Test:**
   ```bash
   docker compose -f compose.yaml -f compose.prod.yaml up -d
   docker compose -f compose.yaml -f compose.prod.yaml exec php bin/console cache:clear
   ```

5. **Once confirmed working, optionally remove GEOLOC_OBJECTS from .env.prod.local**

## Best Practices

1. **Start with example file:**
   - Always copy `geoloc.example.json` as a base
   - Don't edit the example file directly

2. **Use sandbox mode for testing:**
   - Test map configuration with `enable_sandbox: true` first
   - Switch to real API once layout is confirmed

3. **Set appropriate refresh intervals:**
   - Too short: Excessive API calls, potential rate limiting
   - Too long: Stale position data
   - Recommended: 10000-60000ms (10-60 seconds) for real-time tracking

4. **Validate JSON before deployment:**
   - Use an online validator or `jq`
   - IDEs like VS Code have built-in JSON validation

5. **Document your configuration:**
   - Add comments in a separate README
   - JSON doesn't support comments, so document externally

6. **Version control strategy:**
   - Commit `geoloc.example.json` with sanitized examples
   - Never commit `geoloc.json` (contains secrets)
   - Document configuration in README or wiki

## Additional Resources

- [JSON Path Documentation](https://goessner.net/articles/JsonPath/) - For understanding `latitude_json_path`
- [Example Configuration](../geoloc.example.json) - Template file
- [Project README](../README.md) - General project documentation
