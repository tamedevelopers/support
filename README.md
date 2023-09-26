# Support Package

Support Package For Tamedevelopers

## Documentation
* [Requirements](#requirements)
* [Installation](#installation)
* [Asset](#Asset)
    * [Asset config](#asset-config)
        * [Asset Cache](#asset-cache)
* [Env](#env)
    * [Env Load](#env-load)
    * [Env Update](#env-update)
* [Server](#server)
    * [Get Servers](#get-servers)
    * [Create Custom Config](#create-custom-config)
    * [Create Config Template File](#create-config-template-file)
* [Autoload Register](#autoload-register)
* [Helpers Functions](#helpers-functions)
* [Error Dump](#error-dump)
* [Error Status](#error-status)
* [Useful links](#useful-links)


## Requirements

- `>= php 8.0+`

## Installation

Prior to installing `support package` get the [Composer](https://getcomposer.org) dependency manager for PHP because it'll simplify installation.

**Step 1** — update your `composer.json`:
```composer.json
"require": {
    "tamedevelopers/support": "^1.0.3"
}
```

**Step 2** — run [Composer](https://getcomposer.org):
```update
composer update
```

**Or composer require**:
```
composer require peterson/database
```


## Asset
- Takes a param as `string` path to asset file
    - Default [dir] is set to `assets`

```
use Tamedevelopers\Support\Asset;

Asset::asset('css/style.css');

- Returns
http://domain.com/assets/css/style.css
```

- or -- `Helpers Function`
```
asset('css/style.css');
```

## Asset Config
- Takes two param as `string` 
    - `$base_path` path base directory
    - `$cache` Tells method to return `cache` of assets.
        - You'll see a link representation as `http://domain.com/[path_to_asset_file]?v=111111111`

```
use Tamedevelopers\Support\Asset;

Asset::config('public/storage');

- Returns
http://domain.com/public/storage/[asset_file]
```

- or -- `Helpers Function`
```
asset_config('public');
```

### Asset Cache
- By Default, `$cache` is set to `true`

```
Asset::config('storage', false);

- Returns
http://domain.com/storage/[asset_file]
```

- or -- `Helpers Function`
```
asset_config('storage');

http://domain.com/storage/[asset_file]?v=111111111
```

## ENV
- By default it use the default root as `.env` path, so mandatory to install vendor in root directory.


### ENV Load
- To load the environment `.env` file
    - Takes optional param as `string` $path

```
use Tamedevelopers\Support\Env;

Env::load('optional_custom_path_to_env_file')
```

- or -- `loadOrFail('optional_path')`
    - Just as the name says. It'll load the `.env` file or fail with status code of 404. An error logger will also be created inside `storage/logs/orm.log`

```
Env::loadOrFail('optional_custom_path_to_env_file')
```

### ENV Update
- Returns `true|false`. Used to update env variables

| Params        |  Description      |
|---------------|-------------------|
| key           |  ENV key          |
| value         |  ENV value        |
| allow_quote   |  `true` \| `false` - Default is true (Allow quotes within value)  |
| allow_space   | `true` \| `false`  - Default is false (Allow space between key and value)|

```
use Tamedevelopers\Support\Env;

Env::updateENV('DB_PASSWORD', 'newPassword');
```

- or -- `Helpers Function`
```
env_update('DB_CHARSET', 'utf8', false);
```

## Server
- Return instance of `Server`


### Get Servers
- Returns assoc arrays of Server
    - `server\|domain\|protocol`

```
use Tamedevelopers\Support\Server;

Server::getServers();
```

- or -- `Helpers Function`
```
server()->getServers('domain');
```

### Create Custom Config
- With this helper you can be able to create your own custom config by extending the Default Config Method
    - When using this model, make sure every of your php file returns an associative array for the key to work

| Params        |  Description      |
|---------------|-------------------|
| key           |  File array key   |
| default       |  Default value if no data is found from the key       |
| folder        |  Folder to search from and Default folder is `config` |

```
use Tamedevelopers\Support\Server;

Server::config('tests.lang.email', [], 'Tests');
```

- Create our own config to extends the default
```
/**
 * Custom Language Handler
 *
 * @param  mixed $key
 * @return mixed
 */
function __($key){

    // since the config only takes the filename follow by dot(.) and keyname
    // then we can manually include additional folder-name followed by / to indicate that it's a folder
    // then message.key_name
    // To make this Laravel kind of language, we can add the default value to be returned as the key
    // Do not forget that it starts from your root base directory, as the Package already has your root path

    return config("en/message.{$key}", "message.{$key}", 'Lang');
}


--- Structure of folder example
--- (d) for directory and (f) for file


Base/
├── Lang/
│   ├── en/
|   |   ────── message.php (File)
|   |   ────── error.php (File)
|   |
│   ├── tr/
|   |   ────── message.php (File)
|   |   ────── error.php (File)
│   └── ...
└── ...
```

- or -- `Helpers Function`
```
server()->config("en/message.{$key}", "message.{$key}", 'Lang');

server()->config("app.name");
```

## Autoload Register
- Takes an `string\|array` as param
    - You can use register a folder containing all needed files
    - This automatically register `Files\|Classes` in the folder and sub-folders.

```
use Tamedevelopers\Support\AutoloadRegister;

AutoloadRegister::load('folder');

or
autoload_register(['folder', 'folder2]);
```

## Helpers Functions

| function name             | Description                                   |
|---------------------------|-----------------------------------------------|
| autoload_register()       | Same as `AutoloadRegister::load()`            |
| env()                     | env method `To get environment variable`      |
| env_update()              | Same as `Env::updateENV` method               |
| server()                  | Return instance of `(new Server)` class       |
| asset()                   | Return Absolute path of asset. Same as `Asset::asset()`   |
| asset_config()            | Same as `Asset::config()`. Configure Asset root directory |
| base_path()               | Get absolute base directory path. It accepts a param as `string` if given, will be appended to the path |
| directory()               | Same as `base_path()` just naming difference        |
| domain()                  | Similar to `base_path()` as it returns domain URI. Also accepts path given and this will append to the endpoint of URL. |
| to_array()                | `array` Convert value to array                |
| to_object()               | `object` Convert value to object              |
| to_json()                 | `string` Convert value to json                |

## Error Dump

| function  | Description       |
|-----------|-------------------|
| dump      | Dump Data         |
| dd        | Dump and Die      |


## Useful Links

- @author Fredrick Peterson (Tame Developers)
- If you love this PHP Library, you can [Buy Tame Developers a coffee](https://www.buymeacoffee.com/tamedevelopers)