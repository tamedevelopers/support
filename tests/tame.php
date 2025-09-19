<?php 

use Tamedevelopers\Support\Tame;

require_once __DIR__ . '/../vendor/autoload.php';


// Tame()->HeadersSent();

$volume = [
    1 => [
        'length' => 20,
        'width' => 10,
        'height' => 40,
    ],
    2 => [
        'length' => 120,
        'width' => 87,
        'height' => 450,
    ],
];

$VolumeWeight       = Tame()->calculateVolumeWeight($volume[1]['length'], $volume[1]['width'], $volume[1]['height'], false, 0.5);
$CubicMeterWeight   = Tame()->calculateCubicMeterWeight($volume[2]['length'], $volume[2]['width'], $volume[2]['height'], false, 0.1);


// $emailChecker = Tame()->emailValidator('tamedev@gmail.com');

$emails = [
    // your original list (12)
    'supportw@axdbfs.com',
    'tamedevelopers@gmail.com',
    'supportrsd@lhkexpress.com',
    'support@liftukrainianup.com',
    'support@saltonmining.com',
    'info@tamedevelopers.com',
    'support@115mail.net',
    'support@0-attorney.com',
    'support@10mail.xyz',
    'support@130gmail.com',
    'support@10launcheds.com',
    'support@11852dbmobbil.emlhub.com',

    // common providers / likely resolvable (10)
    'test1@gmail.com',
    'hello@yahoo.com',
    'check@outlook.com',
    'demo@protonmail.com',
    'info@icloud.com',
    'support@hotmail.com',
    'admin@zoho.com',
    'contact@mail.com',
    'noreply@aol.com',
    'test@tutanota.com',

    // bogus / non-resolving examples (20)
    'support@nosuchdomain123456.com',
    'fake@notrealmail.zzz',
    'user@expired-example.org',
    'noreply@invalid-mx-domain.net',
    'mail@unconfigured12345.io',
    'test@deadserverexample.com',
    'admin@nullmxexample.com',
    'info@0-nonexistent-domain.xyz',
    'support@bogusmail999999.org',
    'hello@thisdomaindoesnotexist.test',

    // mixed additional real / well-known (12)
    'contact@github.com',
    'security@google.com',
    'postmaster@yahoo.co.uk',
    'info@amazon.com',
    'no-reply@facebook.com',
    'support@twitter.com',
    'help@linkedin.com',
    'admin@microsoft.com',
    'mailbox@office365.com',
    'team@slack.com',
    'devnull@example.com',
    'bounce@localhost',

    // more test / reserved / local (10)
    'user@example.invalid',
    'test@localhost',
    'webmaster@domain.local',
    'info@mytestdomain.dev',
    'hello@sampledomain.xyz',
    'contact@temporary-mail.org',
    'support@tempmail.net',
    'info@disposablemail.com',
    'test@throwawaymail.com',
    'qa@staging.example.com',

    // enterprise / intranet style (10)
    'sysadmin@enterprise.corp',
    'ops@intranet.local',
    'mailer-daemon@lists.example.com',
    'bounce@mailer.example',
    'postmaster@subdomain.example.com',
    'sales@onlineshop.example',
    'orders@shop-example.com',
    'billing@finance.example',
    'noreply@notifications.example',
    'subscribe@newsletter.example',

    // business / startup style (12)
    'info@company-example.org',
    'contact@business-example.co',
    'hello@startup-example.io',
    'team@opensource.org',
    'support@wordpress.org',
    'info@drupal.org',
    'admin@joomla.org',
    'test@invalid-domain-xyz-123.com',
    'demo@nonexistent-subdomain.example',
    'user@thisshouldnotexist.tld',
    'fakeuser@madeup-domain.zz',
    'sample@no-mx-record.test',

    // probing / scanning style (14)
    'ping@unresolved-domain.example',
    'probe@host-without-mx.com',
    'check@mx-missing-domain.org',
    'scan@null-record-domain.xyz',
    'try@parked-domain-example.com',
    'mail@temporary-example-mail.biz',
    'post@old-domain-archive.org',
    'contact@company12345.net',
    'support@alpha-beta-gamma.io',
    'info@server-down-example.com',
    'admin@backup-server.org',
    'noreply@mailer2025.example',
    'test.account@exampleservice.com',
    'user.service@servicexyz.net',

    // monitoring / ops / edge cases (12)
    'helpdesk@enterprise-example.com',
    'reporting@analytics.example',
    'alerts@monitoring.example',
    'security@infosec.example',
    'abuse@isp-example.net',
    'contact@legal-example.org',
    'info@research-lab.edu',
    'webmaster@college-example.edu',
    'support@nonprofit-example.org',
    'mail@edgecase-domain.test',
    'probe2@nonexistent-12345.test',
    'fake2@veryfake-domain-000.com'
];


dd(
    // $emailChecker,
    // Tame::emailPing('supportw@axdbfs.com'),
    // Tame::deepEmailPing('info@tamedevelopers.com'),
    Tame::batchDeepEmailPing($emails),
);
exit();

dd(
    [
      Tame::urlExist('google.com'),
      Tame::isInternetAvailable(),
    ],
    [
        Tame()->countDivisibleNumbers(100, 680),
        Tame()->byteToUnit(6880),
        Tame()->sizeToBytes('24mb'),
        Tame()->fileTime('tests/tame.php')
    ],

    'br',
    $VolumeWeight,
    $CubicMeterWeight,

    [
        Tame()->getBetweenBoxLengthAndWeightInKg(20, 10, 40, $VolumeWeight, true, 0.5),
        Tame()->getBetweenBoxLengthAndWeightInCMB(20, 10, 40, $CubicMeterWeight),
        Tame()->kgToGrams(2.7),
        Tame()->gramsToKg(2701.0),
        Tame()->calPercentageBetweenNumbers(2701.0, 4320),
        Tame()->formatNumberToNearestThousand(300196366636753)
    ],

    
); 