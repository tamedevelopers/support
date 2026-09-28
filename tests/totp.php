<?php 

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\TOTP;

require_once __DIR__ . '/../vendor/autoload.php';


$totp = new TOTP();

$data = $totp->render(
    'tamedevelopers@gmail.com', 
    'Tame Support',
    public_path("user_2_totp"),
    [
        'pattern' => 'rounded',
        // 'shape' => 'classic',
        // 'outerShape'  => 'rounded',
        'iconPath' => base_path('watermark.png'),
        'showIcon' => true,
    ]
);

echo $data['image'];

$userSecret = 'IGEZARNTOHTSJOPT';
$decrypt    = Str::decrypt('{"k":"273565b11c486764","e":"CZdXqelQPuknnbMUPmNiMA==","s":"WlNocUpjOWkrMStDd3cyTXlab3dBZFpvV2NmU2xDM0lMQUNNdHNjRHBYVT0="}');
$liveOtp    = 738830;

dd(
    // $data,
    $decrypt,
    $totp->verify($userSecret, $liveOtp),
);

// manual generating
$userSecret = $totp->generateSecret();
$otpUri = $totp->getProvisioningUri(
    secret: $userSecret,
    accountName: 'User-OwnerName',
    issuer: 'Issuer Name'
);

dd($otpUri);