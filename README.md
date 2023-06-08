## Send your events to API
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

Example with CURL
```shell
curl -k -X POST https://127.0.0.1:8000/track -u login:sha1ofPassword -d '[{"event":"TopAction","category":"Product","value":3}]'
```
Example with fast UDP server
```shell
bin/console app:udp_server
echo -n '[{"event":"TopAction","category":"Product","value":3}]' | nc -4u -w0 localhost 8888
```

---
Made inspired by https://github.com/statsd/statsd
