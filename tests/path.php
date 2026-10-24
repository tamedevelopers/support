<?php 


require_once __DIR__ . '/../vendor/autoload.php';

$Json = '{"name":"Peterson","age":20,"food":["Rice","Garri","Fish","Calories"]}';

$array = [
    'content' => "<h1>Title First</h1> <br> Hello There i Love You!",
    'destination' => base_path('save.pdf'),
    'output' => 'download'
];

$helper = urlHelper();
$request = TameRequest();

dd(
    'Path: ' . $helper->path(),
    'getBasePath: ' . $request->getBasePath(),
    'directory: ' . directory(),
    'base_path: ' . base_path(),
    'storage_path: ' . storage_path(),
    'app_path' . app_path(),
    'public_path: ' . public_path(),
    'config_path: ' . config_path(),
    'lang_path: ' . lang_path(),
    'database_path: ' . database_path(),
    
    server()->toArray($Json),
    // server()->toJson($array),

    // to_array($Json),
    to_object($Json),
    to_json($array)
);

