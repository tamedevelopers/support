<?php 



require_once __DIR__ . '/../vendor/autoload.php';


$data = tview('tests.layout.if', [
    'name' => 'Peterson',
    'condition' => true,
]);

// echo $data;


dd(
    $data->render()
);