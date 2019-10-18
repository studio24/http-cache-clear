# Clear the expired items from HTTP Cache on the command line

Simple tool to help delete the expired files from the http_cache directory. The HttpCache Symfony component basically caches full HTTP responses based on Cache-Control headers. The problem is the old files are not deleted, so eventually they fill up disk space.

## Usage

Clear the http_cache directory of files older than the specified number of hours:

    bin/run clear:httpCache  /var/cache/prod/http_cache 4
   
 Where _/var/cache/prod/http_cache_ is the root path of HTTPCache directory and _4_ is the number of hours 

## Installation

This CLI scripts uses the [Symfony Console](http://symfony.com/doc/current/components/console/index.html) component. 
Use [Composer](http://getcomposer.org) to load this.

To install run the following commands:


```
git clone git@github.com:studio24/http-cache-clear.git
cd http-cache-clear
composer install
```

## License

MIT License (MIT)  
Copyright (c) 2019 Studio 24 Ltd (www.studio24.net)

