# Setup Environment

## Install sublime text plugin for editorconfig

[Setup Guide](https://github.com/sindresorhus/editorconfig-sublime#readme)

## Setup PHP

1. Install PHP
The Yii2.0 require the verison of PHP must be >= 5.4.0, so before install PHP and its extensions, you must do like below first
```sh
sudo add-apt-repository ppa:ondrej/php5
sudo apt-get update
```
Then install PHP and its extensions
```sh
sudo apt-get install php5-cgi php5-fpm php5-curl php5-mcrypt php5-gd php5-dev
```

2. PHP-FPM Status Management

```sh
sudo service php5-fpm {start|stop|quit|restart|reload|logrotate}
```

## Setup Redis

1. Install Redis
```sh
sudo apt-get install redis-server
```

2. Install PHP Redis extension
```sh
sudo apt-get install php5-redis
```

## Setup nginx

1. Install nginx
```sh
sudo apt-get install nginx
```

2. Config nginx
```sh
vi /etc/nginx/conf.d/wm.conf
```

Add below configuration to the file, **Change the folder name to your own project (/usr/share/nginx/www/aug-marketing)**

```sh
server {
    listen       80;
    server_name wm.com *.wm.com;
    root  /usr/share/nginx/www/aug-marketing/src/backend/web;
    index index.html index.htm index.php;

    access_log /var/log/nginx/wm.com-access.log;
    error_log  /var/log/nginx/wm.com-error.log;

    location / {
        proxy_pass http://wm.com:81;
    }

    location /api {
        try_files $uri $uri/ /index.php?$args;
    }

    location /webapp/build/ {
        alias /usr/share/nginx/www/aug-marketing/src/webapp/web/build/;
    }

    location /webapp {
        proxy_pass http://wm.com:82;
    }

    location ~ .*\.(php|php5)?$ {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        # or fastcgi_pass   127.0.0.1:9000;
        include        fastcgi_params;
    }

    location ~ /\.(ht|svn|git) {
            deny all;
    }
}

server {
    listen       81;
    server_name wm.com *.wm.com;
    root  /usr/share/nginx/www/aug-marketing/src/frontend/web;
    index index.html index.htm index.php;

    access_log /var/log/nginx/wm.com-access.log;
    error_log  /var/log/nginx/wm.com-error.log;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location /vendor/ {
        alias /usr/share/nginx/www/aug-marketing/src/vendor/;
    }

    location /dist/ {
        alias /usr/share/nginx/www/aug-marketing/src/web/dist/;
    }

    location ~ .*\.(php|php5)?$ {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        # or fastcgi_pass   127.0.0.1:9000;
        include        fastcgi_params;
    }

    location ~ /\.(ht|svn|git) {
            deny all;
    }
}

server {
    listen       82;
    server_name wm.com *.wm.com;
    root /usr/share/nginx/html/aug-marketing/src/webapp/web;
    index index.php;

    access_log /var/log/nginx/wm-webapp-access.log;
    error_log  /var/log/nginx/wm-webapp-error.log;

    location / {
        try_files $uri $uri/ /index.php?$args;
        #try_files $uri$args $uri$args/ index.php;
    }

    location /vendor/ {
        alias /usr/share/nginx/html/aug-marketing/src/vendor/;
    }

    location /dist/ {
        alias /usr/share/nginx/html/aug-marketing/src/web/dist/;
    }

    location ~ .*\.(php|php5)?$ {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        # or fastcgi_pass   127.0.0.1:9000;
        include        fastcgi_params;
    }

    location ~ /\.(ht|svn|git) {
            deny all;
    }

    location ~ /.+\.(coffee|scss) {
           deny all;
    }
}
```

**Restart nginx to make the configuration work**

```sh
sudo service nginx reload
```

## Setup Mongo

### Setup with pecl

1. Install Mongo
Follow the [setup guide](http://docs.mongodb.org/manual/tutorial/install-mongodb-on-ubuntu/), and select the 2.6 version to install.

2. Install PHP Mongo Extension
```sh
sudo pecl install mongo
```
After install successfully, create 'mongo.ini' file in `/etc/php5/mods-available`, write 'extension=mongo.so' to it. Then create symbol link like below
```sh
sudo ln -s /etc/php5/mods-available/mongo.ini /etc/php5/fpm/conf.d/30-mongo.ini
sudo ln -s /etc/php5/mods-available/mongo.ini /etc/php5/cgi/conf.d/30-mongo.ini
sudo ln -s /etc/php5/mods-available/mongo.ini /etc/php5/cli/conf.d/30-mongo.ini
```

3. Restart PHP-fpm
```sh
sudo service php5-fpm restart
```

### Setup with souce code

1. Install Mongo
Follow the [setup guide](http://docs.mongodb.org/manual/tutorial/install-mongodb-on-ubuntu/), and select the 2.6 version to install.

2. Install PHP Mongo Extension
Download souce code from github [mongo php driver](https://github.com/mongodb/mongo-php-driver)
```sh
$ tar zxvf mongodb-mongodb-php-driver-<commit_id>.tar.gz
$ cd mongodb-mongodb-php-driver-<commit_id>
$ phpize
$ ./configure
$ sudo make install
```
After install successfully, create 'mongo.ini' file in `/etc/php5/mods-available`, write 'extension=mongo.so' to it.

3. Restart PHP-fpm
```sh
sudo service php5-fpm restart
```

## Configure your host file

```sh
vi /etc/hosts
```
Add below line to your host file
```sh
127.0.0.1 wm.com
```

## Setup SCSS

1. Install ruby (version > 1.9.1)
```sh
sudo apt-get install ruby
```

2. Use mirror for ruby
```sh
gem sources --remove http://rubygems.org/
gem sources -a https://ruby.taobao.org/
gem sources -l
*** CURRENT SOURCES ***
https://ruby.taobao.org
```
Ensure only ruby.taobao.org exists

3. Install SASS
```sh
gem install sass
```

## Setup grunt and bower

1. Install nodejs
```sh
curl https://raw.githubusercontent.com/creationix/nvm/v0.25.1/install.sh | bash
. ~/.profile
nvm install v0.10.24
```

2. Install grunt
```sh
npm install -g grunt-cli
```

3. Setup bower
```sh
npm install -g bower
```

## Initialize project

```sh
cd src
./initDev
cd ..
./updateModules
```

## Run grunt task

```sh
cd src
npm install
grunt
```

## Generate test account locally

**If your mongo version is 2.6.x, you should run command as follows to create database auth user**
```sh
mongo 
> use wm
> db.addUser("root", "root")
```

**If your mongo version is 3.0.x, you should run command as follows to create database auth user**
```sh
mongo 
> use wm
> db.createUser({user:"root",pwd:"root",roles:[{role:"dbOwner",db:"wm"}]});
```

```sh
cd src
./yii management/account/generate-by-email  11@qq.com
```

**Every time you pull code from our repo, execute 'grunt cbuild'**

Access http://wm.com with the test account generated with command above, default password is `abc123_`

## Reference

* [Grunt Get Started](http://gruntjs.cn/getting-started/)
* [Bower](http://bower.io)
* [SASS用法指南](http://www.ruanyifeng.com/blog/2012/06/sass.html)
* [CoffeeScript中文教程](http://coffee-script.org/)
* [mongo-php-driver安装](http://www.runoob.com/mongodb/mongodb-install-php-driver.html)
