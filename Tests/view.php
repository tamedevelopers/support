<?php 



require_once __DIR__ . '/../vendor/autoload.php';

use Tamedevelopers\Support\View;


$layout = 'tests.layout.home';

// Share global data
tview()->share('appName', 'Laravel-Like Framework');
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
$viewYield = new View('tests.layout.home2', []);
// echo $viewYield->render();


$viewIf = tview('tests.layout.if', [
    'name' => 'Peterson',
    'condition' => true,
]);

echo $viewIf->render();

// dd(
//     $viewYield,
//     'qwwe'
// );
