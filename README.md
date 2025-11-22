# geoloc-map

This project provides a simple and flexible way to geolocate multiple objects based on environment variable. Each object is associated with a specific source from which its geolocation data is retrieved.

## Features

- **Configurable Maps**: Define multiple maps with specific parameters (default latitude/longitude, zoom, etc.).
- **Interactive Objects**: Each object has its own interactive map displaying its geolocation data. Data is fetched from a specified URL.
- **Easy Integration**: The maps can be seamlessly embedded into other websites as iframes. You can also define the height of the iframe by passing `height` as a query parameter.
- **Sandbox mode**: Mock some random coordinate to test the module

## Use Case

This project is ideal for scenarios where you need to display geolocation data for multiple objects on individual maps, with the ability to integrate these maps into external web pages.

## Configuration

Geoloc-Map uses JSON configuration to define maps and their geolocatable objects. You can configure using either:

1. **External JSON file** (recommended) - `geoloc.json`
2. **Environment variable** - `GEOLOC_OBJECTS`

**Quick example:**
```json
[
  {
    "mapName": "my_car",
    "default_latitude": 48.8575,
    "default_longitude": 2.3514,
    "default_zoom_level": 12,
    "refresh_interval": 5000,
    "time_ranges": [
      {
        "days": ["bastille_day"],
        "startTime": "10:00",
        "endTime": "14:00"
      },
      {
        "days": ["french_holidays"],
        "startTime": "closed"
      },
      {
        "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "startTime": "08:00",
        "endTime": "18:00"
      }
    ],
    "objects": [
      {
        "name": "My Vehicle",
        "url": "https://gps-api.example.com/location",
        "query_params": {"device_id": "car_001"},
        "latitude_json_path": "location.lat",
        "longitude_json_path": "location.lng"
      }
    ]
  }
]
```

Access your map at: `https://your-domain.com/my_car`

**📖 For complete configuration options, examples, and best practices, see:**
- **[Configuration Guide](docs/configuration.md)** - Detailed documentation
- **[geoloc.example.json](GEOLOC_OBJECTS.example.json)** - Template with examples

## Production Deployment

### Option A: Using Pre-built DockerHub Image (Recommended)

The easiest way to deploy in production is using our pre-built Docker image.

**⚠️ First, create persistent volumes for Let's Encrypt certificates:**
```bash
docker volume create caddy_data
docker volume create caddy_config
```

Then deploy the container:

**With inline JSON configuration:**
```bash
docker run -d \
  --name geoloc-map \
  -p 80:80 \
  -p 443:443 \
  -v caddy_data:/data \
  -v caddy_config:/config \
  -e SERVER_NAME="your-domain.example.com" \
  -e APP_SECRET="$(openssl rand -hex 32)" \
  -e GEOLOC_OBJECTS='[{"mapName":"example","default_latitude":48.8575,"default_longitude":2.3514,"default_zoom_level":12,"refresh_interval":5000,"objects":[{"name":"Test Object","enable_sandbox":true}]}]' \
  nicolascodemate/geoloc-map:latest
```

**With external JSON file (Recommended):**
```bash
# First, create your geoloc.json file, then:
docker run -d \
  --name geoloc-map \
  -p 80:80 \
  -p 443:443 \
  -v caddy_data:/data \
  -v caddy_config:/config \
  -v $(pwd)/geoloc.json:/app/geoloc.json:ro \
  -e SERVER_NAME="your-domain.example.com" \
  -e APP_SECRET="$(openssl rand -hex 32)" \
  -e GEOLOC_OBJECTS="/app/geoloc.json" \
  nicolascodemate/geoloc-map:latest
```

**Required Environment Variables:**
- `SERVER_NAME`: Your domain name or `:80` for HTTP-only
- `APP_SECRET`: Generate with `openssl rand -hex 32`
- `GEOLOC_OBJECTS`: JSON configuration string **OR** path to json file

**Optional:**
- `CADDY_SERVER_EXTRA_DIRECTIVES`: For custom SSL certificates

