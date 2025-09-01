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

return;

// Set shared data using trait method
// View::setShared('company', 'TameDevelopers');

// // Render a view if it exists
// if (View::exists($layout)) {
//     echo View::renderPartial($layout, [
//         'title' => 'Welcome',
//         'user' => 'John Doe',
//     ]);
// }

// Render with errors and old input
$view = new View($layout, [
    // 'title' => 'Dashboard',
]);
// $view->withErrors(['email' => 'Invalid email'])->withOldInput(['email' => 'test@example.com']);



// Render each item in a collection
$users = [
    ['name' => 'Alice'],
    ['name' => 'Bob'],
];
echo View::renderEach('partials/user', $users);

// Use layout and sections
$view = new View($layout, []);
$view->setLayout('main');
$view->section('header', '<h1>Header Section</h1>');
echo $view->yieldSection('header');

// Add global variable
View::addGlobal('theme', 'dark');

// Register a custom directive (example)
View::registerDirective('uppercase', function($value) {
    return strtoupper($value);
});

// Conditionally render a view
if ($showProfile = true) {
    echo View::renderIf($showProfile, 'partials/profile', ['user' => 'Jane']);
}

// Cache a rendered view
$content = View::cache($layout, ['title' => 'Cached Home'], 120);
echo $content;

// SharedDataTrait advanced usage
View::mergeShared(['foo' => 'bar', 'baz' => 'qux']);
View::shareOnce('unique', 'value');
View::shareIf(true, 'conditional', 'yes');
View::lockShared('company');
View::unlockShared('company');
$count = View::sharedCount();
echo "Shared data count: $count\n";

// Get shared data
$shared = View::allShared();
print_r($shared);


$data = tview('tests.layout.if', [
    'name' => 'Peterson',
    'condition' => true,
]);

// echo $data;


dd(
    $data->render()
);