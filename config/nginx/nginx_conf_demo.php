server {
    listen 8360;
    server_name {usr}.hulk.com;
    root {root}/src/www;
    index index.php;

    access_log /data/nginx/logs/{usr}.hulk.com-access.log combinedio;
    error_log  /data/nginx/logs/{usr}.hulk.com-error.log;

    if (!-e $request_filename) {
        rewrite ^/(.*) /index.php?$1 last;
    }

    location ~ .*\.(php|php5)?$ {
        include       fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass  127.0.0.1:9000;
        fastcgi_index index.php;
    }
}
