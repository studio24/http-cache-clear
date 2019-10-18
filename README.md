# Clear the expired items from HTTP Cache on the command line

Symfony's [HTTP Cache](https://symfony.com/doc/current/http_cache.html) is a powerful way to cache entire HTTP responses 
and provide fast, full-page caching for your website or web application.

However, due to a [known flaw](https://github.com/symfony/symfony/pull/6855) if you use the default filesystem method to 
store HTTP Cache files this does not clean up expired cache files, which can fill up a disk drive.

We created this small tool to help delete the expired files from Symfony's HTTP Cache. 

## Installation

Install via [Composer](https://getcomposer.org/):

```
composer require studio24/http-clear-cache:^1.0
```


## Usage

The default command clears the HTTP Cache of cache files that are 4 hours or older.

```
http-cache-clear <path>
```

The first required argument is the path to your HttpCache cache folder, e.g. var/cache/prod/http_cache

```
http-cache-clear var/cache/prod/http_cache
```

 
The default is to clear all files older than 4 hours. You can change this by passing the `hours` argument.

```
http-cache-clear var/cache/prod/http_cache --hours 24 
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Maria Beznea](https://www.studio24.net/)
- [Simon R Jones](https://github.com/simonrjones)


