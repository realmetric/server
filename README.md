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
You need PHP 8.1 or higher. PHP extensions: pdo, pdo_mysql or sqlite3, xml.
```shell
git clone https://github.com/realmetric/server.git
cd server
sudo apt install php8.1-pdo php8.1-mysql php8.1-sqlite3 php8.1-xml # if you dont have them yet
composer install
php -S localhost:8000 public/index.php  #or any other local server e.g. Nginx
```
Then open in your browser https://realmetric.github.io/?api=http://127.0.0.1:8000

Don't forget to create Production ENV on prod server
```shell
composer dump-env prod

# For nginx server you can use sample config and edit then
cp config/infrastructure/nginx.conf.sample config/infrastructure/nginx.prod.conf
```

## Sending data to Realmetric (tracking)
to API via cURL
```shell
curl -k -X POST https://127.0.0.1:8000/track -u login:sha1ofPassword -d '[{"event":"TopAction","category":"Product","value":3}]'
```
to UDP server
```shell
bin/console app:udp_server localhost 8888
echo -n '[{"event":"TopAction","category":"Product","value":3}]' | nc -4u -w0 localhost 8888
```

## Tests
```shell
bin/phpunit
```

---
Made inspired by https://github.com/statsd/statsd
