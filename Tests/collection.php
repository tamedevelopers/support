<?php 

use Tamedevelopers\Support\Collections\Collection;

require_once __DIR__ . '/../vendor/autoload.php';



$data = [
    ['id' => 1, 'name' => 'John', 'age' => 28],
    ['id' => 2, 'name' => 'Jane', 'age' => 22],
    ['id' => 3, 'name' => 'Doe', 'age' => 31],
    ['id' => 4, 'name' => 'Smith', 'age' => 29],
    ['id' => 5, 'name' => 'Emily', 'age' => 25],
];

$language = [
    'en' => [
        'lang_name' => 'English',
        'flag' => 'us',
        'locale_iso' => 'en',
        'locale' => 'en',
        'locale_allow' => 'true'
    ],
    'cn' => [
        'lang_name' => '中文',
        'flag' => 'hk',
        'locale_iso' => 'cn',
        'locale' => 'zh-Hant',
        'locale_allow' => 'true'
    ]
];


dump(
    tcollect($language)
        ->filter(fn($value) => $value['locale_allow'] == 'true')
        ->mapWithKeys(fn ($value) => [
            $value['locale_iso'] => [
                'name'   => $value['lang_name'],
                'flag'   => $value['flag'],
                'iso'    => $value['locale_iso'],
                'locale' => $value['locale'],
            ]
        ])
        ->forget(['name', 'isos'])
        ->toArray(),
);



// Create a new collection instance
$collection = TameCollect($data);

// getIterator()
$iterator = $collection->getIterator();
foreach ($iterator as $item) {
    // Process each item

    dd(
        $item
    );
}


// has()
$hasId2 = $collection->has(1); // true

// count()
$count = $collection->count(); // 5

// all()
$allItems = $collection->all(); // All items in the collection

// first()
$firstItem = $collection->first(); // ['id' => 1, 'name' => 'John', 'age' => 28]

// last()
$lastItem = $collection->last(); // ['id' => 5, 'name' => 'Emily', 'age' => 25]

// isNotEmpty()
$isNotEmpty = $collection->isNotEmpty(); // true

// isEmpty()
$isEmpty = $collection->isEmpty(); // false

// filter()
$filtered = $collection->filter(fn($item) => $item['age'] > 25); // Filter items with age > 25

// map()
$mapped = $collection->map(fn($item) => $item['name']); // ['John', 'Jane', 'Doe', 'Smith', 'Emily']

// reduce()
$sumAge = $collection->reduce(fn($carry, $item) => $carry + $item['age'], 0); // Sum of ages

// reverse()
$reversed = $collection->reverse(); // Reverse the collection

// pad()
$padded = $collection->pad(7, ['id' => 0, 'name' => 'Unknown', 'age' => 0]); // Pad with default values

// combine()
$keys = ['first', 'second', 'third', 'fourth', 'fifth'];
$combined = $collection->combine($keys); // Combine with new keys

// collapse()
$multiDimensional = new Collection([
    ['a' => 1, 'b' => 2],
    ['c' => 3, 'd' => 4],
]);
$collapsed = $multiDimensional->collapse(); // Flatten nested collections

// flatten()
$nested = new Collection([
    [1, 2], [3, 4], [5, 6], ['Peterson', 39, ['name' => 'Fredrick']]
]);

dump(
    'flatten',
    $nested->flatten(),
    // [1, 2, 3, 4, 5, 6, 'Peterson', 39, 'Fredrick']
);

// zip()
$additionalArray = [10, 20, 30, 40, 50];
dump(
    'zip',
    $collection->zip($additionalArray)
    // Combine items with another array
);

// merge()
dump(
    'merge',
    $collection->merge([['id' => 6, 'name' => 'Sarah', 'age' => 30]])
);

// chunk()
$chunked = $collection->chunk(2); // Split into chunks of size 2

// keys()
$keys = $collection->keys(); // ['id', 'name', 'age']

// values()
$values = $collection->values(); // [1, 2, 3, 4, 5]

// contains()
$containsAge28 = $collection->contains(['age' => 28]); // true

// doesntContain()
$doesNotContain = $collection->doesntContain('name', 'John'); // false

// pluck()
dump(
    'pluck',
    $collection->pluck(['name', 'age'])
    // ['John', 'Jane', 'Doe', 'Smith', 'Emily']
);

// select()
dump(
    'select[name, age] and pluck(age)',
    $collection->select(['name', 'age'])->pluck('age')
);

// search()
dump(
    'search',
    $collection->search('Jane')
     // 1
);

// sort()
$sortedByName = $collection->sort(); // Sort by values (default sorting)

// sortBy()
$sortedByAge = $collection->sortBy('age'); // Sort by age

// sortByMany() 
dump(
    'sortByMany',
    $collection->sortByMany(['age' => SORT_DESC, 'name' => SORT_ASC])
    // Sort by age descending, then name ascending
);

// unique()
dump(
    'unique',
    $collection->unique()
    // Get unique items
);
