#!/usr/bin/env bash

cat << EOF | sudo tee -a /etc/motd.tail
***************************************

Welcome to ubuntu/trusty64 Vagrant Box

For development

***************************************
EOF

sudo apt-get update

### Russian locale
sudo locale-gen ru_RU.UTF-8
sudo dpkg-reconfigure locales

## Web server with PHP5
sudo apt-get install -y git-core curl php5-cli php5-curl php5-dev make php5-sqlite \
    php5-mcrypt php5-gd php5-fpm nginx libsqlite3-0 libsqlite3-dev mc htop screen

sudo echo '' > /etc/nginx/sites-available/default
sudo cat > /etc/nginx/sites-available/default <<-'EOF'
upstream phpfpm {
	server unix:/var/run/php5-fpm.sock;
}

server {
	listen 80;
	server_name _;

	root /var/www;
	index index.php;


	location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

	location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }

	location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

	location / {
        try_files $uri $uri/ /index.php?$args;
    }

	location ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {
        expires 24h;
        log_not_found off;
    }

	location ~ \.php$ {
	    try_files $uri =404;

        # Fix for server variables that behave differently under nginx/php-fpm than typically expected
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        # Include the standard fastcgi_params file included with nginx
        include fastcgi_params;
        fastcgi_param  PATH_INFO        $fastcgi_path_info;
        fastcgi_index index.php;
        # Override the SCRIPT_FILENAME variable set by fastcgi_params
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        # Pass to upstream PHP-FPM; This must match whatever you name your upstream connection

        fastcgi_pass phpfpm;
    }

}

EOF

sudo service nginx restart