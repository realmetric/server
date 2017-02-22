# API points:
### POST: /track
```json
[
    {
        "metric": "Product.Some.Metric",
        "value": 123.78,
        "time": "",
        "slices": [
            {
                "category": "project",
                "name": "some_project_1"
            },
            {
                "category": "paid",
                "name": "yes"
            }
        ]
    },
    {
        "metric": "Product.Some.Metric2",
        "slices": "..."
    }
]
```

# Installing
```shell
# All in ROOT
apt-get update

# Enable swap
dd if=/dev/zero of=/swapspace bs=1M count=4000
mkswap /swapspace
swapon /swapspace
echo "/swapspace none swap defaults 0 0" >> /etc/fstab
```
