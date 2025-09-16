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
    * [ISO](#iso)
    * [Cents](#Cents)
    * [Value](#value)
    * [toText](#toText)
    * [toNumber](#toNumber)
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
    * [Usage](#str-usage)
    * [phone](#phone)
    * [mask](#mask)
    * [html](#html)
    * [text](#text)
    * [shorten](#shorten)
    * [random](#random)
    * [formatString](#formatString)
    * [formatOnlyString](#formatOnlyString)
    * [encrypt](#encrypt)
    * [decrypt](#decrypt)
    * [bindings](#bindings)
    * [flattenValue](#flattenValue)
    * [exceptArray](#exceptArray)
    * [replaceFirst](#replaceFirst)
    * [replaceLast](#replaceLast)
    * [renameArrayKeys](#renameArrayKeys)
    * [forgetArrayKeys](#forgetArrayKeys)
    * [changeKeyCase](#changeKeyCase)
    * [convertArrayCase](#convertArrayCase)
    * [padLeft](#padLeft)
    * [padRight](#padRight)
    * [words](#words)
    * [ascii](#ascii)
    * [is](#is)
    * [snake](#snake)
    * [camel](#camel)
    * [kebab](#kebab)
    * [title](#title)
    * [studly](#studly)
    * [slugify](#slugify)
    * [slug](#slug)
    * [before](#before)
    * [after](#after)
    * [between](#between)
    * [contains](#contains)
    * [truncate](#truncate)
    * [reverse](#reverse)
    * [count](#count)
    * [countOccurrences](#countOccurrences)
    * [uuid](#uuid)
    * [randomWords](#randomWords)
    * [extension](#extension)
    * [wrap](#wrap)
    * [head](#head)
    * [last](#last)
* [TextSanitizer](#TextSanitizer)
    * [Usage](#sanitizer-usage)
    * [phone](#phone)
* [Country](#country)
    * [Usage](#country-usage)
    * [iso3](#country-iso3)
    * [iso2](#country-iso2)
    * [flagIso3](#country-flagIso3)
    * [flagIso2](#country-flagIso2)
    * [month](#country-month)
    * [week](#country-week)
    * [zone](#country-zone)
    * [captcha](#country-captcha)
* [File](#file)
    * [Usage](#file-usage)
    * [files](#file-files) 
    * [makeDirectory](#file-makedirectory) 
    * [exists](#file-exists) 
    * [get](#file-get)
    * [put](#file-put)
    * [delete](#file-delete) 
    * [copy](#file-copy) 
    * [move](#file-move)
    * [size](#file-size) 
    * [lastModified](#file-lastmodified) 
    * [extension](#file-extension) 
    * [name](#file-name) 
    * [mimeType](#file-mimetype) 
    * [type](#file-type) 
    * [permissions](#file-permissions)
    * [isReadable](#file-isreadable) 
    * [isWritable](#file-iswritable) 
    * [isDirectory](#file-isdirectory) 
    * [isFile](#file-isfile) 
    * [isFileType](#file-isfiletype)
* [Collection](#collection)
    * [Usage](#collection-usage)
    * [has](#collection-has)
    * [count](#collection-count)
    * [all](#collection-all)
    * [isNotEmpty](#collection-isnotempty)
    * [isEmpty](#collection-isempty)
    * [isSame](#collection-issame)
    * [isDuplicate](#collection-isduplicate)
    * [reverse](#collection-reverse)
    * [pad](#collection-pad)
    * [combine](#collection-combine)
    * [collapse](#collection-collapse)
    * [flatten](#collection-flatten)
    * [zip](#collection-zip)
    * [merge](#collection-merge)
    * [only](#collection-only)
    * [except](#collection-except)
    * [chunk](#collection-chunk)
    * [keys](#collection-keys)
    * [values](#collection-values)
    * [filter](#collection-filter)
    * [reject](#collection-reject)
    * [where](#collection-where)
    * [whereIn](#collection-wherein)
    * [whereNotIn](#collection-wherenotin)
    * [whereNull](#collection-wherenull)
    * [whereNotNull](#collection-wherenotnull)
    * [first](#collection-first)
    * [firstWhere](#collection-firstwhere)
    * [last](#collection-last)
    * [contains](#collection-contains)
    * [doesntContain](#collection-doesntcontain)
    * [every](#collection-every)
    * [some](#collection-some)
    * [select](#collection-select)
    * [map](#collection-map)
    * [mapWithKeys](#collection-mapwithkeys)
    * [pluck](#collection-pluck)
    * [pluckDot](#collection-pluckdot)
    * [groupBy](#collection-groupby)
    * [search](#collection-search)
    * [sort](#collection-sort)
    * [sortBy](#collection-sortby)
    * [sortByMany](#collection-sortbymany)
    * [sortByDesc](#collection-sortbydesc)
    * [sortKeys](#collection-sortkeys)
    * [sortKeysDesc](#collection-sortkeysdesc)
    * [keyBy](#collection-keyby)
    * [slice](#collection-slice)
    * [take](#collection-take)
    * [takeUntil](#collection-takeuntil)
    * [skip](#collection-skip)
    * [concat](#collection-concat)
    * [union](#collection-union)
    * [toBase](#collection-tobase)
    * [pipe](#collection-pipe)
    * [crossJoin](#collection-crossjoin)
    * [join](#collection-join)
    * [unique](#collection-unique)
    * [each](#collection-each)
    * [forget](#collection-forget)
    * [changeKeyCase](#collection-changekeycase)
    * [reduce](#collection-reduce)
    * [shuffle](#collection-shuffle)
    * [partition](#collection-partition)
    * [tap](#collection-tap)
    * [chunkWhile](#collection-chunkwhile)
    * [nth](#collection-nth)
    * [paginate](#collection-paginate)
    * [zipWith](#collection-zipwith)
    * [countBy](#collection-countby)
    * [duplicates](#collection-duplicates)
    * [shuffleKeys](#collection-shufflekeys)
    * [average](#collection-average)
    * [avg](#collection-avg)
    * [sum](#collection-sum)
    * [max](#collection-max)
    * [min](#collection-min)
* [Mail](#mail)
    * [config](#config)
    * [to](#to)
    * [subject](#subject)
    * [altBody](#altBody)
    * [body](#body)
    * [cc](#cc)
    * [bcc](#bcc)
    * [replyTo](#replyTo)
    * [attach](#attach)
    * [delete](#delete)
    * [send](#send)
    * [flush](#flush)
    * [obFlush](#obFlush)
    * [convert](#convert)
* [Zip](#zip)
    * [Unzip](#unzip)
    * [Zip Download](#zip-download)
* [PDF](#pdf)
    * [Usage](#pdf-usage)
    * [Read PDF](#read-pdf)
* [Time](#time)
    * [time-usage](#time-usage)
    * [now](#now)
    * [date](#date)
    * [today](#today)
    * [yesterday](#yesterday)
    * [createFromFormat](#createFromFormat)
    * [createFromDateString](#createFromDateString)
    * [timestamp](#timestamp)
    * [toJsTimer](#toJsTimer)
    * [format](#format)
    * [toDateTimeString](#toDateTimeString)
    * [toDateString](#toDateString)
    * [toTimeString](#toTimeString)
    * [diff](#diff)
    * [diffBetween](#diffBetween)
    * [ago](#ago)
    * [range](#range)
    * [addSeconds](#addSeconds)
    * [subSeconds](#subSeconds)
    * [addMinutes](#addMinutes)
    * [subMinutes](#subMinutes)
    * [addHours](#addHours)
    * [subHours](#subHours)
    * [addDays](#addDays)
    * [subDays](#subDays)
    * [addWeeks](#addWeeks)
    * [subWeeks](#subWeeks)
    * [addMonths](#addMonths)
    * [subMonths](#subMonths)
    * [addYears](#addYears)
    * [subYears](#subYears)
    * [greeting](#greeting)
    * [allTimezone](#allTimezone)
    * [setTimeZone](#setTimeZone)
    * [getTimeZone](#getTimeZone)
* [HttpRequest](#HttpRequest)
    * [url](#url)
    * [http](#http)
    * [host](#host)
    * [full](#full)
    * [path](#path)
    * [server](#http-server)
    * [request](#request)
    * [referral](#referral)
* [Cookie](#cookie)
    * [Usage](#cookie-usage)
    * [set](#cookie-set) 
    * [get](#cookie-get) 
    * [forget](#cookie-forget) 
    * [expire](#cookie-expire) 
    * [all](#cookie-all) 
    * [has](#cookie-has)
* [Hash](#hash)
    * [Usage](#hash-usage)
    * [make](#hash-make) 
    * [check](#hash-check)
* [Asset](#Asset)
    * [Asset config](#asset-config)
        * [Asset Cache](#asset-cache)
* [View](#view)
    * [Usage](#view-usage)
    * [Support](#view-support)
    * [Helper tview](#view-helper)
* [Env](#env)
    * [Env Create](#env-create)
    * [Env Load](#env-load)
    * [Env Update](#env-update)
* [Manager](#manager)
    * [Key Management](#key-management)
    * [Helper tmanager](#helper-tmanager)
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
    - It's helper class can be called, using -- `NumberToWords()`

![Sample Units](https://raw.githubusercontent.com/tamedevelopers/support/master/thousand_units.png)

| iso (country iso3)         | cents | number |
|----------------------------|-------|--------|
| `NGA \| GBR \| USA `| `true \| false` | `int\|float\|string` |
| If `iso` is given and found, it'll automatically converts the text into a currency format | If you want the decimals to be translated to text as well. | numeric figures: `299 \| '42,982' \| 3200.98` |


### Iso
- Takes param as `string` and case-insensitive

```php
NumberToWords::iso('nga');
```

### Cents
- Takes param as `boolean`. Default is `false`
    - By default, it doesn't format the decimals `.00` Must be set to true, to format if needed.

```php
NumberToWords::cents(true);
```

### Value
- Takes one param as `int | float | string`
    - If numbers is larger than a trillion `1_000_000_000_000`, then the value must be passed as a string.

```php
NumberToWords::value(1290);
```

### toText
- Convert number to readable words
    - Below, we're using the function helper method

```php
NumberToWords()
    ->iso('TUR')
    ->value('120.953')
    ->cents(true)
    ->toText();

// Output: One hundred and twenty lira, nine hundred and fifty-three kuruş
```

### toNumber
- Convert words to number
    - comma `, ` is used to seperate decimals in words

```php
use Tamedevelopers\Support\NumberToWords;

NumberToWords::value('twelve million three hundred thousand, six hundred and ninety-eight')
        ->cents(true)
        ->toNumber()

// Output: 12300000.698
```

## Tame
- The Core Class of Components
    - It's helper class can be called, using -- `Tame()`

```php
use Tamedevelopers\Support\Tame;
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

```php
Tame()->byteToUnit(6880);

// Output: 7kb
```

### unitToByte
```php
Tame()->unitToByte('24mb');

// Output: 25165824
```

### fileTime
- Returns last edited time of the file as an - `int|false`
```php
Tame()->fileTime(base_path('filepath.php'));
```

### exists
- Checks if a file exists and is not a directory - `bool`
```php
Tame()->exists(base_path('filepath.php'));
// Output: true or false
```

### unlink
- Deletes a file from the server if it exists and does not match the restricted file name - `void`
    - [optional] second param <filename.extension>

```php
Tame()->unlink(base_path('path/to/directory/avatar.png'), 'default.png');
```

### mask  
- Masks characters in a string based on position and length, with support for emails and custom masking characters.

| Params        | Description                                                                                   |
|---------------|-----------------------------------------------------------------------------------------------|
| `$str`        | The string to be masked.                                                                      |
| `$length`     | The number of visible characters. Default is 4.                                              |
| `$position`   | The position to apply the mask: `'left'`, `'center'`, or `'right'` (default is `'right'`).    |
| `$mask`       | The character used for masking (default is `*`).                                              |

#### Example:
```php
Tame()->mask('example@email.com', 4, 'left');
// Output: "exam***@email.com"

Tame()->mask('example@email.com', 4, 'right');
// Output: "e***mple@email.com"

Tame()->mask('shortstring', 4, 'center');
// Output: "sh*******ng"
```

### imageToBase64
- Converts an image file to its Base64 representation. Supports local files and direct URLs - `null|string`

```php
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

```php
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

```php
Tame()->calPercentageBetweenNumbers(100, 80);
```

### formatNumberToNearestThousand
- Formats a number to its nearest thousand, million, billion, or higher as a - `string|float|int`

```php
Tame()->formatNumberToNearestThousand(1500000);
// Output: "1.5m"
```

## Str
- The Core Class For String Manipulations
    - It's helper class can be called, using -- `TameStr()`

### Str Usage

```php
use Tamedevelopers\Support\Str;

// Replace first/last occurrence
Str::replaceFirst('foo', 'bar', 'foofoo');        // 'barfoo'
Str::replaceLast('foo', 'bar', 'foofoo');         // 'foobar'

// Word limiting & ASCII
Str::words('The quick brown fox jumps', 3);       // 'The quick brown...'
Str::ascii('Jürgen');                              // 'Jurgen'

// Padding
Str::padLeft('7', 3, '0');                         // '007'
Str::padRight('7', 3, '0');                        // '700'

// Pattern matching
Str::is('user/*', 'user/42');                      // true
Str::contains('brown', 'The quick brown fox');     // true

// Case/format helpers
Str::snake('Hello World');                         // 'hello_world'
Str::camel('hello world');                         // 'helloWorld'
Str::kebab('Hello World');                         // 'hello-world'
Str::title('hello world');                         // 'Hello World'
Str::studly('hello world');                        // 'HelloWorld'
Str::slug('Hello World!');                         // 'hello-world'
Str::slugify('À bientôt');                         // 'a-bientot'

// Slicing
Str::before('user:42', ':');                       // 'user'
Str::after('user:42', ':');                        // '42'
Str::between('a[core]z', '[', ']');                // 'core'

// Transformations
Str::reverse('abc');                               // 'cba'
Str::truncate('lorem ipsum', 5);                   // 'lo...'

// Randoms
Str::random(8);                                    // 'a1B9...'
Str::uuid();                                       // 'xxxxxxxx-xxxx-4xxx-...'
Str::randomWords(3);                               // 'lor em ip'

// Arrays
Str::exceptArray(['a'=>1,'b'=>2], 'a');            // ['b'=>2]
Str::renameArrayKeys([['id'=>1]], 'id', 'user_id');// [['user_id'=>1]]
Str::forgetArrayKeys(['a'=>1,'b'=>2], 'a');        // ['b'=>2]
Str::bindings(['where'=>[1,2], 'join'=>[3]]);      // [1,2,3]
Str::flattenValue([[1,2],[3]]);                    // [1,2,3]
Str::convertArrayCase(['Name'=>['Age'=>1]], 'lower', 'upper'); // ['name'=>['age'=>1]]

// Security/text helpers
Str::phone('+1 (555) 123-4567');                   // '+15551234567'
Str::mask('1234567890', 4, 'right');               // '******7890'
Str::html('&lt;b&gt;Hi&lt;/b&gt;');                  // '<b>Hi</b>'
Str::text('<b>Hi</b>');                            // 'Hi'
Str::shorten('Long sentence here', 10);            // 'Long sente...'
Str::encrypt('secret');                            // encrypted
Str::decrypt('...');                               // original
```

## Country
- Country data and helpers
    - Class: `Tamedevelopers\Support\Country`
    - It's helper class can be called, using -- `TameCountry()`

### Country Usage

- ISO codes and flags
```php
use Tamedevelopers\Support\Country;

Country::getCountryIso3('name');
Country::getCountryIso2('name');
Country::getCountryFlagIso3('name');
Country::getCountryFlagIso2('name');
```

- Months, Weeks, Time Zones, Captcha Locale
```php
Country::getMonths('short');
Country::getWeeks('mon');
Country::getTimeZone('Europe/London');
Country::getCaptchaLocale('en');
```

## File
- The Core File utilities (read, write, copy, move, info).
    - Class: `Tamedevelopers\Support\Capsule\File`

### File Usage

```php
use Tamedevelopers\Support\Capsule\File;

// Create directory
File::makeDirectory(storage_path('logs'));

// Write & read
File::put(storage_path('logs/app.log'), 'Hello');
$content = File::get(storage_path('logs/app.log')); // 'Hello'

// Info
File::exists(storage_path('logs/app.log')); // true
File::size(storage_path('logs/app.log')); // int bytes
File::extension(storage_path('logs/app.log')); // 'log'
File::lastModified(storage_path('logs/app.log')); // timestamp

// Move/Copy/Delete
File::copy(storage_path('logs/app.log'), storage_path('logs/app_copy.log'));
File::move(storage_path('logs/app_copy.log'), storage_path('logs/app_moved.log'));
File::delete(storage_path('logs/app_moved.log'));

// List files
$files = File::files(storage_path('logs')); // array of SplFileInfo
```

## Collection
- Lightweight collection utilities.
    - Class: `Tamedevelopers\Support\Collections\Collection`
    - It's helper class can be called, using -- `TameCollect() | tcollect()`

### Collection Usage

```php
use Tamedevelopers\Support\Collections\Collection;

$users = new Collection([
    ['id' => 1, 'name' => 'Ada'],
    ['id' => 2, 'name' => 'Ben'],
    ['id' => 3, 'name' => 'Cee'],
]);

$users->isEmpty();       // false
$users->count();         // 3
$users->keys()->all();   // [0,1,2]
$users->values()->all(); // same as original but reindexed

// Keep only specific keys from an associative array
$profile = new Collection(['id'=>1,'name'=>'Ada','role'=>'admin']);
$profile->only('id', 'name')->all();     // ['id'=>1,'name'=>'Ada']
$profile->except('role')->all();         // ['id'=>1,'name'=>'Ada']

// Filtering
$even = (new Collection([1,2,3,4]))->filter(fn($v) => $v % 2 === 0)->all(); // [2,4]

// Merge/Chunk/Reverse
(new Collection([1,2]))->merge([3,4])->all(); // [1,2,3,4]
(new Collection(range(1,6)))->chunk(2)->all(); // [[1,2],[3,4],[5,6]]
(new Collection([1,2,3]))->reverse()->all(); // [3,2,1]
```

## Mail
- The Core Class/Wrapper For `PHPMailer`
    - It's helper class can be called, using -- `TameMail()`

```php
Tamedevelopers\Support\Mail

Mail::to('email@example.com')
        ->subject('subject')
        ->body('<div>Hello Body</div>')
        ->send();
``` 

### to
- Accepts multiple emails as `array|string`

```php
Mail::to('email@example.com')

Mail::to(['email@example.com', 'email2@example.com'])

Mail::to('email@example.com', 'email2@example.com', 'email3@example.com')
```

### attach
- Accepts multiple complex data as attachment as `array|string`

```php
Mail::attach(public_path("image.png"), 'New File Name')

Mail::attach(['path' => public_path("image.png"), 'as' => 'New name'])

Mail::attach([
    ['path' => public_path("image.png"), 'as' => 'New name'],
    ['path' => public_path("image2.zip"), 'as' => 'New name2'],
    ['path' => public_path("image3.jpeng"), 'as' => 'New name2'],
])
```

### subject
- Accepts mandatory `string`

```php
Mail::subject('subject');
```

### body
- Accepts mandatory `string`

```php
Mail::subject('body');
```

### send
- Accepts [optional] closure/function

```php
Mail::to('email@example.com')->send();

Mail::to('email@example.com')->send(function($reponse){

    // $reponse
});
```

### flush
- Accepts mandatory `bool` Default value is false
    - Clear buffers and send email in the background without waiting (But only to be used when using an API/Submitting via Ajax/Fetch or similar method of form submission)
```php
Mail::to('email@example.com')
    ->body('<p>Body Text</p>')
    ->flush(true)
    ->send();
```

## Zip
- Takes two param as `string`
    - [sourcePath] path to zip-directory
    - [destination] path to save zip file

```php
TameZip()->zip('app/Http', 'app.zip')
``` 

### Unzip
- Takes two param as `string`
    - [sourcePath] path of zip-file
    - [destination] path to unzip-directory

```php
TameZip()->unzip('newData.zip', base_path('public/zip'))
```

### Zip Download
- Takes two param as `string | boolean`
    - [fileName] path of zip-file
    - [unlink] Default is `true` unlinks file after download

```php
TameZip()->download('newData.zip')
```

## PDF
- Require package to be installed - `composer require dompdf/dompdf`
    - It's helper class can be called, using -- `TamePDF()`

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


### PDF Usage

```php
Tamedevelopers\Support\PDF

$generate = strtotime('now') . '.pdf';

PDF::create([
    'content'     => '<h1>Hello World!</h1>',
    'destination' => public_path("invoice/{$generate}"),
    'output'      => 'view',
]);
```

### Read PDF
- Takes one param as `string`
    - [path] absolute path to PDF file

```php
TamePDF()->read('invoice100.pdf')

// This will read the PDF to the browser
```

## Time
- Class: `\Tamedevelopers\Support\Time`
    - It's helper class can be called, using -- `TameTime()`

### Time Usage
- Get time date from class

| function name             | Description               |
|---------------------------|---------------------------|
| `sec() \| getSec()`       | Get seconds from time     |
| `min() \| getMin()`       | Get minutes               |
| `hour() \| getHour()`     | Get hour                  |
| `day() \| getDay()`       | Get days                  |
| `week() \| getWeek()`     | Get weeks                 |
| `month() \| getMonth()`   | Get months                |
| `year() \| getYear()`     | Get years                 |
| `time() \| getTime()`     | Get time as int           |

```php
$time = new Time('now', 'Africa/Lagos');

[
    $time4->time(),
    $time4->sec(),
    $time4->min(),
    $time4->hour(),
    $time4->day(),
    $time4->week(),
    $time4->month(),
    $time4->year(),
]
```

### now
- Returns the Time Object with current timestamp of `now`
```php
$time->now()->format()
```

### date
- Accepts one param as (time) `int|string`
```php
$time->date("first day of this month")->toDateTimeString()
```

### today
- Thesame as `now()` with timestamp of `today`

### yesterday
- Thesame as `now()` with timestamp of `yesterday`

```php
$time->today();
$time->yesterday();
```

### createFromFormat
- Accepts two parameter [format, date]
    - only [format] is mandatory and returns the `string stamped formatted date`

```php
$time->createFromFormat('m/d/Y h:ia', '24 Jan 2025 14:00:00');
// 01/25/2025 02:00am
```

### createFromDateString
- Accepts one parameter [date]

```php
$time->createFromDateString('24 Jan 2025 14:00:00');
// 2025-01-24 14:00:00.000000
```

### timestamp
- Accepts two parameter [date, format]
    - only [date] is mandatory and returns formated timestamp

```php
$time->timestamp('24 Jan 2025 14:00:00');
// Output: 2025-01-24 14:00:00
```

### toJsTimer
- Accept one parameter as [date]. Returns formated javascript timestamp

```php
$time->toJsTimer('24 Jan 2025 14:00:00');
$time->jsTimer('24 Jan 2025 14:00:00');
// Output: Jan 24, 2025 14:00:00
```

### format
- Accepts two parameter [format, date] (none is required by default)

```php
$time4->now()->format()
// 2025-09-15 05:07:07
```

### toDateTimeString

```php
$time4->now()->toDateTimeString()
// 2025-09-13 22:00:00
```

### toDateString

```php
$time4->now()->toDateString()
// 2025-09-15
```

### toTimeString

```php
$time4->now()->toTimeString()
// 05:09:01
```

### diff
- Takes one paramater as `mode`. Different between the given date a current time as `now`
    - Return an array if [mode] is not found or value of `mode set`

| mode                                                      |
|-----------------------------------------------------------|
| `year \| month \| hour \| mins \| sec \| days \| weeks`   |

```php
$time->date('last year december')->diff('month');
// Output: 1
```

### diffBetween
- Takes three paramater as `firstDate \| lastDate \| mode`. Thesame as diff.

```php
$time->diffBetween('last year december', 1737752400, 'weeks');
// Output: 4
```

### ago 
- `ago() or timeAgo()`, Takes one paramater as `mode`

| mode                                                                   |
|------------------------------------------------------------------------|
| `full \| short \| duration \| time \| date \| date_time \| time_stamp` |

```php
$time->date('today')->timeAgo('full')
// 17 hours ago

// Output: 
$time->date('today')->ago()
[
    "full" => "4 hours ago"
    "short" => "4h"
    "duration" => 4
    "time" => 1737752400
    "date" => "24 Jan, 2025"
    "date_time" => "24 Jan, 2025 10:01am"
    "time_stamp" => "Jan 24, 2025 10:00:00"
]
```

### range
- Build date range according to value given
    - Accepts (2) params `value and format`

```php
$time->range('0-10', 'D, M j')
// Output: returns class of Tamedevelopers\Support\Capsule\TimeHelper
```

#### get output
- To get the output, we need to call the TimeHelper format method
    - The format() method takes two [optional] param. `start, year` as boolean

```php
$time->range('0-10')->format(true, true)
// Output: Thu, Jan 23 - Tue, Mar 4, 2025

$time->range('0-10')->format()
// Output: Tue, Mar 4
```

### addSeconds
- Can be called using boht [plural|singular] formats. To add more dates with already existing time.

```php
$time4->now()->addMonth(3)->addSeconds(2)->addDays(2)->format()
```

### subSeconds
- Can be called using boht [plural|singular] formats. To subtract dates from already existing time.

```php
$time4->now()->subMonth(3)->subSecond(2)->subDays(2)->format()
```

### allTimezone
```php
Time::allTimezone();
```

### setTimeZone
```php
Time::setTimeZone('Pacific/Pago_Pago');
```

### getTimeZone
```php
Time::getTimeZone();
```

## HttpRequest
- Http Request Helper
    - It's helper class can be called, using -- `TameRequest()|urlHelper()`
    - `urlHelper()` was older method name. We still keep for older project support.


```php
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Process\Http; // same as HttpRequest

$http = TameRequest();

[
    Http::url(),
    HttpRequest::server(),
    HttpRequest::method(),
    $http->full(),
    $http->request(),
    $http->referral(),
    $http->http(),
    $http->host(),
    $http->path(),
]
```

## Cookie
- Class: `\Tamedevelopers\Support\Cookie`
    - It's helper class can be called, using -- `TameCookie()`

### Cookie Usage

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

```php
use Tamedevelopers\Support\Cookie;

Cookie::set('cookie_name', 'value');
// TameCookie()->set('user', '__user');
```

### Get
- Takes param as `string`

```php
Cookie::get('cookie_name');
```

### Forget
- Takes `3 param`
    - Mandatory `$name` param as `string`
    - [optional] `$path` param as `string | null`
    - [optional] `$domain` param as `string | null`

```php
Cookie::forget('cookie_name');
```

### Has
- Takes param as `string`
    - Returns `bool`

```php
if(Cookie::has('cookie_name')){
    // execute code
}
```

## Hash

### Hash Usage
- Password hashing and verify

```php
use Tamedevelopers\Support\Hash;

bcrypt('testPassword');
// or
Hash::make('testPassword');

// $2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6
```

### hash-make
```php
Hash::make('secret');
```

### hash-check
```php
$oldPassword = "$2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6";
Hash::check('testPassword', $oldPassword);
// or native
password_verify('testPassword', $oldPassword);
```

## Asset
- Takes a param as `string` path to asset file
    - Default [dir] is set to `public`
    - It's helper class can be called, using -- `tasset()`

```php
use Tamedevelopers\Support\Asset;

Asset::asset('css/style.css');

// - Returns
// http://domain.com/assets/css/style.css
```

## Asset Config
- Takes three param as `string` 
    - It's helper class can be called, using -- `config_asset()`

| params        | Description                 |
|---------------|-----------------------------|
| base_path     | Path to file                  |
| cache         | By Default is `false`. Tell method to include cache for each file  |
| path_type     | By Default is `false`, which uses absolute path for all files. While `true` will use relative path |
        

```php
use Tamedevelopers\Support\Asset;

Asset::config('public/storage');

// - Returns
// http://domain.com/public/storage/[asset_file]

// config_asset('public');
```

### Asset Cache
- By Default, `cache` is set to `false`
    - You'll see a link representation as `http://domain.com/[path_to_asset_file]?v=111111111`

```php
Asset::config('storage', false);

// - Returns
// http://domain.com/storage/[asset_file]
```

- or -- `using helper method`
```php
// absolute path
config_asset('storage/main.js', true);
// Output: http://domain.com/storage/main.js?v=111111111

// relative url path
config_asset('storage/style.css', true, true);
// Output: /storage/style.css?v=111111111
```

## View

### View Usage
- Basic usage with layout and sections

```php
use Tamedevelopers\Support\View;

// Using a child view that extends a layout
$view = new View('tests.layout.home2', [
    'title' => 'Homepage',
]);

echo $view->render();
```

- Rendering multiple times safely (same instance)
```php
$view = new View('tests.layout.home2', [
    'title' => 'Homepage',
]);

// First render
echo $view->render();

// Second render (fresh render, no duplicated sections)
echo $view->render();
```

- Render and capture as a string
```php
$html = (new View('tests.layout.home2', ['title' => 'Homepage']))->render();
```

### View Support
- Supported extensions for views [only resolves filename]
    - Similar Laravel blade syntax usage

```php
$extensions = [
    '.php',        // Generic / CodeIgniter / CakePHP 4+
    '.blade.php',  // Laravel
    '.twig',       // Symfony/Twig generic
    '.html.twig',  // Symfony typical
];
```

Samples
```blade

@extends('layout.partials.app')


@section('content')
    <h1>Welcome to the Homepage!</h1>
@endsection

@include('layout.partials.footer', ['year' => 2025])
@yield('content')

@foreach($condition as $value)
@endforeach

@if($condition)
    @else
@endif
```

### View Helper
- It's helper class can be called, using -- `tview()`

```php
// set base folder for views
// [optional], but when set - this will be the default path to look for view files.
tview()->base('tests');

// Create a view instance via helper and render
$view = tview('layout.home2', ['title' => 'Homepage']);
echo $view->render();

// One-liner (render via static call)
use Tamedevelopers\Support\View;
echo View::render('layout.home2', ['title' => 'Homepage']);
```

## Env
- By default it use the default root as `.env` path, so mandatory to install vendor in root directory.


### Env Create
- To create an environment `.env` file. Create or ignore if exists

```php
use Tamedevelopers\Support\Env;

Env::createOrIgnore()
```

### Env Load
- To load the environment `.env` file
    - Takes optional param as `string` $path

```php
use Tamedevelopers\Support\Env;

Env::load('path_to_env_folder')

// or 
// Just as the name says. It'll load the `.env` file or fail with status code of 404. An error 
Env::loadOrFail('path_to_env_folder')
```

### Env Update
- Returns `true|false`. Used to update env variables
    - It's helper class can be called, using -- `env_update()`

| Params  |  Description      |
|---------|-------------------|
| key     |  ENV key          |
| value   |  ENV value        |
| quote   |  `true \| false` - Default is true (Allow quotes within value)            |
| space   |  `true \| false`  - Default is false (Allow space between key and value)  |

```php
use Tamedevelopers\Support\Env;

Env::updateENV('DB_PASSWORD', 'newPassword');
env_update('DB_CHARSET', 'utf8', false);
```

## Server
- Return instance of `Server`
    - It's helper class can be called, using -- `server()`


### Get Servers
- Returns assoc arrays of Server
    - `server\|domain`

```php
use Tamedevelopers\Support\Server;

Server::getServers();
// server()->getServers('domain');
```

### Create Custom Config
- With this helper you can be able to create your own custom config by extending the Default Config Method
    - When using this model, make sure every of your php file returns an associative array for the key to work

| Params        |  Description      |
|---------------|-------------------|
| key           |  File array key   |
| default       |  Default value if no data is found from the key       |
| folder        |  Folder to search from and Default folder is `config` |

```php
use Tamedevelopers\Support\Server;

Server::config('tests.lang.email', [], 'tests');
```

- Create our own config to extends the default
```php
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

- or -- `using helpers`
```php
server()->config("en/message.{$key}", "message.{$key}", 'Lang');

server()->config("app.name");
```

## Manager

### Key Management
- The package enforces a valid application key in `.env` as `APP_KEY`.
- If `APP_KEY` is missing, invalid, or manually altered, the application will return HTTP 500 until a new key is generated via the API below.

```php
use Tamedevelopers\Support\Capsule\Manager;

// Generate and persist a new key to .env and the fingerprint store
Manager::regenerate();

// Or ensure env is booted then enforce key (called internally)
Manager::startEnvIFNotStarted();
```

- Valid key format is Laravel-style: `base64:` followed by base64 of 32 random bytes.

### Helper tmanager
- You can use the helper for convenience:

```php
// Generate and persist a new key
tmanager()->regenerate();

// Optionally start env and enforce key
tmanager()->startEnvIFNotStarted();
```

## Autoload Register
- Takes a `string|array` as parameter
    - Register one or more folders containing your PHP files
    - Automatically loads `Files|Classes` in the folder and sub-folders

```php
use Tamedevelopers\Support\AutoloadRegister;

// Single folder
AutoloadRegister::load('folder');

// Multiple folders
AutoloadRegister::load(['folder', 'folder2']);

// Or use the helper
autoload_register('folder');
autoload_register(['folder', 'folder2']);
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
