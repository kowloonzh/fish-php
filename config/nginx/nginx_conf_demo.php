server {
    listen 8360;
    server_name {usr}.api.cloud.cn;
    root {root}/src/www;
    index index.php;

    access_log /data/nginx/logs/{usr}.api.cloud.cn-access.log combinedio;
    error_log  /data/nginx/logs/{usr}.api.cloud.cn-error.log;

    if (!-e $request_filename) {
        rewrite ^/(.*) /index.php?$1 last;
    }

    location ~ .*\.(php|php5)?$ {
        include       fastcgi.conf;
        fastcgi_pass  127.0.0.1:9000;
        fastcgi_index index.php;
    }
}

