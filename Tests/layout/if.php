
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    @if ($condition)
        Condition is {{ $condition }}
        @else
        Condition `Else` is {{ $condition }}
    @endif

    <br>
    {{ $name }}
</body>
</html>



