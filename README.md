# geoloc-map

This project provides a simple and flexible way to geolocate multiple objects based on environment variable. Each object is associated with a specific source from which its geolocation data is retrieved.

## Features

- **Configurable Maps**: Define multiple maps with specific parameters (default latitude/longitude, zoom, etc.).
- **Interactive Objects**: Each object has its own interactive map displaying its geolocation data. Data is fetched from a specified URL.
- **Easy Integration**: The maps can be seamlessly embedded into other websites as iframes. You can also define the height of the iframe by passing `height` as a query parameter.
- **Sandbox mode**: Mock some random coordinate to test the module

## Use Case

This project is ideal for scenarios where you need to display geolocation data for multiple objects on individual maps, with the ability to integrate these maps into external web pages.

## How It Works

Configure the objects and their respective data sources using `GEOLOC_OBJECTS` in JSON format.

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
                "days": [
                    "Monday",
                    "Tuesday",
                    "Wednesday",
                    "Thursday",
                    "Friday"
                ],
                "start": "08:00",
                "end": "12:00"
            },
            {
                "days": [
                    "Monday",
                    "Tuesday",
                    "Wednesday",
                    "Thursday",
                    "Friday"
                ],
                "start": "14:00",
                "end": "18:00"
            },
            {
                "name": [
                    "Saturday"
                ],
                "start": "09:00",
                "end": "12:00"
            }
        ],
        "objects": [
            {
                "name": "Mon super véhicule",
                "url": "https://my_jeedom_domain.com/core/api/jeeApi.php",
                "query_params": {
                    "apikey": "**JEEDOM_API_KEY**",
                    "method": "get",
                    "plugin": "jMQTT",
                    "type": "cmd",
                    "id": "[779,780]"
                },
                "latitude_json_path": "779",
                "longitude_json_path": "780"
            }
        ]
    }
]
```

## Production Deployment

### Option A: Using Pre-built DockerHub Image (Recommended)

The easiest way to deploy in production is using our pre-built Docker image.

**⚠️ First, create persistent volumes for Let's Encrypt certificates:**
```bash
docker volume create caddy_data
docker volume create caddy_config
```

Then deploy the container:

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

**Required Environment Variables:**
- `SERVER_NAME`: Your domain name or `:80` for HTTP-only
- `APP_SECRET`: Generate with `openssl rand -hex 32` 
- `GEOLOC_OBJECTS`: JSON configuration (see examples below)

**Optional:**
- `CADDY_SERVER_EXTRA_DIRECTIVES`: For custom SSL certificates

**Configuration Examples:**
- `GEOLOC_OBJECTS.example.json`: Configuration examples with multiple scenarios

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

### Portainer Quick Setup

**⚠️ First, create volumes for certificate persistence:**
Go to **Volumes** → **Add volume**:
- Name: `caddy_data` → Create
- Name: `caddy_config` → Create

Then create the container:

1. Go to **Containers** → **Add container**
2. **Image**: `nicolascodemate/geoloc-map:latest`
3. **Network ports**:
   - `80:80/tcp`
   - `443:443/tcp`
4. **Environment variables**:
   ```
   SERVER_NAME=your-domain.example.com
   APP_SECRET=your-generated-secret
   GEOLOC_OBJECTS=your-json-config
   ```
5. **Volumes** (required for certificate persistence):
   - `caddy_data:/data`
   - `caddy_config:/config`

   Optional (if using custom certificates):
   - `/host/path/to/certs:/etc/caddy/certs:ro`
6. **Deploy the container**

### Option B: Build from Source

For customization or development.

**⚠️ First, create persistent volumes:**
```bash
docker volume create caddy_data
docker volume create caddy_config
```

Then build and deploy:

```bash
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
SERVER_NAME=your-domain-name.example.com \
docker compose -f compose.yaml -f compose.prod.yaml up --wait
```

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
* Optionnal `time_ranges`: An array of time ranges to specify when objects should be displayed.
    * If omitted, objects will always be visible.
    * Outside the specified time ranges, the map will display the default latitude and longitude with a generic error message indicating no geolocatable objects.
    * Each time range includes:
        * `days`: An array of days of the week (e.g., ["Monday", "Tuesday"]).
        * `start`: The start time of the range (e.g., "08:00").
        * `end`: The end time of the range (e.g., "20:00").
* `objects`: An array of objects to display on the map:
    * `name`: The name of the object.
    * `url`: The URL to retrieve the geolocation data for the object.
    * `query_params`: The query parameters to include in the request.
    * `latitude_json_path`: The JSON path to extract the latitude from the response.
    * `longitude_json_path`: The JSON path to extract the longitude from the response.
    * `enable_sandbox`: set to true and omit all other param except name to enable a sandbox mode. It will generate some random coordinate.

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
