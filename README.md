# Reflektor
Reflektor is a cache of caches. It contacts a set of given caches until it finds one that contains the requested info_hash, and then caches the request locally before serving it.

Set up your own
--

reflektor requires:

-   PHP 5.4
-   nginx
-   linux


# Configuration
## nginx configuration:
    # Rate-limiting pools:
    # For reflektor.showrss.info, 1MB (about 16k states), limit to 1 request/second
    # Rate-limit overall connections to this vhost to 10/s
    # Limit the overall number of connections
    limit_req_zone $binary_remote_addr zone=reflektor_ip_req:1m rate=1r/s;
    limit_req_zone $server_name zone=reflektor_vhost_req:1m rate=10r/s;
    limit_conn_zone $server_name zone=reflektor_vhost_conn:1m;
    
    limit_req_status 503;
    limit_req_log_level error;
    limit_conn_status 503;
    limit_conn_log_level error;
    
    server {
        listen 80;
        
        root /var/www/reflektor/public/;
        index index.html;
        
        server_name reflektor.karmorra.info;
        
        rewrite "^/torrent/([A-Fa-f0-9]{40})\.[Tt][Oo][Rr]{2}[Ee][Nn][Tt]$" /serve.php?ih=$1 last;
        rewrite ^/torrent/?$ / redirect;
        
        limit_req_log_level info;
        limit_req zone=reflektor_ip_req burst=3;
        limit_req zone=reflektor_vhost_req burst=20;
        limit_req_status 429;
        error_page 429 /rate_limit.html;
        limit_conn reflektor_vhost_conn 50;
        limit_conn_status 503;
        
        location /rate_limit.html {
            internal;
            access_log off;
        }
        
        location /torrents/ {
            internal;
            alias /var/www/reflektor/cache/;
            access_log off;
        }
        
        location = /serve.php {
            fastcgi_pass unix:/var/run/php5-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            access_log off;
        }
    }

# Recommendations
* It is advisable to configure a deadicated php-fpm pool for reflektor, with
increased `pm.max_children`, `pm.start_servers`, `pm.min_spare_servers`, and 
`pm.max_spare_servers` values. Tuning these values is important for performance.
* Make sure permissions are correct, and that nginx is allowed to read the cache
folder, and PHP is allowed to read and write the cache folder.
* Cached torrents older than 7 days can be cleaned with the following command:
`find /var/www/reflektor/cache/ -type f -name '*.torrent' -mtime +7 -delete`

