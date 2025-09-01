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
    static protected function isWordsInstance()
    {
        return self::$staticData instanceof NumberToWords;
    }

    /**
      * All currency 
      * - Country <iso-3></iso-3>
      *
      * @param string|null $iso3 Country iso3
      * @return array
      */
    public static function allCurrency($iso3 = null)
    {
        $data = [
            'AFG' => ['country' => 'Afghan', 'name' => 'Afghani', 'code' => 'AFN', 'cents' => 'puls (پول)'],
            'ALB' => ['country' => 'Albanian', 'name' => 'Lek', 'code' => 'ALL', 'cents' => 'qindarkë'],
            'DZA' => ['country' => 'Algerian', 'name' => 'Dinar', 'code' => 'DZD', 'cents' => 'centimes'],
            'AND' => ['country' => 'Andorran', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'AGO' => ['country' => 'Angolan', 'name' => 'Kwanza', 'code' => 'AOA', 'cents' => 'cêntimos'],
            'AIA' => ['country' => 'Anguillan', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents'],
            'ATG' => ['country' => 'Antiguan', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents'],
            'ARG' => ['country' => 'Argentine', 'name' => 'Peso', 'code' => 'ARS', 'cents' => 'cents'],
            'ARM' => ['country' => 'Armenian', 'name' => 'Dram', 'code' => 'AMD', 'cents' => 'luma (լումա)'],
            'AUS' => ['country' => 'Australian', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'AUT' => ['country' => 'Austrian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'AZE' => ['country' => 'Azerbaijani', 'name' => 'Manat', 'code' => 'AZN', 'cents' => 'qəpik (qəpiklər)'],
            'BHS' => ['country' => 'Bahamian', 'name' => 'Dollar', 'code' => 'BSD', 'cents' => 'cents'],
            'BHR' => ['country' => 'Bahraini', 'name' => 'Dinar', 'code' => 'BHD', 'cents' => 'fils'],
            'BGD' => ['country' => 'Bangladeshi', 'name' => 'Taka', 'code' => 'BDT', 'cents' => 'poisha'],
            'BRB' => ['country' => 'Barbadian', 'name' => 'Dollar', 'code' => 'BBD', 'cents' => 'cents'],
            'BLR' => ['country' => 'Belarusian', 'name' => 'Ruble', 'code' => 'BYN', 'cents' => 'cents'],
            'BEL' => ['country' => 'Belgian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'BLZ' => ['country' => 'Belizean', 'name' => 'Dollar', 'code' => 'BZD', 'cents' => 'cents'],
            'BEN' => ['country' => 'Beninese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'BMU' => ['country' => 'Bermudian', 'name' => 'Dollar', 'code' => 'BMD', 'cents' => 'cents'],
            'BTN' => ['country' => 'Bhutanese', 'name' => 'Ngultrum', 'code' => 'BTN', 'cents' => 'chhertum (ཕྱེད་ཏམ)'],
            'BOL' => ['country' => 'Bolivian', 'name' => 'Boliviano', 'code' => 'BOB', 'cents' => 'centavo'],
            'BES' => ['country' => 'Bonairean', 'name' => 'US Dollar', 'code' => 'USD', 'cents' => 'cents'],
            'BIH' => ['country' => 'Bosnian', 'name' => 'Convertible Mark', 'code' => 'BAM', 'cents' => 'fening'],
            'BWA' => ['country' => 'Botswana', 'name' => 'Pula', 'code' => 'BWP', 'cents' => 'thebe'],
            'BRA' => ['country' => 'Brazilian', 'name' => 'Real', 'code' => 'BRL', 'cents' => 'centavo'],
            'BRN' => ['country' => 'Bruneian', 'name' => 'Dollar', 'code' => 'BND', 'cents' => 'cents'],
            'BGR' => ['country' => 'Bulgarian', 'name' => 'Lev', 'code' => 'BGN', 'cents' => 'stotinki'],
            'BFA' => ['country' => 'Burkinese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'BDI' => ['country' => 'Burundian', 'name' => 'Burundi Franc', 'code' => 'BIF', 'cents' => 'centime'],
            'CPV' => ['country' => 'Cape Verdean', 'name' => 'Escudo', 'code' => 'CVE', 'cents' => 'centavo'],
            'KHM' => ['country' => 'Cambodian', 'name' => 'Riel', 'code' => 'KHR', 'cents' => 'sen'],
            'CMR' => ['country' => 'Cameroonian', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime'],
            'CAN' => ['country' => 'Canadian', 'name' => 'Dollar', 'code' => 'CAD', 'cents' => 'cent'],
            'CAF' => ['country' => 'Central African', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime'],
            'TCD' => ['country' => 'Chadian', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime'],
            'CHL' => ['country' => 'Chilean', 'name' => 'Peso', 'code' => 'CLP', 'cents' => 'centavo'],
            'CHN' => ['country' => 'Chinese', 'name' => 'Yuan', 'code' => 'CNY', 'cents' => 'jiao (角)'],
            'CXR' => ['country' => 'Christmas Island', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'CCK' => ['country' => 'Cocos Islands', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'COL' => ['country' => 'Colombian', 'name' => 'Peso', 'code' => 'COP', 'cents' => 'centavo'],
            'COM' => ['country' => 'Comorian', 'name' => 'Franc', 'code' => 'KMF', 'cents' => 'centime'],
            'COG' => ['country' => 'Congolese', 'name' => 'CFA Franc', 'code' => 'CFA', 'cents' => 'centime'],
            'COD' => ['country' => 'Congolese (DRC)', 'name' => 'Franc', 'code' => 'CDF', 'cents' => 'centime'],
            'COK' => ['country' => 'Cook Islands', 'name' => 'Dollar', 'code' => 'NZD', 'cents' => 'cents'],
            'CRI' => ['country' => 'Costa Rican', 'name' => 'Colón', 'code' => 'CRC', 'cents' => 'céntimo'],
            'CIV' => ['country' => 'Ivorian', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'HRV' => ['country' => 'Croatian', 'name' => 'Kuna', 'code' => 'HRK', 'cents' => 'lipa'],
            'CUB' => ['country' => 'Cuban', 'name' => 'Peso', 'code' => 'CUP', 'cents' => 'centavo'],
            'CYP' => ['country' => 'Cypriot', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'CZE' => ['country' => 'Czech', 'name' => 'Koruna', 'code' => 'CZK', 'cents' => 'haléř'],
            'DMA' => ['country' => 'Dominican', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents'],
            'DOM' => ['country' => 'Dominican Republic', 'name' => 'Peso', 'code' => 'DOP', 'cents' => 'centavo'],
            'DJI' => ['country' => 'Djiboutian', 'name' => 'Franc', 'code' => 'DJF', 'cents' => 'centime'],
            'DNK' => ['country' => 'Danish', 'name' => 'Krone', 'code' => 'DKK', 'cents' => 'øre'],
            'ECU' => ['country' => 'Ecuadorian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'EGY' => ['country' => 'Egyptian', 'name' => 'Pound', 'code' => 'EGP', 'cents' => 'piastre'],
            'SLV' => ['country' => 'Salvadoran', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'centavos'],
            'GNQ' => ['country' => 'Equatorial Guinean', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime'],
            'ERI' => ['country' => 'Eritrean', 'name' => 'Nakfa', 'code' => 'ERN', 'cents' => 'cents'],
            'EST' => ['country' => 'Estonian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'ETH' => ['country' => 'Ethiopian', 'name' => 'Birr', 'code' => 'ETB', 'cents' => 'cent'],
            'FRO' => ['country' => 'Faroese', 'name' => 'Króna', 'code' => 'DKK', 'cents' => 'øre'],
            'FIN' => ['country' => 'Finnish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'FJI' => ['country' => 'Fijian', 'name' => 'Dollar', 'code' => 'FJD', 'cents' => 'cents'],
            'FRA' => ['country' => 'French', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'GAB' => ['country' => 'Gabonese', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime'],
            'GMB' => ['country' => 'Gambian', 'name' => 'Dalasi', 'code' => 'GMD', 'cents' => 'butut'],
            'GEO' => ['country' => 'Georgian', 'name' => 'Lari', 'code' => 'GEL', 'cents' => 'tetri'],
            'DEU' => ['country' => 'German', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'GHA' => ['country' => 'Ghanaian', 'name' => 'Cedi', 'code' => 'GHS', 'cents' => 'pesewa'],
            'GRC' => ['country' => 'Greek', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents'],
            'GRD' => ['country' => 'Grenadian', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents'],
            'GUM' => ['country' => 'Guamanian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cents'],
            'GTM' => ['country' => 'Guatemalan', 'name' => 'Quetzal', 'code' => 'GTQ', 'cents' => 'centavo'],
            'GIN' => ['country' => 'Guinean', 'name' => 'Franc', 'code' => 'GNF', 'cents' => 'santim'],
            'GNB' => ['country' => 'Guinea-Bissauan', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'GUY' => ['country' => 'Guyanese', 'name' => 'Dollar', 'code' => 'GYD', 'cents' => 'cent'],
            'HTI' => ['country' => 'Haitian', 'name' => 'Gourde', 'code' => 'HTG', 'cents' => 'centime'],
            'HKG' => ['country' => 'Hong Kong', 'name' => 'Dollar', 'code' => 'HKD', 'cents' => 'cent'],
            'HND' => ['country' => 'Honduran', 'name' => 'Lempira', 'code' => 'HNL', 'cents' => 'centavo'],
            'HUN' => ['country' => 'Hungarian', 'name' => 'Forint', 'code' => 'HUF', 'cents' => 'filler'],
            'ISL' => ['country' => 'Icelandic', 'name' => 'Króna', 'code' => 'ISK', 'cents' => 'aurar'],
            'IND' => ['country' => 'Indian', 'name' => 'Rupee', 'code' => 'INR', 'cents' => 'paisa'],
            'IDN' => ['country' => 'Indonesian', 'name' => 'Rupiah', 'code' => 'IDR', 'cents' => 'sen'],
            'IRN' => ['country' => 'Iranian', 'name' => 'Rial', 'code' => 'IRR', 'cents' => 'rial'],
            'IRQ' => ['country' => 'Iraqi', 'name' => 'Dinar', 'code' => 'IQD', 'cents' => 'fils'],
            'IRL' => ['country' => 'Irish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'ISR' => ['country' => 'Israeli', 'name' => 'New Shekel', 'code' => 'ILS', 'cents' => 'agora'],
            'ITA' => ['country' => 'Italian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'JAM' => ['country' => 'Jamaican', 'name' => 'Dollar', 'code' => 'JMD', 'cents' => 'cent'],
            'JPN' => ['country' => 'Japanese', 'name' => 'Yen', 'code' => 'JPY', 'cents' => 'sen'],
            'JOR' => ['country' => 'Jordanian', 'name' => 'Dinar', 'code' => 'JOD', 'cents' => 'piastre'],
            'KAZ' => ['country' => 'Kazakh', 'name' => 'Tenge', 'code' => 'KZT', 'cents' => 'tiyn'],
            'KEN' => ['country' => 'Kenyan', 'name' => 'Shilling', 'code' => 'KES', 'cents' => 'cent'],
            'KIR' => ['country' => 'Kiribati', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'PRK' => ['country' => 'North Korean', 'name' => 'Won', 'code' => 'KPW', 'cents' => 'chon'],
            'KOR' => ['country' => 'South Korean', 'name' => 'Won', 'code' => 'KRW', 'cents' => 'jeon'],
            'KWT' => ['country' => 'Kuwaiti', 'name' => 'Dinar', 'code' => 'KWD', 'cents' => 'fils'],
            'KGZ' => ['country' => 'Kyrgyz', 'name' => 'Som', 'code' => 'KGS', 'cents' => 'tyiyn'],
            'LAO' => ['country' => 'Laotian', 'name' => 'Kip', 'code' => 'LAK', 'cents' => 'att'],
            'LVA' => ['country' => 'Latvian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'LBN' => ['country' => 'Lebanese', 'name' => 'Pound', 'code' => 'LBP', 'cents' => 'piastre'],
            'LSO' => ['country' => 'Lesotho', 'name' => 'Loti', 'code' => 'LSL', 'cents' => 'lisente'],
            'LBR' => ['country' => 'Liberian', 'name' => 'Dollar', 'code' => 'LRD', 'cents' => 'cent'],
            'LBY' => ['country' => 'Libyan', 'name' => 'Dinar', 'code' => 'LYD', 'cents' => 'dirham'],
            'LIE' => ['country' => 'Liechtenstein', 'name' => 'Franc', 'code' => 'CHF', 'cents' => 'rappen'],
            'LTU' => ['country' => 'Lithuanian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'LUX' => ['country' => 'Luxembourg', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'MKD' => ['country' => 'Macedonian', 'name' => 'Denar', 'code' => 'MKD', 'cents' => 'deni'],
            'MDG' => ['country' => 'Malagasy', 'name' => 'Ariary', 'code' => 'MGA', 'cents' => 'iraimbilanja'],
            'MWI' => ['country' => 'Malawian', 'name' => 'Kwacha', 'code' => 'MWK', 'cents' => 'ngwee'],
            'MYS' => ['country' => 'Malaysian', 'name' => 'Ringgit', 'code' => 'MYR', 'cents' => 'sen'],
            'MDV' => ['country' => 'Maldivian', 'name' => 'Rufiyaa', 'code' => 'MVR', 'cents' => 'laari'],
            'MLI' => ['country' => 'Malian', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'MLT' => ['country' => 'Maltese', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'MHL' => ['country' => 'Marshall Islands', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'MRT' => ['country' => 'Mauritanian', 'name' => 'Ouguiya', 'code' => 'MRU', 'cents' => 'Khoums'],
            'MUS' => ['country' => 'Mauritian', 'name' => 'Rupee', 'code' => 'MUR', 'cents' => 'Cent'],
            'MEX' => ['country' => 'Mexican', 'name' => 'Peso', 'code' => 'MXN', 'cents' => 'centavo'],
            'FSM' => ['country' => 'Micronesian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'MDA' => ['country' => 'Moldovan', 'name' => 'Leu', 'code' => 'MDL', 'cents' => 'bani'],
            'MCO' => ['country' => 'Monacan', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'MNG' => ['country' => 'Mongolian', 'name' => 'Tugrik', 'code' => 'MNT', 'cents' => 'möngö'],
            'MNE' => ['country' => 'Montenegrin', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'MAR' => ['country' => 'Moroccan', 'name' => 'Dirham', 'code' => 'MAD', 'cents' => 'Centime'],
            'MOZ' => ['country' => 'Mozambican', 'name' => 'Metical', 'code' => 'MZN', 'cents' => 'centavo'],
            'MMR' => ['country' => 'Burmese', 'name' => 'Kyat', 'code' => 'MMK', 'cents' => 'pya'],
            'NAM' => ['country' => 'Namibian', 'name' => 'Dollar', 'code' => 'NAD', 'cents' => 'cents'],
            'NRU' => ['country' => 'Nauruan', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'NPL' => ['country' => 'Nepali', 'name' => 'Rupee', 'code' => 'NPR', 'cents' => 'paisa'],
            'NLD' => ['country' => 'Dutch', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'NCL' => ['country' => 'New Caledonian', 'name' => 'CFA Franc', 'code' => 'XPF', 'cents' => 'centime'],
            'NZL' => ['country' => 'New Zealand', 'name' => 'Dollar', 'code' => 'NZD', 'cents' => 'cents'],
            'NIC' => ['country' => 'Nicaraguan', 'name' => 'Córdoba', 'code' => 'NIO', 'cents' => 'centavo'],
            'NER' => ['country' => 'Nigerien', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'NGA' => ['country' => 'Nigerian', 'name' => 'Naira', 'code' => 'NGN', 'cents' => 'kobo'],
            'NOR' => ['country' => 'Norwegian', 'name' => 'Krone', 'code' => 'NOK', 'cents' => 'øre'],
            'OMN' => ['country' => 'Omani', 'name' => 'Rial', 'code' => 'OMR', 'cents' => 'Baisa'],
            'NVA' => ['country' => 'Navajo Nation', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'PAK' => ['country' => 'Pakistani', 'name' => 'Rupee', 'code' => 'PKR', 'cents' => 'paisa'],
            'PLW' => ['country' => 'Palauan', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'PAN' => ['country' => 'Panamanian', 'name' => 'Balboa', 'code' => 'PAB', 'cents' => 'centavo'],
            'PNG' => ['country' => 'Papua New Guinea', 'name' => 'Kina', 'code' => 'PGK', 'cents' => 'toea'],
            'PRY' => ['country' => 'Paraguayan', 'name' => 'Guarani', 'code' => 'PYG', 'cents' => 'Céntimo'],
            'PER' => ['country' => 'Peruvian', 'name' => 'Nuevo Sol', 'code' => 'PEN', 'cents' => 'Céntimo'],
            'PHL' => ['country' => 'Philippine', 'name' => 'Peso', 'code' => 'PHP', 'cents' => 'Sentimo'],
            'POL' => ['country' => 'Polish', 'name' => 'Złoty', 'code' => 'PLN', 'cents' => 'Grosz'],
            'PRT' => ['country' => 'Portuguese', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'QAT' => ['country' => 'Qatari', 'name' => 'Riyal', 'code' => 'QAR', 'cents' => 'dirham'],
            'ROU' => ['country' => 'Romanian', 'name' => 'Leu', 'code' => 'RON', 'cents' => 'bani'],
            'RUS' => ['country' => 'Russian', 'name' => 'Ruble', 'code' => 'RUB', 'cents' => 'kopeck'],
            'RWA' => ['country' => 'Rwandan', 'name' => 'Franc', 'code' => 'RWF', 'cents' => 'centime'],
            'WSM' => ['country' => 'Samoan', 'name' => 'Tala', 'code' => 'WST', 'cents' => 'Sene'],
            'KNA' => ['country' => 'Saint Kitts and Nevis', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cent'],
            'VCT' => ['country' => 'Saint Vincent and the Grenadines', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cent'],
            'SMR' => ['country' => 'San Marino', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'STP' => ['country' => 'São Toméan', 'name' => 'Dobra', 'code' => 'STN', 'cents' => 'cent'],
            'SAU' => ['country' => 'Saudi', 'name' => 'Riyal', 'code' => 'SAR', 'cents' => 'halala'],
            'SEN' => ['country' => 'Senegalese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'SRB' => ['country' => 'Serbian', 'name' => 'Dinar', 'code' => 'RSD', 'cents' => 'Para'],
            'SYC' => ['country' => 'Seychellois', 'name' => 'Rupee', 'code' => 'SCR', 'cents' => 'cent'],
            'SLE' => ['country' => 'Sierra Leonean', 'name' => 'Leone', 'code' => 'SLL', 'cents' => 'cent'],
            'SGP' => ['country' => 'Singaporean', 'name' => 'Dollar', 'code' => 'SGD', 'cents' => 'cent'],
            'SVK' => ['country' => 'Slovak', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'SVN' => ['country' => 'Slovenian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'SLB' => ['country' => 'Solomon Islands', 'name' => 'Dollar', 'code' => 'SBD', 'cents' => 'cent'],
            'SOM' => ['country' => 'Somali', 'name' => 'Shilling', 'code' => 'SOS', 'cents' => 'cent'],
            'ZAF' => ['country' => 'South African', 'name' => 'Rand', 'code' => 'ZAR', 'cents' => 'cent'],
            'SSD' => ['country' => 'South Sudanese', 'name' => 'Pound', 'code' => 'SSP', 'cents' => 'Piaster'],
            'SDN' => ['country' => 'Sudanese', 'name' => 'Pound', 'code' => 'SDG', 'cents' => 'Piastre'],
            'ESP' => ['country' => 'Spanish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent'],
            'LKA' => ['country' => 'Sri Lankan', 'name' => 'Rupee', 'code' => 'LKR', 'cents' => 'Cent'],
            'SUR' => ['country' => 'Surinamese', 'name' => 'Dollar', 'code' => 'SRD', 'cents' => 'cent'],
            'SWZ' => ['country' => 'Swazi', 'name' => 'Lilangeni', 'code' => 'SZL', 'cents' => 'cent'],
            'SWE' => ['country' => 'Swedish', 'name' => 'Krona', 'code' => 'SEK', 'cents' => 'öre'],
            'CHE' => ['country' => 'Swiss', 'name' => 'Franc', 'code' => 'CHF', 'cents' => 'rappen'],
            'SYR' => ['country' => 'Syrian', 'name' => 'Pound', 'code' => 'SYP', 'cents' => 'piastre'],
            'TWN' => ['country' => 'Taiwanese', 'name' => 'Dollar', 'code' => 'TWD', 'cents' => 'cent'],
            'TJK' => ['country' => 'Tajik', 'name' => 'Somoni', 'code' => 'TJS', 'cents' => 'diram'],
            'TZA' => ['country' => 'Tanzanian', 'name' => 'Shilling', 'code' => 'TZS', 'cents' => 'Cent'],
            'THA' => ['country' => 'Thai', 'name' => 'Baht', 'code' => 'THB', 'cents' => 'Satang'],
            'TGO' => ['country' => 'Togolese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime'],
            'TON' => ['country' => 'Tongan', 'name' => 'Paʻanga', 'code' => 'TOP', 'cents' => 'seniti'],
            'TTO' => ['country' => 'Trinidad and Tobagonian', 'name' => 'Dollar', 'code' => 'TTD', 'cents' => 'Cent'],
            'TUN' => ['country' => 'Tunisian', 'name' => 'Dinar', 'code' => 'TND', 'cents' => 'Millime'],
            'TUR' => ['country' => 'Turkish', 'name' => 'Lira', 'code' => 'TRY', 'cents' => 'kuruş'],
            'TKM' => ['country' => 'Turkmen', 'name' => 'Manat', 'code' => 'TMT', 'cents' => 'tenge'],
            'TUV' => ['country' => 'Tuvaluan', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents'],
            'UGA' => ['country' => 'Ugandan', 'name' => 'Shilling', 'code' => 'UGX', 'cents' => 'cent'],
            'UKR' => ['country' => 'Ukrainian', 'name' => 'Hryvnia', 'code' => 'UAH', 'cents' => 'kopiyka'],
            'ARE' => ['country' => 'Emirati', 'name' => 'Dirham', 'code' => 'AED', 'cents' => 'fils'],
            'GBR' => ['country' => 'British', 'name' => 'Pound', 'code' => 'GBP', 'cents' => 'pence'],
            'USA' => ['country' => 'United States', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent'],
            'URY' => ['country' => 'Uruguayan', 'name' => 'Peso', 'code' => 'UYU', 'cents' => 'centésimo'],
            'UZB' => ['country' => 'Uzbekistani', 'name' => 'Som', 'code' => 'UZS', 'cents' => 'tiyin'],
            'VUT' => ['country' => 'Vanuatu', 'name' => 'Vatu', 'code' => 'VUV', 'cents' => 'cent'],
            'VEN' => ['country' => 'Venezuelan', 'name' => 'Bolívar', 'code' => 'VES', 'cents' => 'centimo'],
            'VNM' => ['country' => 'Vietnamese', 'name' => 'Dong', 'code' => 'VND', 'cents' => 'hào'],
            'WLF' => ['country' => 'Wallis and Futuna', 'name' => 'CFP Franc', 'code' => 'CFP', 'cents' => 'centime'],
            'YEM' => ['country' => 'Yemeni', 'name' => 'Rial', 'code' => 'YER', 'cents' => 'fils'],
            'ZMB' => ['country' => 'Zambian', 'name' => 'Kwacha', 'code' => 'ZMW', 'cents' => 'ngwee'],
            'ZWE' => ['country' => 'Zimbabwean', 'name' => 'Dollar', 'code' => 'ZWD', 'cents' => 'cent'],
        ];

        return $data[$iso3] ?? $data;
    }

    /**
     * Get the text representation of a currency code.
     *
     * @param string|null $code
     * - [NGA, USA, FRA]
     * 
     * @return array|null
     */
    public static function getCurrencyByIso3($code = null) 
    {
        // convert code to upper
        $code = Str::upper($code);

        // get data
        $data = self::allCurrency()[$code] ?? null;

        if(is_null($data)){
            return;
        }

        return $data;
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
            'iso', 'iso3', 'locale' => '__iso',
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
