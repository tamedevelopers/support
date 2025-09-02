<?php

use Tamedevelopers\Support\NumberToWords;

require_once __DIR__ . '/../vendor/autoload.php';

// Start HTML output
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<thead>";
echo "<tr>";
echo "<th>Unit</th>";
echo "<th>Formatted Value</th>";
echo "<th>Zeros</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach (NumberToWords::getUnits() as $index => $unit) {
    if ($index === 0) {
        $value = "1";
        $formattedValue = $value;
        $zeros = 0;
    } else {
        $ones = str_repeat("1", 1);
        $thousands = str_repeat("0", $index * 3);
        
        $value = $ones . $thousands;
        $formattedValue = number_format((int) $ones . $thousands);
        $zeros = $index * 3;
    }

    // Display row
    echo "<tr>";
    echo "<td>" . ucfirst($unit) . "</td>";
    echo "<td>" . $formattedValue . "</td>";
    echo "<td>" . $zeros . "</td>";
    echo "</tr>";
}

echo "</table> <br><br>";