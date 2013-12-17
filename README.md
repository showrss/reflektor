reflektor is a cache of caches. It contacts a set of given caches until it finds one that contains the requested info_hash, and then caches the request locally before serving it.

Set up your own
--

reflektor requires:

-   PHP 5.4
-   nginx
-   linux


Configuration
--
    
    server {
        listen 80;
    
        root /var/www/reflektor/public/;
        index index.html;
    
        server_name reflektor.karmorra.info;
    
        rewrite ^/torrent/(.+)\.torrent$ /serve.php?ih=$1 last;
        rewrite ^/torrent/?$ / redirect;
    
    	location /torrents/ {
    		internal;
    		alias /var/www/reflektor/cache/;
    	}
    
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php5-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
        }
    }
    
Dotdeb.org sources are recommended for an easy and painless setup.

Make sure permissions are correct, and that both PHP and nginx are allowed to read and write in the cache folder.
