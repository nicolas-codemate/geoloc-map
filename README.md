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

### Deployment in production using docker
```bash
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
SERVER_NAME=your-domain-name.example.com \
docker compose -f compose.yaml -f compose.prod.yaml up --wait
```

or

```
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
# run the container by using port 8080 (for cloudrun)
docker run -e SERVER_NAME=:8080 -e HTTP_PORT=8080 -p 8080:8080 geoloc-map:latest                                                                              
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

