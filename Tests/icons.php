<?php 

use Tamedevelopers\Support\Time;

require_once __DIR__ . '/../vendor/autoload.php';


$platform   = Tame()->platformIcon('windows');
$payment    = Tame()->paymentIcon('payment-wallet');


dump(
    $platform,
    $payment,
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        svg{
            width: 2rem;
            height: 2rem;
            object-fit: contain;
            
        }
        section{
            display: flex;
            gap: .5rem;
        }
    </style>
</head>
<body>

    <section>
        <div>
            <?php Tame()->include($platform);?>
        </div>
    
        <div>
            <?php Tame()->include(Tame()->platformIcon('ios'));?>
        </div>
    
        <div>
            <?php Tame()->include($payment);?>
        </div>
    </section>

</body>
</html>


