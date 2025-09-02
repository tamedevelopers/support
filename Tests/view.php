<?php 



require_once __DIR__ . '/../vendor/autoload.php';

use Tamedevelopers\Support\View;


$layout = 'layout.home';

// set base folder for views
// optional, but when set - this will be the default path to look for view files.
(new View)->base('tests');

tview()->exists('header');

// Share global data
(new View)->share('appName', 'Laravel-Like Framework');
tview()->share('header', [
    'meta' => '<meta charset="UTF-8">',
    'title' => ':: Home Page Title',
    'year' => date('Y'),
]);

$view = new View($layout, [
    'title' => 'Dashboard',
]);
// echo $view->render();

// Use layout and sections
$viewYield = new View('layout.home2', []);
echo $viewYield->render();


dd(
    $viewYield,
    $viewYield->render(),
    'qwwe'
);

// $viewIf = tview('layout.if', [
//     'name' => 'Peterson',
//     'condition' => true,
// ]);

// echo $viewIf->render();
