<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\NumberToWords;


/**
 * 
 * @property mixed $staticData
*/
trait NumberToWordsTraits
{

    /**
     * Units
     * Can be able to convert numbers unto quintillion
     *
     * @var array
     */
    private static $units = [
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

    // Short unit suffixes
    private static $suffixes = [
        '',
        'k', // Thousand
        'm', // Million
        'b', // Billion
        't', // Trillion
        'q', // Quadrillion
        'Q', // Quintillion
        's', // Sextillion
        'S', // Septillion
        'o', // Octillion
        'n', // Nonillion
        'd', // Decillion
        'u', // Undecillion
        'D', // Duodecillion
        'T', // Tredecillion
        'qD', // Quattuordecillion
        'Qd', // Quindecillion
        'sD', // Sexdecillion
        'Sd', // Septendecillion
        'oD', // Octodecillion
        'nD', // Novemdecillion
        'v', // Vigintillion
    ];

    /**
     * Words
     *
     * @var array
     */
    private static $words = [
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
    private static $tens = [
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
    private static $numberMap = [
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
    private static $scaleMap = [
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
    protected static function isWordsInstance()
    {
        return self::$staticData instanceof NumberToWords;
    }

    /**
      * All currency 
      *
      * @param string|null $code Currency (iso-4217) code
      * @return array
      */
    public static function allCurrency($code = null)
    {
        $array = [
            'AFN' => ['name' => 'Afghani', 'code' => 'AFN', 'cents' => 'puls (پول)', 'symbol' => '؋'],
            'ALL' => ['name' => 'Lek', 'code' => 'ALL', 'cents' => 'qindarkë', 'symbol' => 'L'],
            'DZD' => ['name' => 'Dinar', 'code' => 'DZD', 'cents' => 'centimes', 'symbol' => 'د.ج'],
            'EUR' => ['name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'AOA' => ['name' => 'Kwanza', 'code' => 'AOA', 'cents' => 'cêntimos', 'symbol' => 'Kz'],
            'XCD' => ['name' => 'East Caribbean Dollar', 'code' => 'XCD', 'cents' => 'cents', 'symbol' => '$'],
            'ARS' => ['name' => 'Peso', 'code' => 'ARS', 'cents' => 'cents', 'symbol' => '$'],
            'AMD' => ['name' => 'Dram', 'code' => 'AMD', 'cents' => 'luma (լումա)', 'symbol' => '֏'],
            'AUD' => ['name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'AZN' => ['name' => 'Manat', 'code' => 'AZN', 'cents' => 'qəpik (qəpiklər)', 'symbol' => '₼'],
            'BSD' => ['name' => 'Dollar', 'code' => 'BSD', 'cents' => 'cents', 'symbol' => '$'],
            'BHD' => ['name' => 'Dinar', 'code' => 'BHD', 'cents' => 'fils', 'symbol' => '.د.ب'],
            'BDT' => ['name' => 'Taka', 'code' => 'BDT', 'cents' => 'poisha', 'symbol' => '৳'],
            'BBD' => ['name' => 'Dollar', 'code' => 'BBD', 'cents' => 'cents', 'symbol' => '$'],
            'BYN' => ['name' => 'Ruble', 'code' => 'BYN', 'cents' => 'cents', 'symbol' => 'Br'],
            'BZD' => ['name' => 'Dollar', 'code' => 'BZD', 'cents' => 'cents', 'symbol' => '$'],
            'XOF' => ['name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'BMD' => ['name' => 'Dollar', 'code' => 'BMD', 'cents' => 'cents', 'symbol' => '$'],
            'BTN' => ['name' => 'Ngultrum', 'code' => 'BTN', 'cents' => 'chhertum (ཕྱེད་ཏམ)', 'symbol' => 'Nu.'],
            'BOB' => ['name' => 'Boliviano', 'code' => 'BOB', 'cents' => 'centavo', 'symbol' => 'Bs.'],
            'USD' => ['name' => 'US Dollar', 'code' => 'USD', 'cents' => 'cents', 'symbol' => '$'],
            'BAM' => ['name' => 'Convertible Mark', 'code' => 'BAM', 'cents' => 'fening', 'symbol' => 'KM'],
            'BWP' => ['name' => 'Pula', 'code' => 'BWP', 'cents' => 'thebe', 'symbol' => 'P'],
            'BRL' => ['name' => 'Real', 'code' => 'BRL', 'cents' => 'centavo', 'symbol' => 'R$'],
            'BND' => ['name' => 'Dollar', 'code' => 'BND', 'cents' => 'cents', 'symbol' => '$'],
            'BGN' => ['name' => 'Lev', 'code' => 'BGN', 'cents' => 'stotinki', 'symbol' => 'лв'],
            'BIF' => ['name' => 'Burundi Franc', 'code' => 'BIF', 'cents' => 'centime', 'symbol' => 'FBu'],
            'CVE' => ['name' => 'Escudo', 'code' => 'CVE', 'cents' => 'centavo', 'symbol' => '$'],
            'KHR' => ['name' => 'Riel', 'code' => 'KHR', 'cents' => 'sen', 'symbol' => '៛'],
            'XAF' => ['name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'CAD' => ['name' => 'Dollar', 'code' => 'CAD', 'cents' => 'cent', 'symbol' => '$'],
            'CLP' => ['name' => 'Peso', 'code' => 'CLP', 'cents' => 'centavo', 'symbol' => '$'],
            'CNY' => ['name' => 'Yuan', 'code' => 'CNY', 'cents' => 'jiao (角)', 'symbol' => '¥'],
            'COP' => ['name' => 'Peso', 'code' => 'COP', 'cents' => 'centavo', 'symbol' => '$'],
            'KMF' => ['name' => 'Franc', 'code' => 'KMF', 'cents' => 'centime', 'symbol' => 'CF'],
            'CFA' => ['name' => 'CFA Franc', 'code' => 'CFA', 'cents' => 'centime', 'symbol' => 'CFA'],
            'CDF' => ['name' => 'Franc', 'code' => 'CDF', 'cents' => 'centime', 'symbol' => 'FC'],
            'NZD' => ['name' => 'Dollar', 'code' => 'NZD', 'cents' => 'cents', 'symbol' => '$'],
            'CRC' => ['name' => 'Colón', 'code' => 'CRC', 'cents' => 'céntimo', 'symbol' => '₡'],
            'HRK' => ['name' => 'Kuna', 'code' => 'HRK', 'cents' => 'lipa', 'symbol' => 'kn'],
            'CUP' => ['name' => 'Peso', 'code' => 'CUP', 'cents' => 'centavo', 'symbol' => '$'],
            'CZK' => ['name' => 'Koruna', 'code' => 'CZK', 'cents' => 'haléř', 'symbol' => 'Kč'],
            'DOP' => ['name' => 'Peso', 'code' => 'DOP', 'cents' => 'centavo', 'symbol' => '$'],
            'DJF' => ['name' => 'Franc', 'code' => 'DJF', 'cents' => 'centime', 'symbol' => 'Fdj'],
            'DKK' => ['name' => 'Krone', 'code' => 'DKK', 'cents' => 'øre', 'symbol' => 'kr'],
            'EGP' => ['name' => 'Pound', 'code' => 'EGP', 'cents' => 'piastre', 'symbol' => '£'],
            'ERN' => ['name' => 'Nakfa', 'code' => 'ERN', 'cents' => 'cents', 'symbol' => 'Nfk'],
            'ETB' => ['name' => 'Birr', 'code' => 'ETB', 'cents' => 'cent', 'symbol' => 'Br'],
            'FJD' => ['name' => 'Dollar', 'code' => 'FJD', 'cents' => 'cents', 'symbol' => '$'],
            'GMD' => ['name' => 'Dalasi', 'code' => 'GMD', 'cents' => 'butut', 'symbol' => 'D'],
            'GEL' => ['name' => 'Lari', 'code' => 'GEL', 'cents' => 'tetri', 'symbol' => '₾'],
            'GHS' => ['name' => 'Cedi', 'code' => 'GHS', 'cents' => 'pesewa', 'symbol' => '₵'],
            'GTQ' => ['name' => 'Quetzal', 'code' => 'GTQ', 'cents' => 'centavo', 'symbol' => 'Q'],
            'GNF' => ['name' => 'Franc', 'code' => 'GNF', 'cents' => 'santim', 'symbol' => 'FG'],
            'GYD' => ['name' => 'Dollar', 'code' => 'GYD', 'cents' => 'cent', 'symbol' => '$'],
            'HTG' => ['name' => 'Gourde', 'code' => 'HTG', 'cents' => 'centime', 'symbol' => 'G'],
            'HKD' => ['name' => 'Dollar', 'code' => 'HKD', 'cents' => 'cent', 'symbol' => '$'],
            'HNL' => ['name' => 'Lempira', 'code' => 'HNL', 'cents' => 'centavo', 'symbol' => 'L'],
            'HUF' => ['name' => 'Forint', 'code' => 'HUF', 'cents' => 'filler', 'symbol' => 'Ft'],
            'ISK' => ['name' => 'Króna', 'code' => 'ISK', 'cents' => 'aurar', 'symbol' => 'kr'],
            'INR' => ['name' => 'Rupee', 'code' => 'INR', 'cents' => 'paisa', 'symbol' => '₹'],
            'IDR' => ['name' => 'Rupiah', 'code' => 'IDR', 'cents' => 'sen', 'symbol' => 'Rp'],
            'IRR' => ['name' => 'Rial', 'code' => 'IRR', 'cents' => 'rial', 'symbol' => '﷼'],
            'IQD' => ['name' => 'Dinar', 'code' => 'IQD', 'cents' => 'fils', 'symbol' => 'ع.د'],
            'ILS' => ['name' => 'New Shekel', 'code' => 'ILS', 'cents' => 'agora', 'symbol' => '₪'],
            'JMD' => ['name' => 'Dollar', 'code' => 'JMD', 'cents' => 'cent', 'symbol' => '$'],
            'JPY' => ['name' => 'Yen', 'code' => 'JPY', 'cents' => 'sen', 'symbol' => '¥'],
            'JOD' => ['name' => 'Dinar', 'code' => 'JOD', 'cents' => 'piastre', 'symbol' => 'د.ا'],
            'KZT' => ['name' => 'Tenge', 'code' => 'KZT', 'cents' => 'tiyn', 'symbol' => '₸'],
            'KES' => ['name' => 'Shilling', 'code' => 'KES', 'cents' => 'cent', 'symbol' => 'KSh'],
            'KPW' => ['name' => 'Won', 'code' => 'KPW', 'cents' => 'chon', 'symbol' => '₩'],
            'KRW' => ['name' => 'Won', 'code' => 'KRW', 'cents' => 'jeon', 'symbol' => '₩'],
            'KWD' => ['name' => 'Dinar', 'code' => 'KWD', 'cents' => 'fils', 'symbol' => 'د.ك'],
            'KGS' => ['name' => 'Som', 'code' => 'KGS', 'cents' => 'tyiyn', 'symbol' => 'с'],
            'LAK' => ['name' => 'Kip', 'code' => 'LAK', 'cents' => 'att', 'symbol' => '₭'],
            'LBP' => ['name' => 'Pound', 'code' => 'LBP', 'cents' => 'piastre', 'symbol' => 'ل.ل'],
            'LSL' => ['name' => 'Loti', 'code' => 'LSL', 'cents' => 'lisente', 'symbol' => 'L'],
            'LRD' => ['name' => 'Dollar', 'code' => 'LRD', 'cents' => 'cent', 'symbol' => '$'],
            'LYD' => ['name' => 'Dinar', 'code' => 'LYD', 'cents' => 'dirham', 'symbol' => 'ل.د'],
            'CHF' => ['name' => 'Franc', 'code' => 'CHF', 'cents' => 'rappen', 'symbol' => 'CHF'],
            'MKD' => ['name' => 'Denar', 'code' => 'MKD', 'cents' => 'deni', 'symbol' => 'ден'],
            'MGA' => ['name' => 'Ariary', 'code' => 'MGA', 'cents' => 'iraimbilanja', 'symbol' => 'Ar'],
            'MWK' => ['name' => 'Kwacha', 'code' => 'MWK', 'cents' => 'ngwee', 'symbol' => 'MK'],
            'MYR' => ['name' => 'Ringgit', 'code' => 'MYR', 'cents' => 'sen', 'symbol' => 'RM'],
            'MVR' => ['name' => 'Rufiyaa', 'code' => 'MVR', 'cents' => 'laari', 'symbol' => 'Rf'],
            'MRU' => ['name' => 'Ouguiya', 'code' => 'MRU', 'cents' => 'Khoums', 'symbol' => 'UM'],
            'MUR' => ['name' => 'Rupee', 'code' => 'MUR', 'cents' => 'Cent', 'symbol' => '₨'],
            'MXN' => ['name' => 'Peso', 'code' => 'MXN', 'cents' => 'centavo', 'symbol' => '$'],
            'MDL' => ['name' => 'Leu', 'code' => 'MDL', 'cents' => 'bani', 'symbol' => 'L'],
            'MNT' => ['name' => 'Tugrik', 'code' => 'MNT', 'cents' => 'möngö', 'symbol' => '₮'],
            'MAD' => ['name' => 'Dirham', 'code' => 'MAD', 'cents' => 'Centime', 'symbol' => 'د.م.'],
            'MZN' => ['name' => 'Metical', 'code' => 'MZN', 'cents' => 'centavo', 'symbol' => 'MT'],
            'MMK' => ['name' => 'Kyat', 'code' => 'MMK', 'cents' => 'pya', 'symbol' => 'K'],
            'NAD' => ['name' => 'Dollar', 'code' => 'NAD', 'cents' => 'cents', 'symbol' => '$'],
            'NPR' => ['name' => 'Rupee', 'code' => 'NPR', 'cents' => 'paisa', 'symbol' => '₨'],
            'XPF' => ['name' => 'CFP Franc', 'code' => 'XPF', 'cents' => 'centime', 'symbol' => '₣'],
            'NIO' => ['name' => 'Córdoba', 'code' => 'NIO', 'cents' => 'centavo', 'symbol' => 'C$'],
            'NGN' => ['name' => 'Naira', 'code' => 'NGN', 'cents' => 'kobo', 'symbol' => '₦'],
            'NOK' => ['name' => 'Krone', 'code' => 'NOK', 'cents' => 'øre', 'symbol' => 'kr'],
            'OMR' => ['name' => 'Rial', 'code' => 'OMR', 'cents' => 'Baisa', 'symbol' => 'ر.ع.'],
            'PKR' => ['name' => 'Rupee', 'code' => 'PKR', 'cents' => 'paisa', 'symbol' => '₨'],
            'PAB' => ['name' => 'Balboa', 'code' => 'PAB', 'cents' => 'centavo', 'symbol' => 'B/.'],
            'PGK' => ['name' => 'Kina', 'code' => 'PGK', 'cents' => 'toea', 'symbol' => 'K'],
            'PYG' => ['name' => 'Guarani', 'code' => 'PYG', 'cents' => 'Céntimo', 'symbol' => '₲'],
            'PEN' => ['name' => 'Nuevo Sol', 'code' => 'PEN', 'cents' => 'Céntimo', 'symbol' => 'S/.'],
            'PHP' => ['name' => 'Peso', 'code' => 'PHP', 'cents' => 'Sentimo', 'symbol' => '₱'],
            'PLN' => ['name' => 'Złoty', 'code' => 'PLN', 'cents' => 'Grosz', 'symbol' => 'zł'],
            'QAR' => ['name' => 'Riyal', 'code' => 'QAR', 'cents' => 'dirham', 'symbol' => 'ر.ق'],
            'RON' => ['name' => 'Leu', 'code' => 'RON', 'cents' => 'bani', 'symbol' => 'lei'],
            'RUB' => ['name' => 'Ruble', 'code' => 'RUB', 'cents' => 'kopeck', 'symbol' => '₽'],
            'RWF' => ['name' => 'Franc', 'code' => 'RWF', 'cents' => 'centime', 'symbol' => 'FRw'],
            'WST' => ['name' => 'Tala', 'code' => 'WST', 'cents' => 'Sene', 'symbol' => 'WS$'],
            'STN' => ['name' => 'Dobra', 'code' => 'STN', 'cents' => 'cent', 'symbol' => 'Db'],
            'SAR' => ['name' => 'Riyal', 'code' => 'SAR', 'cents' => 'halala', 'symbol' => 'ر.س'],
            'RSD' => ['name' => 'Dinar', 'code' => 'RSD', 'cents' => 'Para', 'symbol' => 'дин.'],
            'SCR' => ['name' => 'Rupee', 'code' => 'SCR', 'cents' => 'cent', 'symbol' => '₨'],
            'SLL' => ['name' => 'Leone', 'code' => 'SLL', 'cents' => 'cent', 'symbol' => 'Le'],
            'SGD' => ['name' => 'Dollar', 'code' => 'SGD', 'cents' => 'cent', 'symbol' => '$'],
            'SBD' => ['name' => 'Dollar', 'code' => 'SBD', 'cents' => 'cent', 'symbol' => '$'],
            'SOS' => ['name' => 'Shilling', 'code' => 'SOS', 'cents' => 'cent', 'symbol' => 'S'],
            'ZAR' => ['name' => 'Rand', 'code' => 'ZAR', 'cents' => 'cent', 'symbol' => 'R'],
            'SSP' => ['name' => 'Pound', 'code' => 'SSP', 'cents' => 'Piaster', 'symbol' => '£'],
            'SDG' => ['name' => 'Pound', 'code' => 'SDG', 'cents' => 'Piastre', 'symbol' => '£'],
            'LKR' => ['name' => 'Rupee', 'code' => 'LKR', 'cents' => 'Cent', 'symbol' => '₨'],
            'SRD' => ['name' => 'Dollar', 'code' => 'SRD', 'cents' => 'cent', 'symbol' => '$'],
            'SZL' => ['name' => 'Lilangeni', 'code' => 'SZL', 'cents' => 'cent', 'symbol' => 'E'],
            'SEK' => ['name' => 'Krona', 'code' => 'SEK', 'cents' => 'öre', 'symbol' => 'kr'],
            'SYP' => ['name' => 'Pound', 'code' => 'SYP', 'cents' => 'piastre', 'symbol' => '£'],
            'TWD' => ['name' => 'Dollar', 'code' => 'TWD', 'cents' => 'cent', 'symbol' => 'NT$'],
            'TJS' => ['name' => 'Somoni', 'code' => 'TJS', 'cents' => 'diram', 'symbol' => 'ЅМ'],
            'TZS' => ['name' => 'Shilling', 'code' => 'TZS', 'cents' => 'Cent', 'symbol' => 'TSh'],
            'THB' => ['name' => 'Baht', 'code' => 'THB', 'cents' => 'Satang', 'symbol' => '฿'],
            'TOP' => ['name' => 'Paʻanga', 'code' => 'TOP', 'cents' => 'seniti', 'symbol' => 'T$'],
            'TTD' => ['name' => 'Dollar', 'code' => 'TTD', 'cents' => 'Cent', 'symbol' => 'TT$'],
            'TND' => ['name' => 'Dinar', 'code' => 'TND', 'cents' => 'Millime', 'symbol' => 'د.ت'],
            'TRY' => ['name' => 'Lira', 'code' => 'TRY', 'cents' => 'kuruş', 'symbol' => '₺'],
            'TMT' => ['name' => 'Manat', 'code' => 'TMT', 'cents' => 'tenge', 'symbol' => 'm'],
            'UGX' => ['name' => 'Shilling', 'code' => 'UGX', 'cents' => 'cent', 'symbol' => 'USh'],
            'UAH' => ['name' => 'Hryvnia', 'code' => 'UAH', 'cents' => 'kopiyka', 'symbol' => '₴'],
            'AED' => ['name' => 'Dirham', 'code' => 'AED', 'cents' => 'fils', 'symbol' => 'د.إ'],
            'GBP' => ['name' => 'Pound', 'code' => 'GBP', 'cents' => 'pence', 'symbol' => '£'],
            'UYU' => ['name' => 'Peso', 'code' => 'UYU', 'cents' => 'centésimo', 'symbol' => '$'],
            'UZS' => ['name' => 'Som', 'code' => 'UZS', 'cents' => 'tiyin', 'symbol' => 'лв'],
            'VUV' => ['name' => 'Vatu', 'code' => 'VUV', 'cents' => 'cent', 'symbol' => 'VT'],
            'VES' => ['name' => 'Bolívar', 'code' => 'VES', 'cents' => 'centimo', 'symbol' => 'Bs.'],
            'VND' => ['name' => 'Dong', 'code' => 'VND', 'cents' => 'hào', 'symbol' => '₫'],
            'CFP' => ['name' => 'CFP Franc', 'code' => 'CFP', 'cents' => 'centime', 'symbol' => '₣'],
            'YER' => ['name' => 'Rial', 'code' => 'YER', 'cents' => 'fils', 'symbol' => '﷼'],
            'ZMW' => ['name' => 'Kwacha', 'code' => 'ZMW', 'cents' => 'ngwee', 'symbol' => 'ZK'],
            'ZWD' => ['name' => 'Dollar', 'code' => 'ZWD', 'cents' => 'cent', 'symbol' => '$'],
        ];

        return $array[$code] ?? $array;
    }

    /**
     * Get the text representation of a currency code.
     *
     * @param string|null $code
     * - [NGN, USD, EUR]
     * 
     * @return array|null
     */
    public static function getCurrencyByCode($code = null) 
    {
        // convert code to upper
        $code = Str::upper($code);

        // get data
        $array = self::allCurrency()[$code] ?? null;

        if(is_null($array)){
            return;
        }

        return $array;
    }
    
    /**
     * Get Units
     *
     * @return array
     */
    public static function getUnits()
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
    private static function nonExistMethod($method = null, $args = null, $clone = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        // create correct method name
        $method = match ($name) {
            'iso', 'iso3', 'code' => '__iso',
            'cent', 'cents' => '__cents',
            default => '__value'
        };

        // this will happen if __construct has not been called 
        // before calling an existing method
        if(empty($clone)){
            $clone = new self();
        }

        return $clone->$method(...$args);
    }

}
