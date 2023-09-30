<?php 

use Tamedevelopers\Support\Translator;

require_once __DIR__ . '/../vendor/autoload.php';


/**
 * Custom Language Handler
 *
 * @param  string $key
 * @return mixed
 */
function __lang($key){
    return Translator::trans(
        "message.{$key}", 
        Translator::getLocale()
    );
}

/**
 * Custom Configuration Handler
 *
 * @param  mixed $key
 * @param  mixed $default
 * @return mixed
 */
function configuration($key, $default = null){

    // since the config only takes the filename follow by dot(.) and keyname
    // then we can manually include additional folder-name followed by / to indicate that it's a folder
    // then message.key_name
    // To make this Laravel kind of language, we can add the default value to be returned as the key

    return config("configuration/{$key}", $default, 'tests');
}


dd(
    __('message.name'),


    __('message.forgot_password'),

    __lang('confirm_password'),

    // configuration('banners'),

);

