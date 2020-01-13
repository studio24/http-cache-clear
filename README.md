# Clear the expired items from HTTP Cache on the command line

Symfony's [HTTP Cache](https://symfony.com/doc/current/http_cache.html) is a powerful way to cache entire HTTP responses 
and provide fast, full-page caching for your website or web application.

However, due to a [known flaw](https://github.com/symfony/symfony/pull/6855) if you use the default filesystem method to 
store HTTP Cache files this does not clean up expired cache files, which can fill up a disk drive.

We created this small tool to help delete the expired cache files from Symfony's HTTP Cache.

## Installation

Install via [Composer](https://getcomposer.org/):

```
composer require studio24/http-cache-clear
```
    
## Usage

The default command clears the HTTP Cache of cache files that are 4 hours or older.

```
./bin/http-cache-clear
```

By default the command clears the cache in `var/cache` for the `prod` environment and for all files older than `4` hours. 
You can change these options on the command line. View help to see how:

```
./bin/http-cache-clear -h
```

One note on the `--path` option. The command appends the environment and `http_cache` folder, so the following command 
actually clears the HTTP cache in `cache/prod/http_cache`. 

```
./bin/http-cache-clear --path=cache
```

The default is to clear all files older than 4 hours. You can change this by passing the `expiry` argument.

```
./bin/http-cache-clear var/cache --expiry=24 
```

## Tests

Run phpunit:

```
./vendor/bin/phpunit
```

Run codesniffer:

```
./vendor/bin/phpcs
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Maria Beznea](https://www.studio24.net/)
- [Simon R Jones](https://github.com/simonrjones)


