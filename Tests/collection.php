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



// Create a new collection instance
$collection = TameCollect($data);

// getIterator()
$iterator = $collection->getIterator();
foreach ($iterator as $item) {
    // Process each item
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
    $nested->flatten(),
    // [1, 2, 3, 4, 5, 6, 'Peterson', 39, 'Fredrick']
);

// zip()
$additionalArray = [10, 20, 30, 40, 50];
dump(
    $collection->zip($additionalArray)
    // Combine items with another array
);

// merge()
dump(
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
    $collection->pluck('name', 'age')
    // ['John', 'Jane', 'Doe', 'Smith', 'Emily']
);

// select()
dump(
    $collection->select(['name', 'age'])->pluck('age')
);

// search()
dump(
    $collection->search('Jane')
     // 1
);

// sort()
$sortedByName = $collection->sort(); // Sort by values (default sorting)

// sortBy()
$sortedByAge = $collection->sortBy('age'); // Sort by age

// sortByMany() 
dump(
    $collection->sortByMany(['age' => SORT_DESC, 'name' => SORT_ASC])
    // Sort by age descending, then name ascending
);

// unique()
dump(
    $collection->unique()
    // Get unique items
);
