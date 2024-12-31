<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\NumberToWords;


trait NumberToWordsTraits
{

    /**
     * Units
     * Can be able to convert numbers unto quintillion
     *
     * @var array
     */
    static private $units = [
        '',
        'thousand',
        'million',
        'billion',
        'trillion',
        'quadrillion',
        'quintillion',
        'sextillion',
        'septillion',
        'octillion',
        'nonillion',
        'decillion',
        'undecillion',
        'duodecillion',
        'tredecillion',
        'quattuordecillion',
        'quindecillion',
        'sexdecillion',
        'septendecillion',
        'octodecillion',
        'novemdecillion',
        'vigintillion',
    ];

    /**
     * Words
     *
     * @var array
     */
    static private $words = [
        "",
        "one",
        "two",
        "three",
        "four", 
        "five",
        "six",
        "seven",
        "eight",
        "nine", 
        "ten",
        "eleven",
        "twelve",
        "thirteen",
        "fourteen",
        "fifteen",
        "sixteen",
        "seventeen",
        "eighteen",
        "nineteen"
    ];

    /**
     * Tens
     *
     * @var array
     */
    static private $tens = [
        "",
        "",
        "twenty",
        "thirty",
        "forty",
        "fifty",
        "sixty",
        "seventy",
        "eighty",
        "ninety"
    ];
    
    /**
     * numberMap
     *
     * @var array
     */
    static private $numberMap = [
        'zero' => 0, 
        'one' => 1, 
        'two' => 2, 
        'three' => 3, 
        'four' => 4,
        'five' => 5, 
        'six' => 6, 
        'seven' => 7, 
        'eight' => 8, 
        'nine' => 9,
        'ten' => 10, 
        'eleven' => 11, 
        'twelve' => 12, 
        'thirteen' => 13, 
        'fourteen' => 14,
        'fifteen' => 15, 
        'sixteen' => 16, 
        'seventeen' => 17, 
        'eighteen' => 18, 
        'nineteen' => 19,
        'twenty' => 20, 
        'thirty' => 30, 
        'forty' => 40, 
        'fifty' => 50,
        'sixty' => 60, 
        'seventy' => 70, 
        'eighty' => 80, 
        'ninety' => 90
    ];
    
    /**
     * scaleMap
     *
     * @var array
     */
    static private $scaleMap = [
        'hundred' => 100, 
        'thousand' => 1000, 
        'million' => 1000000, 
        'billion' => 1000000000, 
        'trillion' => 1000000000000,
        'quadrillion' => 1000000000000000, 
        'quintillion' => 1000000000000000000,
        'sextillion' => 1000000000000000000000, 
        'septillion' => 1000000000000000000000000,
        'octillion' => 1000000000000000000000000000, 
        'nonillion' => 1000000000000000000000000000000,
        'decillion' => 1000000000000000000000000000000000, 
        'undecillion' => 1000000000000000000000000000000000000,
        'duodecillion' => 1000000000000000000000000000000000000000, 
        'tredecillion' => 1000000000000000000000000000000000000000000,
        'quattuordecillion' => 1000000000000000000000000000000000000000000000, 
        'quindecillion' => 1000000000000000000000000000000000000000000000000,
        'sexdecillion' => 1000000000000000000000000000000000000000000000000000, 
        'septendecillion' => 1000000000000000000000000000000000000000000000000000000,
        'octodecillion' => 1000000000000000000000000000000000000000000000000000000000, 
        'novemdecillion' => 1000000000000000000000000000000000000000000000000000000000000,
        'vigintillion' => 1000000000000000000000000000000000000000000000000000000000000000
    ];

    /**
     * isWordsInstance
     *
     * @return bool
     */
    static protected function isWordsInstance()
    {
        return self::$staticData instanceof NumberToWords;
    }

