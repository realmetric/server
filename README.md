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

# Self-signed ssl sertificate. Better use letsencrypt.org
sudo mkdir /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/nginx.key -out /etc/nginx/ssl/nginx.crt

```

### Configuring mysql (mysql.cfg) 
```shell
[mysqld]
pid-file        = /var/run/mysqld/mysqld.pid
socket          = /var/run/mysqld/mysqld.sock
# By default we only accept connections from localhost
bind-address    = 127.0.0.1
# Disabling symbolic-links is recommended to prevent assorted security risks
symbolic-links=0

# MyISAM #
key-buffer-size                = 16M

# SAFETY #
max-allowed-packet             = 16M
max-connect-errors             = 1000000
skip-name-resolve

# DATA STORAGE #
datadir                        = /var/lib/mysql/
default-storage-engine         = InnoDB

# CACHES AND LIMITS #
tmp-table-size                 = 32M
max-heap-table-size            = 32M
query-cache-type               = 0
query-cache-size               = 0
max-connections                = 50
thread-cache-size              = 50
open-files-limit               = 65535
table-definition-cache         = 1024
table-open-cache               = 2048

# INNODB #
innodb-flush-method            = O_DIRECT
innodb-log-files-in-group      = 2
innodb-log-file-size           = 64M
innodb-flush-log-at-trx-commit = 1
innodb-file-per-table          = 1
innodb-buffer-pool-size        = 160M

# LOGGING #
log-error                      = /var/log/mysql/error.log
log-queries-not-using-indexes  = 1
slow-query-log                 = 1
slow-query-log-file            = /var/lib/mysql/mysql-slow.log

```
