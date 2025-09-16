<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Template File Lines
    |--------------------------------------------------------------------------
    |
    | The following template lines are used during text formatting for various
    | messages that we need to display to the user. You are free to modify
    | these template lines according to your application's requirements.
    |
    */
    
    // Gmail ignores dots and supports plus
    "gmail.com"         => ["strip_plus" => true, "strip_dots" => false],
    "googlemail.com"    => ["strip_plus" => true, "strip_dots" => false],

    // Microsoft (Outlook/Hotmail/Live/MSN) supports plus, but NOT dot collapsing
    "outlook.com"       => ["strip_plus" => true, "strip_dots" => false],
    "hotmail.com"       => ["strip_plus" => true, "strip_dots" => false],
    "live.com"          => ["strip_plus" => true, "strip_dots" => false],
    "msn.com"           => ["strip_plus" => true, "strip_dots" => false],

    // Apple (iCloud/Me/Mac) supports plus, no dot collapsing
    "icloud.com"        => ["strip_plus" => true, "strip_dots" => false],
    "me.com"            => ["strip_plus" => true, "strip_dots" => false],
    "mac.com"           => ["strip_plus" => true, "strip_dots" => false],

    // Fastmail supports plus, no dot collapsing
    "fastmail.com"      => ["strip_plus" => true, "strip_dots" => false],

    // ProtonMail supports plus, no dot collapsing
    "protonmail.com"    => ["strip_plus" => true, "strip_dots" => false],
    "proton.me"         => ["strip_plus" => true, "strip_dots" => false],
    "pm.me"             => ["strip_plus" => true, "strip_dots" => false],

    // Zoho supports plus, no dot collapsing
    "zoho.com"          => ["strip_plus" => true, "strip_dots" => false],
    "zohomail.com"      => ["strip_plus" => true, "strip_dots" => false],

    // Yandex supports plus, no dot collapsing
    "yandex.com"        => ["strip_plus" => true, "strip_dots" => false],
    "yandex.ru"         => ["strip_plus" => true, "strip_dots" => false],

    // GMX and Mail.com support plus, no dot collapsing
    "gmx.com"           => ["strip_plus" => true, "strip_dots" => false],
    "gmx.de"            => ["strip_plus" => true, "strip_dots" => false],
    "mail.com"          => ["strip_plus" => true, "strip_dots" => false],

    // Privacy-focused providers (Mailbox.org, Posteo, Runbox, StartMail) support plus
    "mailbox.org"       => ["strip_plus" => true, "strip_dots" => false],
    "posteo.de"         => ["strip_plus" => true, "strip_dots" => false],
    "runbox.com"        => ["strip_plus" => true, "strip_dots" => false],
    "startmail.com"     => ["strip_plus" => true, "strip_dots" => false]
];