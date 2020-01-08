<div align="center">
  <img alt="FindUp logo: a folder icon with an upwards arrow in front" src="find-up.svg" width="220" height="189">
</div>

# FindUp
[![Test status on Travis](https://badgen.net/travis/loilo/find-up?label=tests&icon=travis)](https://travis-ci.org/loilo/find-up)
[![Version on packagist.org](https://badgen.net/packagist/v/loilo/find-up)](https://packagist.org/packages/loilo/find-up)

Find a file by walking up ancestor directories (e.g. a `composer.json` from inside a project).

## Installation
```bash
composer require loilo/find-up
```

## Usage
### Basic Example
```
/var/www
└── project
    ├── composer.json
    └── src
        └── foo
            ├── bar
            └── example.php
```

`example.php`:
```php
use Loilo\FindUp\Up;

// Get the project's composer.json's path by
// walking up from /var/www/project/src/foo
Up::find('composer.json') === '/var/www/project/composer.json';
```

This is the most basic example how to use this package. The finder will look for a `composer.json` file, starting at the directory where `Up::find()` was called (which is `/var/www/project/src/foo`).

> **Note:** If there was no `composer.json` on the way up, the `find` method would return `null`.

### Starting Directory
As can be seen in the basic example above, the default starting directory is the one from where `Up::find()` is called. Alternatively, a starting directory can be passed to the `find` method as a second argument:

```php
// Start from the current working directory
Up::find('composer.json', getcwd());
```

### Advanced Matching
Instead of a file name to search for, a (non-string) callable may be passed as the `find` method's first argument. It will receive each encountered file on the way up and decides whether it is the searched file.

Assuming we're starting in the `example.php` from the basic example directory tree above, we can find the path to the `composer.json` with the following call:

```php
Up::find(function ($file, $directory) {
  return $file === 'composer.json';
});
```

#### Stop Searching Prematurely
You can stop the search before reaching the filesystem root by returning an `Up::stop()` call. For example, if you know that your `composer.json` is not found upwards from the `/var/www` folder, you can break out of the search:

```php
Up::find(function ($file, $directory) {
  if ($directory === '/var/www') {
    return Up::stop();
  }

  return $file === 'composer.json';
});
```

When returning `Up::stop()`, the `Up::find()` method will return `null` by default.

You may however pass a path to the `stop()` method that will be used as the result. If the path is not absolute, it will be resolved against the current `$directory`:

```php
Up::find(function ($file, $directory) {
  if ($directory === '/var/www') {
    return Up::stop('stop.txt');
  }

  return false;
}) === '/var/www/stop.txt';
```

> **Note:** In previous versions, the way to stop searching was to return the `Up::STOP` constant.
>
> This technique still works, but it's deprecated and it's recommended to use `Up::stop()` instead.

#### Skip Folder
If you are in a directory of which you're sure it does not contain the searched file, you may avoid walking through all its files by returning `Up::skip()`:

```php
Up::find(function ($file, $directory) {
  // Skip "src" directories
  if (basename($directory) === 'src') {
    return Up::skip();
  }

  return $file === 'composer.json';
});
```

By default, the `Up::skip()` method will only skip the currently scanned directory. You may however pass a (positive) number that indicates how many levels of directories should be skipped.

```php
...

// skip the current directory and its parent directory,
// continue with grandparent directory
return Up::skip(2)
```

> **Note:** In previous versions, the way to skip scanning a folder was to return the `Up::SKIP` constant.
>
> This technique still works, but it's deprecated and it's recommended to use `Up::skip()` instead.

#### Jump to Other Folder
If you want to stop searching the current directory tree altogether and continue from another path, you can return an `Up::jump()` call:

```php
Up::find(function ($file, $directory) {
  if ($directory === '/var/www/project') {
    return Up::jump('/var/www/other-project');
  }

  return $file === 'composer.json';
});
```

> **Note:** You can only jump to a directory you have not previously visited in the current search. This serves to avoid infinite loops.