**Configuration Examples:**
- `geoloc.example.json`: Template file with examples
- [Configuration Guide](docs/configuration.md): Complete documentation

### Docker Desktop Quick Setup

**⚠️ First, create volumes for certificate persistence:**
Go to **Volumes** → Create two volumes: `caddy_data` and `caddy_config`

Then deploy the container:

1. Open Docker Desktop
2. Go to **Images** → Search for `nicolascodemate/geoloc-map`
3. Click **Run** → **Optional settings**
4. **Port mappings**: Add `80:80` and `443:443`
5. **Volumes**:
   - Host path: `caddy_data` → Container path: `/data`
   - Host path: `caddy_config` → Container path: `/config`
6. **Environment variables**:
   ```
   SERVER_NAME=localhost
   APP_SECRET=your-generated-secret-32-chars
   GEOLOC_OBJECTS=your-json-config
   ```
7. **Run** the container
8. Access your maps at: `http://localhost/{mapName}`

### Portainer Deployment

#### Step 1: Create Volumes for Certificate Persistence

Go to **Volumes** → **Add volume**:
- Name: `caddy_data` → Click **Create the volume**
- Name: `caddy_config` → Click **Create the volume**

These volumes will persist Let's Encrypt certificates across container updates.

#### Step 2: Prepare Configuration File

On your server, create the configuration file:

```bash
# SSH to your server
ssh user@your-server

# Create directory
mkdir -p /opt/geoloc-map

# Create configuration file
nano /opt/geoloc-map/geoloc.json
```

Paste your configuration and save. **Example:**
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
        "enable_sandbox": true
      }
    ]
  }
]
```

#### Step 3: Deploy Container in Portainer

1. Go to **Containers** → **Add container**

2. **Name**: `geoloc-map`

3. **Image**: `nicolascodemate/geoloc-map:latest`

4. **Network ports configuration**:
   - Click **publish a new network port**
   - `80:80/tcp` → Add
   - `443:443/tcp` → Add

5. **Advanced container settings** → **Volumes** tab:

   **Volume mapping:**
   - Click **map additional volume**
   - Container: `/data` → Volume: `caddy_data` → **Bind** mode
   - Click **map additional volume**
   - Container: `/config` → Volume: `caddy_config` → **Bind** mode
   - Click **map additional volume**
   - Container: `/app/geoloc.json` → Host: `/opt/geoloc-map/geoloc.json` → **Bind** mode, **Read-only** ✓

6. **Advanced container settings** → **Env** tab:

   Click **add environment variable** for each:
   ```
   Name: SERVER_NAME          Value: https://your-domain.example.com
   Name: APP_SECRET           Value: your-generated-secret-32-chars
   Name: GEOLOC_OBJECTS       Value: /app/geoloc.json
   ```

7. **Advanced container settings** → **Restart policy**:
   - Select: `Unless stopped`

8. Click **Deploy the container**

#### Step 4: Verify Deployment

1. Check logs: **Containers** → `geoloc-map` → **Logs**
2. Look for: `certificate obtained successfully`
3. Access: `https://your-domain.example.com/fleet`

#### Updating Configuration

To modify `geoloc.json` without redeploying:

```bash
# Edit on server
nano /opt/geoloc-map/geoloc.json

# Clear Symfony cache via Portainer:
# Containers → geoloc-map → Console → Connect
# Then run: bin/console cache:clear

# Or restart container in Portainer
```

Changes are visible immediately after cache clear!

### Option B: Using Docker Compose (Recommended for Server Deployments)

Deploy using Docker Compose with the pre-built image from DockerHub.

**⚠️ First, create persistent volumes:**
```bash
docker volume create caddy_data
docker volume create caddy_config
```

**Setup configuration:**
```bash
# Clone or download the compose files
git clone https://github.com/nicolascodemate/geoloc-map.git
cd geoloc-map

# Create your configuration
cp geoloc.example.json geoloc.json
nano geoloc.json  # Edit with your configuration

# Create environment file
nano .env.prod.local
```

