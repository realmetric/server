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
```php
$data = gzcompress(json_encode($events));
curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
```

# Installing
```shell
# All via ROOT
apt-get update

# Enable swap
dd if=/dev/zero of=/swapspace bs=1M count=4000
mkswap /swapspace
swapon /swapspace
echo "/swapspace none swap defaults 0 0" >> /etc/fstab

# Self-signed ssl sertificate. Better use letsencrypt.org
sudo mkdir /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/nginx.key -out /etc/nginx/ssl/nginx.crt

# Supervisor
sudo apt-get install supervisor
sudo ln -s /home/wdata/server/config/supervisor.conf /etc/supervisor/conf.d/tasks.conf
sudo service supervisor reload
```