    /**
      * All currency 
      * - Country <iso-3></iso-3>
      *
      * @return array
      */
    static public function CurrencyNames()
    {
        return [
            'AFG' => [
                'name' => 'Afghani',
                'cents' => 'puls (پول)',
            ],
            'ALB' => [
                'name' => 'Lek',
                'cents' => 'qindarkë',
            ],
            'DZA' => [
                'name' => 'Dinar',
                'cents' => 'centimes',
            ],
            'AND' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'AGO' => [
                'name' => 'Kwanza',
                'cents' => 'cêntimos',
            ],
            'AIA' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'ATG' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'ARG' => [
                'name' => 'Peso',
                'cents' => 'cents',
            ],
            'ARM' => [
                'name' => 'Dram',
                'cents' => 'luma (լումա)',
            ],
            'AUS' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'AUT' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'AZE' => [
                'name' => 'Manat',
                'cents' => 'qəpik (qəpiklər)',
            ],
            'BHS' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'BHR' => [
                'name' => 'Dinar',
                'cents' => 'fils',
            ],
            'BGD' => [
                'name' => 'Taka',
                'cents' => 'poisha',
            ],
            'BRB' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'BLR' => [
                'name' => 'Ruble',
                'cents' => 'cents',
            ],
            'BEL' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'BLZ' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'BEN' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'BTN' => [
                'name' => 'Ngultrum',
                'cents' => 'chhertum (ཕྱེད་ཏམ)',
            ],
            'BOL' => [
                'name' => 'Boliviano',
                'cents' => 'cents',
            ],
            'BIH' => [
                'name' => 'Mark',
                'cents' => 'feninga',
            ],
            'BWA' => [
                'name' => 'Pula',
                'cents' => 'thebe',
            ],
            'BRA' => [
                'name' => 'Real',
                'cents' => 'cents',
            ],
            'BRN' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'BGR' => [
                'name' => 'Lev',
                'cents' => 'cents',
            ],
            'BFA' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'BDI' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'CPV' => [
                'name' => 'Escudo',
                'cents' => 'Centavos',
            ],
            'KHM' => [
                'name' => 'Riel',
                'cents' => 'Sen',
            ],
            'CMR' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'CAN' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'CAF' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'TCD' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'CHL' => [
                'name' => 'Peso',
                'cents' => 'cents',
            ],
            'CHN' => [
                'name' => 'Yuan',
                'cents' => 'fen',
            ],
            'COL' => [
                'name' => 'Peso',
                'cents' => 'cents',
            ],
            'COM' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'COG' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'CRI' => [
                'name' => 'Colón',
                'cents' => 'Céntimos',
            ],
            'CIV' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'HRV' => [
                'name' => 'Kuna',
                'cents' => 'Lipa',
            ],
            'CUB' => [
                'name' => 'Peso',
                'cents' => 'cents',
            ],
            'CYP' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'CZE' => [
                'name' => 'Koruna',
                'cents' => 'haléřů',
            ],
            'DNK' => [
                'name' => 'Krone',
                'cents' => 'øre',
            ],
            'DJI' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'DMA' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'DOM' => [
                'name' => 'Peso',
                'cents' => 'cents',
            ],
            'ECU' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'EGY' => [
                'name' => 'Pound',
                'cents' => 'piastres',
            ],
            'SLV' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'GNQ' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'ERI' => [
                'name' => 'Nakfa',
                'cents' => 'cents',
            ],
            'EST' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'SWZ' => [
                'name' => 'Lilangeni',
                'cents' => 'cents',
            ],
            'ETH' => [
                'name' => 'Birr',
                'cents' => 'cents',
            ],
            'FJI' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'FIN' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'FRA' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'GAB' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'GMB' => [
                'name' => 'Dalasi',
                'cents' => 'Butut',
            ],
            'GEO' => [
                'name' => 'Lari',
                'cents' => 'Tetri',
            ],
            'DEU' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'GHA' => [
                'name' => 'Cedi',
                'cents' => 'Pesewas',
            ],
            'GRC' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'GRD' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'GTM' => [
                'name' => 'Quetzal',
                'cents' => 'Centavo',
            ],
            'GIN' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'GNB' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'GUY' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'HTI' => [
                'name' => 'Gourde',
                'cents' => 'centime',
            ],
            'HND' => [
                'name' => 'Lempira',
                'cents' => 'centavo',
            ],
            'HKG' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'HUN' => [
                'name' => 'Forint',
                'cents' => 'fillér',
            ], 
            'ISL' => [
                'name' => 'Króna',
                'cents' => 'aurar',
            ],
            'IND' => [
                'name' => 'Rupee',
                'cents' => 'paise',
            ],
            'IDN' => [
                'name' => 'Rupiah',
                'cents' => 'sen',
            ],
            'IRN' => [
                'name' => 'Rial',
                'cents' => 'rial',
            ],
            'IRQ' => [
                'name' => 'Dinar',
                'cents' => 'fils',
            ],
            'IRL' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'ISR' => [
                'name' => 'New Shekel',
                'cents' => 'cents',
            ],
            'ITA' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'JAM' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ], 
            'JPN' => [
                'name' => 'Yen',
                'cents' => 'sen',
            ],
            'JOR' => [
                'name' => 'Dinar',
                'cents' => 'piastres',
            ],
            'KAZ' => [
                'name' => 'Tenge',
                'cents' => 'tyn',
            ],
            'KEN' => [
                'name' => 'Shilling',
                'cents' => 'cents',
            ],
            'KIR' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'KWT' => [
                'name' => 'Dinar',
                'cents' => 'fils',
            ],
            'KGZ' => [
                'name' => 'Som',
                'cents' => 'tyiyn',
            ],
            'LAO' => [
                'name' => 'Kip',
                'cents' => 'att',
            ],
            'LVA' => [
                'name' => 'Euro',
                'cents' => 'cents',
            ],
            'LBN' => [
                'name' => 'Pound',
                'cents' => 'Piastre',
            ],
            'LSO' => [
                'name' => 'Lesotho',
                'cents' => 'Sente',
            ],
            'LBR' => [
                'name' => 'Liberia',
                'cents' => 'Cent',
            ],
            'LBY' => [
                'name' => 'Dinar',
                'cents' => 'dirham',
            ],
            'LIE' => [
                'name' => 'Franc',
                'cents' => 'Rappen',
            ],
            'LTU' => [
                'name' => 'Lithuania',
                'cents' => 'Centas',
            ],
            'LUX' => [
                'name' => 'Luxembourg',
                'cents' => 'Euro',
            ],
            'MKD' => [
                'name' => 'Macedonia',
                'cents' => 'Denar',
            ],
            'MDG' => [
                'name' => 'Malagasy Ariary',
                'cents' => 'Iraimbilanja',
            ],
            'MWI' => [
                'name' => 'Malawi',
                'cents' => 'Tambala',
            ],
            'MYS' => [
                'name' => 'Malaysia',
                'cents' => 'Sen',
            ],
            'MDV' => [
                'name' => 'Maldives Rufiyaa',
                'cents' => 'Laari',
            ],
            'MLI' => [
                'name' => 'Franc',
                'cents' => 'Centime',
            ],
            'MLT' => [
                'name' => 'Malta',
                'cents' => 'Euro',
            ],
            'MHL' => [
                'name' => 'Marshall Islands',
                'cents' => 'Dollar',
            ],
            'MRT' => [
                'name' => 'Mauritanian Ouguiya',
                'cents' => 'Khoums',
            ],
            'MUS' => [
                'name' => 'Mauritius Rupee',
                'cents' => 'Cent',
            ],
            'MEX' => [
                'name' => 'Mexico Peso',
                'cents' => 'Centavo',
            ],
            'FSM' => [
                'name' => 'Micronesia',
                'cents' => 'Dollar',
            ],
            'MDA' => [
                'name' => 'Moldovan Leu',
                'cents' => 'Ban',
            ],
            'MCO' => [
                'name' => 'Monaco',
                'cents' => 'Euro',
            ],
            'MNG' => [
                'name' => 'Mongolian Tugrik',
                'cents' => 'Möngö',
            ],
            'MNE' => [
                'name' => 'Montenegro',
                'cents' => 'Euro',
            ],
            'MAR' => [
                'name' => 'Moroccan Dirham',
                'cents' => 'Centime',
            ],
            'MOZ' => [
                'name' => 'Mozambican Metical',
                'cents' => 'Centavo',
            ],
            'MMR' => [
                'name' => 'Myanmar Kyat',
                'cents' => 'Pya',
            ],
            'NAM' => [
                'name' => 'Namibian Dollar',
                'cents' => 'Cent',
            ],
            'NRU' => [
                'name' => 'Nauruan Dollar',
                'cents' => 'Cent',
            ],
            'NPL' => [
                'name' => 'Nepalese Rupee',
                'cents' => 'Paisa',
            ],
            'NLD' => [
                'name' => 'Netherlands',
                'cents' => 'Euro',
            ],
            'NZL' => [
                'name' => 'New Zealand Dollar',
                'cents' => 'Cent',
            ],
            'NIC' => [
                'name' => 'Nicaragua Córdoba',
                'cents' => 'Centavo',
            ],
            'NER' => [
                'name' => 'Niger Franc',
                'cents' => 'centimes',
            ],
            'NGA' => [
                'name' => 'Naira',
                'cents' => 'Kobo',
            ],
            'PRK' => [
                'name' => 'North Korean Won',
                'cents' => 'Chon',
            ],
            'NOR' => [
                'name' => 'Norwegian Krone',
                'cents' => 'Øre',
            ],
            'OMN' => [
                'name' => 'Omani Rial',
                'cents' => 'Baisa',
            ],
            'PAK' => [
                'name' => 'Pakistani Rupee',
                'cents' => 'Paisa',
            ],
            'PLW' => [
                'name' => 'Palauan Dollar',
                'cents' => 'Cent',
            ],
            'PAN' => [
                'name' => 'Panamanian Dollar',
                'cents' => 'Centésimo',
            ],
            'PNG' => [
                'name' => 'Papua New Guinean Kina',
                'cents' => 'Toea',
            ],
            'PRY' => [
                'name' => 'Paraguayan Guarani',
                'cents' => 'Céntimo',
            ],
            'PER' => [
                'name' => 'Peruvian Nuevo Sol',
                'cents' => 'Céntimo',
            ],
            'PHL' => [
                'name' => 'Philippine Peso',
                'cents' => 'Sentimo',
            ],
            'POL' => [
                'name' => 'Polish Złoty',
                'cents' => 'Grosz',
            ],
            'PRT' => [
                'name' => 'Euro',
                'cents' => 'Centavo',
            ],
            'QAT' => [
                'name' => 'Rial',
                'cents' => 'Dirham',
            ],
            'KOR' => [
                'name' => 'Won',
                'cents' => 'Jeon',
            ],
            'ROU' => [
                'name' => 'Leu',
                'cents' => 'Bani',
            ],
            'RUS' => [
                'name' => 'Ruble',
                'cents' => 'Kopek',
            ],
            'RWA' => [
                'name' => 'Franc',
                'cents' => 'centimes',
            ],
            'WSM' => [
                'name' => 'Tala',
                'cents' => 'Sene',
            ],
            'SMR' => [
                'name' => 'San Marino Euro',
                'cents' => 'Centesimo',
            ],
            'STP' => [
                'name' => 'São Tomé and Príncipe Dobra',
                'cents' => 'Cêntimo',
            ],
            'SAU' => [
                'name' => 'Saudi Riyal',
                'cents' => 'Halala',
            ],
            'SEN' => [
                'name' => 'Senegalese Franc',
                'cents' => 'Centime',
            ],
            'SRB' => [
                'name' => 'Serbian Dinar',
                'cents' => 'Para',
            ],
            'SYC' => [
                'name' => 'Seychellois Rupee',
                'cents' => 'Cent',
            ],
            'SLE' => [
                'name' => 'Sierra Leone Leone',
                'cents' => 'Cent',
            ],
            'SGP' => [
                'name' => 'Singapore Dollar',
                'cents' => 'Cent',
            ],
            'SVK' => [
                'name' => 'Slovak Euro',
                'cents' => 'Cent',
            ],
            'SVN' => [
                'name' => 'Slovenian Euro',
                'cents' => 'Cent',
            ],
            'SLB' => [
                'name' => 'Solomon Islands Dollar',
                'cents' => 'Cent',
            ],
            'SOM' => [
                'name' => 'Somali Shilling',
                'cents' => 'Centesimi',
            ],
            'ZAF' => [
                'name' => 'South African Rand',
                'cents' => 'Cent',
            ],
            'SSD' => [
                'name' => 'South Sudanese Pound',
                'cents' => 'Piaster',
            ],
            'ESP' => [
                'name' => 'Spanish Euro',
                'cents' => 'Cent',
            ],
            'LKA' => [
                'name' => 'Sri Lankan Rupee',
                'cents' => 'Cent',
            ],
            'SDN' => [
                'name' => 'Sudanese Pound',
                'cents' => 'Piastre',
            ],
            'SUR' => [
                'name' => 'Surinamese Dollar',
                'cents' => 'Cent',
            ],
            'SWZ' => [
                'name' => 'Swazi Lilangeni',
                'cents' => 'Cent',
            ],
            'SWE' => [
                'name' => 'Swedish Krona',
                'cents' => 'Öre',
            ],
            'CHE' => [
                'name' => 'Swiss Franc',
                'cents' => 'Rappen',
            ],
            'SYR' => [
                'name' => 'Syrian Pound',
                'cents' => 'Piastre',
            ],
            'TJK' => [
                'name' => 'Tajikistani Somoni',
                'cents' => 'Diram',
            ],
            'TZA' => [
                'name' => 'Shilling',
                'cents' => 'Cent',
            ],
            'THA' => [
                'name' => 'Thai Baht',
                'cents' => 'Satang',
            ],
            'TGO' => [
                'name' => 'Franc',
                'cents' => 'Centime',
            ],
            'TON' => [
                'name' => 'Tongan Paʻanga',
                'cents' => 'Seniti',
            ],
            'TTO' => [
                'name' => 'Dollar',
                'cents' => 'Cent',
            ],
            'TUN' => [
                'name' => 'Dinar',
                'cents' => 'Millime',
            ],
            'TUR' => [
                'name' => 'Lira',
                'cents' => 'Kuruş',
            ],
            'TKM' => [
                'name' => 'Manat',
                'cents' => 'Tenge',
            ],
            'TUV' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'UGA' => [
                'name' => 'Shilling',
                'cents' => 'cents',
            ],
            'UKR' => [
                'name' => 'Hryvnia',
                'cents' => 'Kopiyka',
            ],
            'ARE' => [
                'name' => 'Dirham',
                'cents' => 'Fils',
            ],
            'GBR' => [
                'name' => 'Pound',
                'cents' => 'Penny',
            ],
            'USA' => [
                'name' => 'Dollar',
                'cents' => 'cents',
            ],
            'URY' => [
                'name' => 'Peso',
                'cents' => 'centésimo',
            ],
            'UZB' => [
                'name' => 'Uzbekistani Som',
                'cents' => 'Tiyin',
            ],
            'VUT' => [
                'name' => 'Vanuatu Vatu',
                'cents' => 'hào',
            ],
            'VEN' => [
                'name' => 'Bolívar',
                'cents' => 'céntimo',
            ],
            'VNM' => [
                'name' => 'Đồng',
                'cents' => 'xu',
            ],
            'YEM' => [
                'name' => 'Yemeni Rial',
                'cents' => 'Fils',
            ],
            'ZMB' => [
                'name' => 'Kwacha',
                'cents' => 'Ngwee',
            ],
            'ZWE' => [
                'name' => 'Zimbabwean Dollar',
                'cents' => 'cents',
            ],
        ];
    }

    /**
     * Get the text representation of a currency code.
     *
     * @param string|null $code
     * - [NGA, USD, EUR]
     * 
     * @return array|null
     */
    static public function getCurrencyValue($code = null) 
    {
        // convert code to upper
        $code = Str::upper($code);

        // get data
        $data = self::currencyNames()[$code] ?? null;

        if(is_null($data)){
            return;
        }

        return Str::convertArrayCase($data, 'lower', 'lower');
    }
    
    /**
     * Get Units
     *
     * @return array
     */
    static public function getUnits()
    {
        return self::$units;
    }

    /**
     * Handle the calls to non-existent methods.
     * @param string|null $method
     * @param mixed $args
     * @param mixed $clone
     * @return mixed
     */
    static private function nonExistMethod($method = null, $args = null, $clone = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        // create correct method name
        $method = match ($name) {
            'iso', 'greeting' => '__iso',
            'cent', 'cents' => '__cents',
            default => '__value'
        };

        // this will happen if __construct has not been called 
        // before calling an existing method
        if(empty($clone)){
            $clone = new static();
        }

        return $clone->$method(...$args);
    }

}