**Example `.env.prod.local`:**
```bash
SERVER_NAME=https://your-domain.example.com
APP_SECRET=your-generated-secret-32-chars
GEOLOC_OBJECTS=/app/geoloc.json
```

**Deploy:**
```bash
# Pull the latest image and start
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

**Update to latest version:**
```bash
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

**Note:** The production compose file uses the pre-built image from DockerHub (built automatically by CI/CD). No build step is required on the server.

---

## Development

### Local Development

For local development with live code reloading:

```bash
# Install dependencies
make composer c=install

# Start development environment
make up

# Access at http://localhost:8080
```

The development compose file builds the image locally with the `frankenphp_dev` target.

### Building Locally (Optional)

If you need to build the production image locally instead of using the pre-built one:

```bash
# Uncomment the build section in compose.prod.yaml
# Then build:
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache

# Run with locally built image
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

**Note:** This is rarely needed as the image is automatically built and published by GitHub Actions on every push to `main`.

---

### Custom SSL Certificates

Mount your certificates and configure Caddy:

```bash
docker run -d \
  --name geoloc-map \
  -p 80:80 -p 443:443 \
  -v /path/to/your/certs:/etc/caddy/certs:ro \
  -e SERVER_NAME="your-domain.example.com" \
  -e APP_SECRET="your-secure-secret" \
  -e GEOLOC_OBJECTS='your-json-config' \
  -e CADDY_SERVER_EXTRA_DIRECTIVES="tls /etc/caddy/certs/cert.crt /etc/caddy/certs/key.key" \
  nicolascodemate/geoloc-map:latest
```

### Configuration Details:

* `mapName`: The name of the map, which also serves as the URI of the iframe. For example, my_car will be accessible at https://mydomain.com/my_car.
* `default_latitude`: The default latitude for the map when no object is visible.
* `default_longitude`: The default longitude for the map when no object is visible.
* `default_zoom_level`: The default zoom level for the map when no object is visible.
* `refresh_interval`: The interval (in milliseconds) at which the map will refresh to fetch new geolocation data.
* Optional `time_ranges`: An array of time ranges with priority-based matching (first match wins).
    * If omitted, objects will always be visible (24/7).
    * Outside the specified time ranges, the map will display the default latitude and longitude with a message indicating no geolocatable objects.
    * **Priority System**: Time ranges are evaluated in order. The first rule that matches the current date/time determines visibility. Place more restrictive rules (holidays) before general rules (weekdays).
    * Each time range includes:
        * `days`: An array of day specifiers. Supported formats:
            * **Day of week**: `"Monday"`, `"Tuesday"`, etc.
            * **Fixed date** (MM-DD): `"05-01"` for May 1st (any year)
            * **Full date** (YYYY-MM-DD): `"2025-12-24"` for a specific date
            * **All French holidays**: `"french_holidays"` for all 11 French public holidays at once
            * **Individual French holiday keywords**: `"labor_day"`, `"easter_monday"`, `"bastille_day"`, etc. (see list below)
        * `startTime`: The start time in HH:MM format (e.g., `"08:00"`), or special keywords:
            * `"closed"`: Force closure (objects hidden)
            * `"open"`: Force opening (objects shown 24/7)
        * `endTime`: The end time in HH:MM format (e.g., `"18:00"`). Ignored if `startTime` is `"closed"` or `"open"`.
    * **French Holiday Keywords** (automatically calculated):
        * `new_year` - January 1st
        * `easter_monday` - Monday after Easter (mobile date)
        * `labor_day` - May 1st
        * `victory_day` - May 8th (Victory in Europe Day)
        * `ascension` - 39 days after Easter (mobile date)
        * `whit_monday` - 50 days after Easter (mobile date)
        * `bastille_day` - July 14th
        * `assumption` - August 15th
        * `all_saints` - November 1st
        * `armistice` - November 11th
        * `christmas` - December 25th
    * **Example Configuration**:
        ```json
        "time_ranges": [
            {
                "days": ["bastille_day", "assumption"],
                "startTime": "10:00",
                "endTime": "14:00"
            },
            {
                "days": ["french_holidays"],
                "startTime": "closed"
            },
            {
                "days": ["2025-12-24"],
                "startTime": "08:00",
                "endTime": "12:00"
            },
            {
                "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                "startTime": "08:00",
                "endTime": "18:00"
            },
            {
                "days": ["Saturday", "Sunday"],
                "startTime": "closed"
            }
        ]
        ```
        In this example (evaluated in order, first match wins):
        1. Bastille Day and Assumption: open 10am-2pm (exception to general holiday closure)
        2. All other French holidays: completely closed
        3. December 24, 2025: open 8am-12pm (special Christmas Eve hours)
        4. Weekdays: open 8am-6pm
        5. Weekends: closed
* `objects`: An array of objects to display on the map:
    * `name`: The name of the object.
    * `url`: The URL to retrieve the geolocation data for the object.
    * `query_params`: The query parameters to include in the request.
    * `latitude_json_path`: The JSON path to extract the latitude from the response.
    * `longitude_json_path`: The JSON path to extract the longitude from the response.
    * `enable_sandbox`: set to true and omit all other param except name to enable a sandbox mode. It will generate some random coordinate.

---

## Installation

On Linux, the following CLI commands are based on a recent Ubuntu release. Please adjust them as needed for your specific distribution.

### Prerequisites
- A Linux server (Ubuntu 20.04 or later is recommended)
- A user with sudo privileges
- Docker and Docker Compose installed
- Git installed

Follow the steps below to install the necessary dependencies, or skip to the [Start the Application](#Start-the-Application) section if you already have them installed.

1. Update the package manager

```bash
sudo apt update
```
2. Install GIT

```bash
sudo apt install git
```
3. Clone the repository in wanted directory

```bash
git clone https://github.com/nicolas-codemate/geoloc-map.git
```

4. [Install Docker and Docker Compose](https://docs.docker.com/engine/install/ubuntu/#install-using-the-repository)

```bash
# Add Docker's official GPG key:
sudo apt-get update
sudo apt-get install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
```

Install Docker Engine, CLI, and Containerd:

```bash
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

