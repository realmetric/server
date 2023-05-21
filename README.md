## Send your events to an API (POST /track)
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
