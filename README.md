## Send your events to API
Pack your events data to format below, and send API GET request to /track 
```json
[
  {
    "event": "Visit",
    "category": "Product",
    "segments": {
      "is_registered": "true",
      "age": 35,
      "country": "Ukraine"
    },
    "value": 1,
    "timestamp": 1505906051
  },
  {
    "event": "TopAction",
    "category": "Product",
    "value": 3
  },
  ...
]
```

## Quick start
You just need PHP8+ and Composer.
```shell
git clone https://github.com/realmetric/server.git
cd server
composer install
php -S localhost:8000 public/index.php  #or any other local server e.g. Nginx
```
Then open in your browser https://realmetric.github.io/?api=http://127.0.0.1:8000


## Sending data to Realmetric (tracking)
to API via cURL
```shell
curl -k -X POST https://127.0.0.1:8000/track -u login:sha1ofPassword -d '[{"event":"TopAction","category":"Product","value":3}]'
```
to UDP server
```shell
bin/console app:udp_server
echo -n '[{"event":"TopAction","category":"Product","value":3}]' | nc -4u -w0 localhost 8888
```

## Tests
```shell
bin/phpunit
```

---
Made inspired by https://github.com/statsd/statsd
