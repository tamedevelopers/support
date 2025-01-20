# Support Package
Support Package For PHP and Laravel

[![Total Downloads](https://poser.pugx.org/tamedevelopers/support/downloads)](https://packagist.org/packages/tamedevelopers/support)
[![Latest Stable Version](https://poser.pugx.org/tamedevelopers/support/version)](https://packagist.org/packages/tamedevelopers/support)
[![License](https://poser.pugx.org/tamedevelopers/support/license)](https://packagist.org/packages/tamedevelopers/support)

## Documentation
* [Requirements](#requirements)
* [Installation](#installation)
* [All Paths](#all-paths)
* [Number to Words](#number-to-words)
* [Tame](#tame)
    * [byteToUnit](#byteToUnit)
    * [unitToByte](#unitToByte)
    * [fileTime](#fileTime)
    * [exists](#exists)
    * [unlink](#unlink)
    * [mask](#mask)
    * [imageToBase64](#imageToBase64)
    * [emailValidator](#emailValidator)
    * [platformIcon](#platformIcon)
    * [paymentIcon](#paymentIcon)
    * [calPercentageBetweenNumbers](#calPercentageBetweenNumbers)
    * [formatNumberToNearestThousand](#formatNumberToNearestThousand)
    * [calculateVolumeWeight](#calculateVolumeWeight)
    * [calculateCubicMeterWeight](#calculateCubicMeterWeight)
    * [getBetweenBoxLengthAndWeightInKg](#getBetweenBoxLengthAndWeightInKg)
    * [getBetweenBoxLengthAndWeightInCMB](#getBetweenBoxLengthAndWeightInCMB)
* [Str](#str)
    * [phone](#phone)
    * [mask](#mask)
    * [html](#html)
    * [text](#text)
    * [shorten](#shorten)
    * [random](#random)
    * [formatString](#formatString)
    * [encrypt](#encrypt)
    * [decrypt](#decrypt)
    * [bindings](#bindings)
    * [flattenValue](#flattenValue)
    * [exceptArray](#exceptArray)
    * [replaceFirst](#replaceFirst)
    * [replaceLast](#replaceLast)
    * [removeKeysFromArray](#removeKeysFromArray)
    * [changeKeysFromArray](#changeKeysFromArray)
    * [convertArrayKey](#convertArrayKey)
    * [convertArrayCase](#convertArrayCase)
* [Zip](#zip)
    * [Unzip](#unzip)
    * [Zip Download](#zip-download)
* [PDF](#pdf)
    * [Read PDF](#read-pdf)
* [Time](#time)
* [Cookie](#cookie)
* [Hash](#hash)
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

```
composer require tamedevelopers/support
```

## All Paths

| function name             | Description                                   |
|---------------------------|-----------------------------------------------|
| base_path()               | Get absolute base directory path. Accepts a param `string` if given, and append to path   |
| directory()               | Thesame as `base_path()`                         |
| public_path()             | Root/public path. Accepts a param `string` if given, and append to path                   |
| storage_path()            | Root/storage path. Accepts a param `string` if given, and append to path                  |
| app_path()                | Root/app path. Accepts a param `string` if given, and append to path                      |
| config_path()             | Root/config path. Accepts a param `string` if given, and append to path                   |
| lang_path()               | Root/lang path. Accepts a param `string` if given, and append to path                     |
| domain()                  | Returns domain URI. Accepts a param `string` if given, and append to path                 |


## Number to Words
- Has three chainable methods
    - Can translate all the way to `vigintillion`

![Sample Units](https://raw.githubusercontent.com/tamedevelopers/support/master/thousand_units.png)

| iso (country iso3)         | cents | number |
|----------------------------|-------|--------|
| `NGA \| GBR \| USA `| `true \| false` | `int\|float\|string` |
| If `iso` is given and found, it'll automatically converts the text into a currency format | If you want the decimals to be translated to text as well. | numeric figures: `299 \| '42,982' \| 3200.98` |


### ISO
- Takes param as `string` and case-insensitive

```
NumberToWords::iso('nga');
```

### Cents
- Takes param as `boolean`. Default is `false`
    - By default, it doesn't format the decimals `.00` Must be set to true, to format if needed.

```
NumberToWords::cents(true);
```

### Value
- Takes one param as `int | float | string`
    - If numbers is larger than a trillion `1_000_000_000_000`, then the value must be passed as a string.

```
NumberToWords::value(1290);
```

### Usage -- `toText()`
- Convert number to readable words
    - Below, we're using the function helper method
```
NumberToWords()
        ->iso('TUR')
        ->value('120.953')
        ->cents(true)
        ->toText();

// One hundred and twenty lira, nine hundred and fifty-three kuruş
```

### Usage -- `toNumber()`
- Convert words to number
    - comma `, ` is used to seperate decimals in words
```
use Tamedevelopers\Support\NumberToWords;


NumberToWords::value('twelve million three hundred thousand, six hundred and ninety-eight')
        ->cents(true)
        ->toNumber()

// 12300000.698
```

## Tame
- The Core Class of Components
    - It's helper class can be called, using -- `Tame()`

```
Tamedevelopers\Support\Tame
``` 

### byteToUnit
- Accepts 5 param. first param alone is needed
    - All other params are [optional]

| Params    | Description                                   |
|-----------|-----------------------------------------------|
| bytes     | The size in bytes to be converted             |
| format    | Whether to preserve case (default: lowercase) |
| gb        | Custom label for GB (default: 'GB')           |
| mb        | Custom label for MB (default: 'MB')           |
| kb        | Custom label for KB (default: 'KB')           |

```
Tame()->byteToUnit(6880);

// Output: 7kb
```

### unitToByte
```
Tame()->unitToByte('24mb');

// Output: 25165824
```

### fileTime
- Returns last edited time of the file as an - `int|false`

```
Tame()->fileTime(base_path('filepath.php'));
```

### exists
- Checks if a file exists and is not a directory - `bool`

```
Tame()->exists(base_path('filepath.php'));
// Output: true or false
```

### unlink
- Deletes a file from the server if it exists and does not match the restricted file name - `void`
    - [optional] second param <filename.extension>

```
Tame()->unlink(base_path('path/to/directory/avatar.png'), 'default.png');
```

### mask  
- Masks characters in a string based on position and length, with support for emails and custom masking characters.

| Params        | Description                                                                                   |
|---------------|-----------------------------------------------------------------------------------------------|
| `$str`        | The string to be masked.                                                                      |
| `$length`     | The number of characters to mask (default is 4).                                              |
| `$position`   | The position to apply the mask: `'left'`, `'center'`, or `'right'` (default is `'right'`).    |
| `$mask`       | The character used for masking (default is `*`).                                              |

#### Example:
```
Tame()->mask('example@email.com', 4, 'left');
// Output: "****ple@email.com"

Tame()->mask('example@email.com', 4, 'right');
// Output: "exa****@email.com"

Tame()->mask('shortstring', 4, 'center');
// Output: "sho****ing"
```

### imageToBase64
- Converts an image file to its Base64 representation. Supports local files and direct URLs - `null|string`

```
Tame()->imageToBase64(base_path('path/to/image.jpg'));
// Output: "data:image/jpg;base64,..." (Base64 string for the image)


Tame()->imageToBase64('https://example.com/image.png', true);
// Output: "data:image/png;base64,..." (Base64 string for the URL image)
```

### emailValidator
- Validates an email address with optional domain and server verification - `bool`

| Params         | Description                                                                                                   |
|----------------|---------------------------------------------------------------------------------------------------------------|
| email          | The email address to validate.                                                                                |
| use_internet   | By default is set to `false`. If `true`, checks the domain using DNS (`checkdnsrr()` and `getmxrr()`) for validity. If `false`, skips domain validation (default: `false`). |
| server_verify  | Verifies the mail server by checking MX records (default: `false`). Only used if `use_internet` is `true`.   |

```
Tame()->emailValidator('example@example.com');
// Output: true (Valid email with domain check using DNS)


Tame()->emailValidator('example@example.com', false);
// Output: true (Valid format only, no internet or DNS checks)

Tame()->emailValidator('example@example.com', true, true);
// Output: true or false (Valid format with domain and server verification)
```

### platformIcon  
- Returns the path to the SVG icon for the specified platform or operating system.

| Params       |Description                  |
|--------------|-----------------------------|
| `$platform`  | Platform name `windows \| linux \| android \| mobile \| phone \| unknown \| mac \| macintosh \| ios \| iphone \| c \| os x` |
| `$os_name`   | OS name `'macos'`, `'os x'`, `'ios'` |

#### Example

```php
$platform = Tame()->platformIcon('windows');
// Output: /path/to/icons/platform/windows.svg

include $platform;
```

### paymentIcon  
- Retrieves the path to the SVG icon for a specified payment method.

| Params      | Description                                                                                                               |
|-------------|---------------------------------------------------------------------------------------------------------------------------|
| `$payment`  | `add-money \| alipay \| bank \| cc \| credit-card \| discover \| faster-pay \| groupbuy \| maestro \| mastercard \| pay \| payme \| payment-card \| payment-wallet \| paypal \| stripe-circle \| tripe-sqaure \| stripe \| visa` |

#### Example

```php
$payment =  Tame()->paymentIcon('paypal');
// Output: /path/to/icons/payment/paypal.svg

include $payment;
```

### calPercentageBetweenNumbers
- Calculates the percentage relationship between two numbers as an - `int`

```
Tame()->calPercentageBetweenNumbers(100, 80);
```

### formatNumberToNearestThousand
- Formats a number to its nearest thousand, million, billion, or higher as a - `string|float|int`

```
Tame()->formatNumberToNearestThousand(1500000);
// Output: "1.5m"
```

## Str
- The Core Class For String Manipulations
    - It's helper class can be called, using -- `TameStr()`

```
Tamedevelopers\Support\Str
``` 

## Zip
- Takes two param as `string`
    - [sourcePath] relative path of zip-file
    - [destination] relative folder path to save zip file

```
TameZip()->zip('app/Http', 'app.zip')
``` 

### Unzip
- Takes two param as `string`
    - [sourcePath] relative path of zip-file
    - [destination] relative folder path to unzip-file

```
TameZip()->unzip('newData.zip', '/public/zip')
```

### Zip Download
- Takes two param as `string | boolean`
    - [fileName] relative path of zip-file
    - [unlink] Default is `true` unlinks file after download

```
TameZip()->download('newData.zip')
```

## PDF
- Require package to be installed - `composer require dompdf/dompdf`

| options                   | Description           |
|-----------------------    |-----------------------|
| content `string`          | HTML Content          |
| paper_size `string`       | Default is `A4` --- `letter \| legal` |
| paper_type `string`       | Default is `portrait` --- `landscape` |
| destination `string`      | Full path to where file has to be save `public_path(invoice/file.pdf)` 
    By default it saves the pdf generated by timename to your project root [dir] |
| output `string`           | Default is `view`  --- `save \| download` |
| isRemoteEnabled `bool`    | Default is `false` ---  `true`  If the content of html contains file/image link |
| title `string`            | If the html content of PDF has no title, file name will automatically become the title |
| delete `bool`             | Default is `true` ---  `false`  If output is `view` you can choose to delete file after preview |


```
Tamedevelopers\Support\PDF

$generate = strtotime('now') . '.pdf';

PDF::create([
    'content'     => '<h1>Hello World!</h1>',
    'destination' => public_path("invoice/{$generate}"),
    'output'      => 'view',
]);
```
- or -- `Helpers Function`
```
TamePDF()->create([
    'content'     => '<h1>Hello World!</h1>',
    'destination' => public_path("invoice/{$generate}"),
    'output'      => 'save',
]);
```

### Read PDF
- Takes one param as `string`
    - [path] absolute path to PDF file

```
TamePDF()->read('invoice100.pdf')

This will read the PDF to the browser
```

## Time
```
Visit the Tests/ folder to see more examples.
```

## Cookie

| function name   | Description                 |
|-----------------|-----------------------------|
| set()           | Used to set cookie          |
| get()           | Used to get cookie          |
| forget()        | Used to expire cookie       |
| expire()        | Same as `forget` method     |
| all()           | Get all available cookie    |
| has()           | Cookie exists               |


### Set
- Takes `7 param`
    - Mandatory `$name` param as `string`
    - [optional] `$value` param as `string | null`
    - [optional] `$minutes` param as `int | string`
    - [optional] `$path` param as `string | null`
    - [optional] `$domain` param as `string | null`
    - [optional] `$secure` param as `bool | null`
    - [optional] `$httponly` param as `bool | null`

```
use Tamedevelopers\Support\Cookie;

Cookie::set('cookie_name', 'value');
```

### Get
- Takes param as `string`

```
Cookie::get('cookie_name');
```

### Forget
- Takes `3 param`
    - Mandatory `$name` param as `string`
    - [optional] `$path` param as `string | null`
    - [optional] `$domain` param as `string | null`

```
Cookie::forget('cookie_name');
```

### Has
- Takes param as `string`
    - Returns `bool`

```
if(Cookie::has('cookie_name')){
    // execute code
}
```

- or -- `Helpers Function`
```
TameCookie()->set('user', '__user');
```

## Hash
- Password hashing and verify

```
use Tamedevelopers\Support\Hash;

bcrypt('testPassword');
or
Hash::make('testPassword');

// $2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6
```

### Password verify 
```
$oldPassword = "$2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6";

Hash::check('testPassword', $oldPassword)

password_verify('testPassword', $oldPassword);
```

## Asset
- Takes a param as `string` path to asset file
    - Default [dir] is set to `public`

```
use Tamedevelopers\Support\Asset;

Asset::asset('css/style.css');

- Returns
http://domain.com/assets/css/style.css
```

- or -- `Helpers Function`
```
tasset('css/style.css');
```

## Asset Config
- Takes three param as `string` 

| params        | Description                 |
|---------------|-----------------------------|
| base_path     | PAth to file                  |
| cache         | By Default `cache` is set to `false`. Tell method to include cache for each file  |
| path_type     | By Default `cache` is set to `false`, which uses absolute path for all files. While `true` will use relative path |
        

```
use Tamedevelopers\Support\Asset;

Asset::config('public/storage');

- Returns
http://domain.com/public/storage/[asset_file]
```

- or -- `Helpers Function`
```
config_asset('public');
```

### Asset Cache
- By Default, `cache` is set to `false`
    - You'll see a link representation as `http://domain.com/[path_to_asset_file]?v=111111111`

```
Asset::config('storage', false);

- Returns
http://domain.com/storage/[asset_file]
```

- or -- `Helpers Function`
```
asset_config('storage/main.js', true);
// Output: http://domain.com/storage/main.js?v=111111111


asset_config('storage/style.css', true, true);
// Output: /storage/style.css?v=111111111
```

## ENV
- By default it use the default root as `.env` path, so mandatory to install vendor in root directory.


### ENV Load
- To load the environment `.env` file
    - Takes optional param as `string` $path

```
use Tamedevelopers\Support\Env;

Env::load('path_to_env_folder')
```

- or -- `loadOrFail('optional_path')`
    - Just as the name says. It'll load the `.env` file or fail with status code of 404. An error logger will also be created inside `storage/logs/orm.log`

```
Env::loadOrFail('path_to_env_folder')
```

### ENV Update
- Returns `true|false`. Used to update env variables

| Params        |  Description      |
|---------------|-------------------|
| key           |  ENV key          |
| value         |  ENV value        |
| allow_quote   |  `true \| false` - Default is true (Allow quotes within value)            |
| allow_space   |  `true \| false`  - Default is false (Allow space between key and value)  |

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
    - `server\|domain`

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
function __lang($key){

    // since the config only takes the filename follow by dot(.) and keyname
    // then we can manually include additional folder-name followed by / to indicate that it's a folder
    // then message.key_name
    // To make this Laravel kind of language, we can add the default value to be returned as the key
    // Do not forget that it starts from your root base directory, as the Package already has your root path

    return Server()->config("en/message.{$key}", "message.{$key}", 'lang');
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

| function name    | Description                                   |
|------------------|-----------------------------------------------|
| env()            | env method `To get environment variable`      |
| server()         | Return instance of `(new Server)` class       |
| to_array()       | `array` Convert value to array                |
| to_object()      | `object` Convert value to object              |
| to_json()        | `string` Convert value to json                |


## Error Dump

| function  | Description       |
|-----------|-------------------|
| dump      | Dump Data         |
| dd        | Dump and Die      |


## Useful Links

- @author Fredrick Peterson (Tame Developers)
- If you love this PHP Library, you can [Buy Tame Developers a coffee](https://www.buymeacoffee.com/tamedevelopers)
