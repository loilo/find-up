<div align="center">
  <img alt="FindUp logo: a folder icon with an upwards arrow in front" src="find-up.svg" width="220" height="189">
</div>

# FindUp
![Test status on Travis](https://badgen.net/travis/loilo/find-up?label=tests&icon=travis)
![Version on packagist.org](https://badgen.net/packagist/v/loilo/find-up)

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
// walking up from the current working directory
Up::find('composer.json') === '/var/www/project/composer.json';
```

This is the most basic example how to use this package. The finder will look for a `composer.json` file, starting at the directory where `Up::find()` was called (which is `/var/www/project/src/foo`).

> **Note:** If there was no `composer.json` on the way up, the `find` method would return `null`.

### Starting Directory
As can be seen in the basic example above, the default starting directory is the one from where `Up::find()` is called. Alternatively, a starting directory can be passed into the `find` method as a second argument:

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

#### Prematurely Stop Searching
You can stop the search before reaching the filesystem root by returning the `Up::STOP` constant. For example, if you know that your `composer.json` is not found upwards from the `/var/www` folder, you can break out of the search (and make the `find` method return `null` instantly):

```php
Up::find(function ($file, $directory) {
  if ($directory === '/var/www') {
    return Up::STOP;
  }

  return $file === 'composer.json';
});
```

#### Skip Folder
If you are in a directory of which you're sure it does not contain the searched file, you may avoid walking through all its files by returning the `Up::SKIP` constant:

```php
Up::find(function ($file, $directory) {
  // Skip "src" directories
  if (basename($directory) === 'src') {
    return Up::SKIP;
  }

  return $file === 'composer.json';
});
```