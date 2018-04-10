# How to Track events:
### Prepare json with arrays of data
```json
# events
[
    {
        "metric": "Product.Metric",
        "value": 12355,
        "time": 1505906051,
        "slices": [
            {
                "project": "some_project_1",
                "lifetime": 5,
            },
            {
                "category": "name",
            }
        ]
    },
    {
        "metric": "Product.Metric2",
        "slices": "..."
    }
]
```
### Compress data with gzip
```php
# PHP
$data = gzcompress(json_encode(events));
```
```python
# Python
data = zlib.compress(events)
```

### Make POST request to /track
```php
# PHP
curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
```
```python
# Python
# http://docs.python-requests.org
r = requests.post(url, json=data)
```






# Installing
```shell
# All via ROOT
sudo -i

# Add repos
add-apt-repository ppa:ondrej/php
apt-get update

# PHP 7.1
apt-get install php7.1
apt-get install php7.1-mbstring 

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

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