5. Allow your user to run Docker commands without sudo

```bash
sudo groupadd docker
sudo usermod -aG docker $USER
```

6. Log out and log back in to apply the group changes.

You can verify everything is working by running:

```bash
docker run hello-world
```

### Start the Application

1. Navigate to the cloned repository directory:

```bash
cd geoloc-map
```

2. Build the Docker images:

```bash
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
```

3. Create a `.env.prod.local` file in the root of your project with the following content:
```env
APP_SECRET=AnyRandomStringToSecureYourApp
GEOLOC_OBJECTS='[...]' # Replace with your actual JSON configuration
```

4. Start the application:

```bash
SERVER_NAME=your-domain-name.example.com \
docker compose -f compose.yaml -f compose.prod.yaml up --wait           
```

By default, the application uses Let's Encrypt to automatically generate a TLS certificate for your domain. To disable HTTPS, set `SERVER_NAME` to `:80` instead of your domain name.
If your server is behind a firewall, ensure that ports 80 and 443 are open to allow incoming traffic for successful TLS certificate generation with Let's Encrypt.

**⚠️ Important**: To avoid hitting Let's Encrypt rate limits, create persistent volumes before your first deployment:
```bash
docker volume create caddy_data
docker volume create caddy_config
```
This ensures certificates persist across container restarts. See [docs/letsencrypt.md](docs/letsencrypt.md) for detailed information.

### Use your own TLS certificates

Put your TLS certificates in the `frankenphp/certs`
And run

```bash
SERVER_NAME=your-domain-name.example.com:443 \
CADDY_SERVER_EXTRA_DIRECTIVES="tls /etc/caddy/certs/your_custom_public_certificate.crt /etc/caddy/certs/your_custom_private_certificate.key" \
docker compose -f compose.yaml -f compose.prod.yaml up --wait
```
