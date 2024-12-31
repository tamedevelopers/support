<?php 


require_once __DIR__ . '/../vendor/autoload.php';

$Json = '{"name":"Peterson","age":20,"food":["Rice","Garri","Fish","Calories"]}';

$array = [
    'content' => "<h1>Title First</h1> <br> Hello There i Love You!",
    'destination' => base_path('save.pdf'),
    'output' => 'download'
];


dd(

    directory(),
    base_path(),
    storage_path(),
    app_path(),
    public_path(),
    config_path(),
    lang_path(),


    
    server()->toArray($Json),
    // to_array($Json),

    // server()->toJson($array),
    to_json($array)
);

