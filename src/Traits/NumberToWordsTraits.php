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
      * - Country <iso-3></iso-3>
      *
      * @param string|null $iso3 Country iso3
      * @return array
      */
    public static function allCurrency($iso3 = null)
    {
        $array = [
            'AFG' => ['country' => 'Afghan', 'name' => 'Afghani', 'code' => 'AFN', 'cents' => 'puls (پول)', 'symbol' => '؋'],
            'ALB' => ['country' => 'Albanian', 'name' => 'Lek', 'code' => 'ALL', 'cents' => 'qindarkë', 'symbol' => 'L'],
            'DZA' => ['country' => 'Algerian', 'name' => 'Dinar', 'code' => 'DZD', 'cents' => 'centimes', 'symbol' => 'د.ج'],
            'AND' => ['country' => 'Andorran', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'AGO' => ['country' => 'Angolan', 'name' => 'Kwanza', 'code' => 'AOA', 'cents' => 'cêntimos', 'symbol' => 'Kz'],
            'AIA' => ['country' => 'Anguillan', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents', 'symbol' => '$'],
            'ATG' => ['country' => 'Antiguan', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents', 'symbol' => '$'],
            'ARG' => ['country' => 'Argentine', 'name' => 'Peso', 'code' => 'ARS', 'cents' => 'cents', 'symbol' => '$'],
            'ARM' => ['country' => 'Armenian', 'name' => 'Dram', 'code' => 'AMD', 'cents' => 'luma (լումա)', 'symbol' => '֏'],
            'AUS' => ['country' => 'Australian', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'AUT' => ['country' => 'Austrian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'AZE' => ['country' => 'Azerbaijani', 'name' => 'Manat', 'code' => 'AZN', 'cents' => 'qəpik (qəpiklər)', 'symbol' => '₼'],
            'BHS' => ['country' => 'Bahamian', 'name' => 'Dollar', 'code' => 'BSD', 'cents' => 'cents', 'symbol' => '$'],
            'BHR' => ['country' => 'Bahraini', 'name' => 'Dinar', 'code' => 'BHD', 'cents' => 'fils', 'symbol' => '.د.ب'],
            'BGD' => ['country' => 'Bangladeshi', 'name' => 'Taka', 'code' => 'BDT', 'cents' => 'poisha', 'symbol' => '৳'],
            'BRB' => ['country' => 'Barbadian', 'name' => 'Dollar', 'code' => 'BBD', 'cents' => 'cents', 'symbol' => '$'],
            'BLR' => ['country' => 'Belarusian', 'name' => 'Ruble', 'code' => 'BYN', 'cents' => 'cents', 'symbol' => 'Br'],
            'BEL' => ['country' => 'Belgian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'BLZ' => ['country' => 'Belizean', 'name' => 'Dollar', 'code' => 'BZD', 'cents' => 'cents', 'symbol' => '$'],
            'BEN' => ['country' => 'Beninese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'BMU' => ['country' => 'Bermudian', 'name' => 'Dollar', 'code' => 'BMD', 'cents' => 'cents', 'symbol' => '$'],
            'BTN' => ['country' => 'Bhutanese', 'name' => 'Ngultrum', 'code' => 'BTN', 'cents' => 'chhertum (ཕྱེད་ཏམ)', 'symbol' => 'Nu.'],
            'BOL' => ['country' => 'Bolivian', 'name' => 'Boliviano', 'code' => 'BOB', 'cents' => 'centavo', 'symbol' => 'Bs.'],
            'BES' => ['country' => 'Bonairean', 'name' => 'US Dollar', 'code' => 'USD', 'cents' => 'cents', 'symbol' => '$'],
            'BIH' => ['country' => 'Bosnian', 'name' => 'Convertible Mark', 'code' => 'BAM', 'cents' => 'fening', 'symbol' => 'KM'],
            'BWA' => ['country' => 'Botswana', 'name' => 'Pula', 'code' => 'BWP', 'cents' => 'thebe', 'symbol' => 'P'],
            'BRA' => ['country' => 'Brazilian', 'name' => 'Real', 'code' => 'BRL', 'cents' => 'centavo', 'symbol' => 'R$'],
            'BRN' => ['country' => 'Bruneian', 'name' => 'Dollar', 'code' => 'BND', 'cents' => 'cents', 'symbol' => '$'],
            'BGR' => ['country' => 'Bulgarian', 'name' => 'Lev', 'code' => 'BGN', 'cents' => 'stotinki', 'symbol' => 'лв'],
            'BFA' => ['country' => 'Burkinese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'BDI' => ['country' => 'Burundian', 'name' => 'Burundi Franc', 'code' => 'BIF', 'cents' => 'centime', 'symbol' => 'FBu'],
            'CPV' => ['country' => 'Cape Verdean', 'name' => 'Escudo', 'code' => 'CVE', 'cents' => 'centavo', 'symbol' => '$'],
            'KHM' => ['country' => 'Cambodian', 'name' => 'Riel', 'code' => 'KHR', 'cents' => 'sen', 'symbol' => '៛'],
            'CMR' => ['country' => 'Cameroonian', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'CAN' => ['country' => 'Canadian', 'name' => 'Dollar', 'code' => 'CAD', 'cents' => 'cent', 'symbol' => '$'],
            'CAF' => ['country' => 'Central African', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'TCD' => ['country' => 'Chadian', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'CHL' => ['country' => 'Chilean', 'name' => 'Peso', 'code' => 'CLP', 'cents' => 'centavo', 'symbol' => '$'],
            'CHN' => ['country' => 'Chinese', 'name' => 'Yuan', 'code' => 'CNY', 'cents' => 'jiao (角)', 'symbol' => '¥'],
            'CXR' => ['country' => 'Christmas Island', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'CCK' => ['country' => 'Cocos Islands', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'COL' => ['country' => 'Colombian', 'name' => 'Peso', 'code' => 'COP', 'cents' => 'centavo', 'symbol' => '$'],
            'COM' => ['country' => 'Comorian', 'name' => 'Franc', 'code' => 'KMF', 'cents' => 'centime', 'symbol' => 'CF'],
            'COG' => ['country' => 'Congolese', 'name' => 'CFA Franc', 'code' => 'CFA', 'cents' => 'centime', 'symbol' => 'CFA'],
            'COD' => ['country' => 'Congolese (DRC)', 'name' => 'Franc', 'code' => 'CDF', 'cents' => 'centime', 'symbol' => 'FC'],
            'COK' => ['country' => 'Cook Islands', 'name' => 'Dollar', 'code' => 'NZD', 'cents' => 'cents', 'symbol' => '$'],
            'CRI' => ['country' => 'Costa Rican', 'name' => 'Colón', 'code' => 'CRC', 'cents' => 'céntimo', 'symbol' => '₡'],
            'CIV' => ['country' => 'Ivorian', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'HRV' => ['country' => 'Croatian', 'name' => 'Kuna', 'code' => 'HRK', 'cents' => 'lipa', 'symbol' => 'kn'],
            'CUB' => ['country' => 'Cuban', 'name' => 'Peso', 'code' => 'CUP', 'cents' => 'centavo', 'symbol' => '$'],
            'CYP' => ['country' => 'Cypriot', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'CZE' => ['country' => 'Czech', 'name' => 'Koruna', 'code' => 'CZK', 'cents' => 'haléř', 'symbol' => 'Kč'],
            'DMA' => ['country' => 'Dominican', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents', 'symbol' => '$'],
            'DOM' => ['country' => 'Dominican Republic', 'name' => 'Peso', 'code' => 'DOP', 'cents' => 'centavo', 'symbol' => '$'],
            'DJI' => ['country' => 'Djiboutian', 'name' => 'Franc', 'code' => 'DJF', 'cents' => 'centime', 'symbol' => 'Fdj'],
            'DNK' => ['country' => 'Danish', 'name' => 'Krone', 'code' => 'DKK', 'cents' => 'øre', 'symbol' => 'kr'],
            'ECU' => ['country' => 'Ecuadorian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'EGY' => ['country' => 'Egyptian', 'name' => 'Pound', 'code' => 'EGP', 'cents' => 'piastre', 'symbol' => '£'],
            'SLV' => ['country' => 'Salvadoran', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'centavos', 'symbol' => '$'],
            'GNQ' => ['country' => 'Equatorial Guinean', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'ERI' => ['country' => 'Eritrean', 'name' => 'Nakfa', 'code' => 'ERN', 'cents' => 'cents', 'symbol' => 'Nfk'],
            'EST' => ['country' => 'Estonian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'ETH' => ['country' => 'Ethiopian', 'name' => 'Birr', 'code' => 'ETB', 'cents' => 'cent', 'symbol' => 'Br'],
            'FRO' => ['country' => 'Faroese', 'name' => 'Króna', 'code' => 'DKK', 'cents' => 'øre', 'symbol' => 'kr'],
            'FIN' => ['country' => 'Finnish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'FJI' => ['country' => 'Fijian', 'name' => 'Dollar', 'code' => 'FJD', 'cents' => 'cents', 'symbol' => '$'],
            'FRA' => ['country' => 'French', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'GAB' => ['country' => 'Gabonese', 'name' => 'CFA Franc', 'code' => 'XAF', 'cents' => 'centime', 'symbol' => 'FCFA'],
            'GMB' => ['country' => 'Gambian', 'name' => 'Dalasi', 'code' => 'GMD', 'cents' => 'butut', 'symbol' => 'D'],
            'GEO' => ['country' => 'Georgian', 'name' => 'Lari', 'code' => 'GEL', 'cents' => 'tetri', 'symbol' => '₾'],
            'DEU' => ['country' => 'German', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'GHA' => ['country' => 'Ghanaian', 'name' => 'Cedi', 'code' => 'GHS', 'cents' => 'pesewa', 'symbol' => '₵'],
            'GRC' => ['country' => 'Greek', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cents', 'symbol' => '€'],
            'GRD' => ['country' => 'Grenadian', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cents', 'symbol' => '$'],
            'GUM' => ['country' => 'Guamanian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cents', 'symbol' => '$'],
            'GTM' => ['country' => 'Guatemalan', 'name' => 'Quetzal', 'code' => 'GTQ', 'cents' => 'centavo', 'symbol' => 'Q'],
            'GIN' => ['country' => 'Guinean', 'name' => 'Franc', 'code' => 'GNF', 'cents' => 'santim', 'symbol' => 'FG'],
            'GNB' => ['country' => 'Guinea-Bissauan', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'GUY' => ['country' => 'Guyanese', 'name' => 'Dollar', 'code' => 'GYD', 'cents' => 'cent', 'symbol' => '$'],
            'HTI' => ['country' => 'Haitian', 'name' => 'Gourde', 'code' => 'HTG', 'cents' => 'centime', 'symbol' => 'G'],
            'HKG' => ['country' => 'Hong Kong', 'name' => 'Dollar', 'code' => 'HKD', 'cents' => 'cent', 'symbol' => '$'],
            'HND' => ['country' => 'Honduran', 'name' => 'Lempira', 'code' => 'HNL', 'cents' => 'centavo', 'symbol' => 'L'],
            'HUN' => ['country' => 'Hungarian', 'name' => 'Forint', 'code' => 'HUF', 'cents' => 'filler', 'symbol' => 'Ft'],
            'ISL' => ['country' => 'Icelandic', 'name' => 'Króna', 'code' => 'ISK', 'cents' => 'aurar', 'symbol' => 'kr'],
            'IND' => ['country' => 'Indian', 'name' => 'Rupee', 'code' => 'INR', 'cents' => 'paisa', 'symbol' => '₹'],
            'IDN' => ['country' => 'Indonesian', 'name' => 'Rupiah', 'code' => 'IDR', 'cents' => 'sen', 'symbol' => 'Rp'],
            'IRN' => ['country' => 'Iranian', 'name' => 'Rial', 'code' => 'IRR', 'cents' => 'rial', 'symbol' => '﷼'],
            'IRQ' => ['country' => 'Iraqi', 'name' => 'Dinar', 'code' => 'IQD', 'cents' => 'fils', 'symbol' => 'ع.د'],
            'IRL' => ['country' => 'Irish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'ISR' => ['country' => 'Israeli', 'name' => 'New Shekel', 'code' => 'ILS', 'cents' => 'agora', 'symbol' => '₪'],
            'ITA' => ['country' => 'Italian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'JAM' => ['country' => 'Jamaican', 'name' => 'Dollar', 'code' => 'JMD', 'cents' => 'cent', 'symbol' => '$'],
            'JPN' => ['country' => 'Japanese', 'name' => 'Yen', 'code' => 'JPY', 'cents' => 'sen', 'symbol' => '¥'],
            'JOR' => ['country' => 'Jordanian', 'name' => 'Dinar', 'code' => 'JOD', 'cents' => 'piastre', 'symbol' => 'د.ا'],
            'KAZ' => ['country' => 'Kazakh', 'name' => 'Tenge', 'code' => 'KZT', 'cents' => 'tiyn', 'symbol' => '₸'],
            'KEN' => ['country' => 'Kenyan', 'name' => 'Shilling', 'code' => 'KES', 'cents' => 'cent', 'symbol' => 'KSh'],
            'KIR' => ['country' => 'Kiribati', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'PRK' => ['country' => 'North Korean', 'name' => 'Won', 'code' => 'KPW', 'cents' => 'chon', 'symbol' => '₩'],
            'KOR' => ['country' => 'South Korean', 'name' => 'Won', 'code' => 'KRW', 'cents' => 'jeon', 'symbol' => '₩'],
            'KWT' => ['country' => 'Kuwaiti', 'name' => 'Dinar', 'code' => 'KWD', 'cents' => 'fils', 'symbol' => 'د.ك'],
            'KGZ' => ['country' => 'Kyrgyz', 'name' => 'Som', 'code' => 'KGS', 'cents' => 'tyiyn', 'symbol' => 'с'],
            'LAO' => ['country' => 'Laotian', 'name' => 'Kip', 'code' => 'LAK', 'cents' => 'att', 'symbol' => '₭'],
            'LVA' => ['country' => 'Latvian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'LBN' => ['country' => 'Lebanese', 'name' => 'Pound', 'code' => 'LBP', 'cents' => 'piastre', 'symbol' => 'ل.ل'],
            'LSO' => ['country' => 'Lesotho', 'name' => 'Loti', 'code' => 'LSL', 'cents' => 'lisente', 'symbol' => 'L'],
            'LBR' => ['country' => 'Liberian', 'name' => 'Dollar', 'code' => 'LRD', 'cents' => 'cent', 'symbol' => '$'],
            'LBY' => ['country' => 'Libyan', 'name' => 'Dinar', 'code' => 'LYD', 'cents' => 'dirham', 'symbol' => 'ل.د'],
            'LIE' => ['country' => 'Liechtenstein', 'name' => 'Franc', 'code' => 'CHF', 'cents' => 'rappen', 'symbol' => 'CHF'],
            'LTU' => ['country' => 'Lithuanian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'LUX' => ['country' => 'Luxembourg', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'MKD' => ['country' => 'Macedonian', 'name' => 'Denar', 'code' => 'MKD', 'cents' => 'deni', 'symbol' => 'ден'],
            'MDG' => ['country' => 'Malagasy', 'name' => 'Ariary', 'code' => 'MGA', 'cents' => 'iraimbilanja', 'symbol' => 'Ar'],
            'MWI' => ['country' => 'Malawian', 'name' => 'Kwacha', 'code' => 'MWK', 'cents' => 'ngwee', 'symbol' => 'MK'],
            'MYS' => ['country' => 'Malaysian', 'name' => 'Ringgit', 'code' => 'MYR', 'cents' => 'sen', 'symbol' => 'RM'],
            'MDV' => ['country' => 'Maldivian', 'name' => 'Rufiyaa', 'code' => 'MVR', 'cents' => 'laari', 'symbol' => 'Rf'],
            'MLI' => ['country' => 'Malian', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'MLT' => ['country' => 'Maltese', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'MHL' => ['country' => 'Marshall Islands', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'MRT' => ['country' => 'Mauritanian', 'name' => 'Ouguiya', 'code' => 'MRU', 'cents' => 'Khoums', 'symbol' => 'UM'],
            'MUS' => ['country' => 'Mauritian', 'name' => 'Rupee', 'code' => 'MUR', 'cents' => 'Cent', 'symbol' => '₨'],
            'MEX' => ['country' => 'Mexican', 'name' => 'Peso', 'code' => 'MXN', 'cents' => 'centavo', 'symbol' => '$'],
            'FSM' => ['country' => 'Micronesian', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'MDA' => ['country' => 'Moldovan', 'name' => 'Leu', 'code' => 'MDL', 'cents' => 'bani', 'symbol' => 'L'],
            'MCO' => ['country' => 'Monacan', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'MNG' => ['country' => 'Mongolian', 'name' => 'Tugrik', 'code' => 'MNT', 'cents' => 'möngö', 'symbol' => '₮'],
            'MNE' => ['country' => 'Montenegrin', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'MAR' => ['country' => 'Moroccan', 'name' => 'Dirham', 'code' => 'MAD', 'cents' => 'Centime', 'symbol' => 'د.م.'],
            'MOZ' => ['country' => 'Mozambican', 'name' => 'Metical', 'code' => 'MZN', 'cents' => 'centavo', 'symbol' => 'MT'],
            'MMR' => ['country' => 'Burmese', 'name' => 'Kyat', 'code' => 'MMK', 'cents' => 'pya', 'symbol' => 'K'],
            'NAM' => ['country' => 'Namibian', 'name' => 'Dollar', 'code' => 'NAD', 'cents' => 'cents', 'symbol' => '$'],
            'NRU' => ['country' => 'Nauruan', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'NPL' => ['country' => 'Nepali', 'name' => 'Rupee', 'code' => 'NPR', 'cents' => 'paisa', 'symbol' => '₨'],
            'NLD' => ['country' => 'Dutch', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'NCL' => ['country' => 'New Caledonian', 'name' => 'CFA Franc', 'code' => 'XPF', 'cents' => 'centime', 'symbol' => '₣'],
            'NZL' => ['country' => 'New Zealand', 'name' => 'Dollar', 'code' => 'NZD', 'cents' => 'cents', 'symbol' => '$'],
            'NIC' => ['country' => 'Nicaraguan', 'name' => 'Córdoba', 'code' => 'NIO', 'cents' => 'centavo', 'symbol' => 'C$'],
            'NER' => ['country' => 'Nigerien', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'NGA' => ['country' => 'Nigerian', 'name' => 'Naira', 'code' => 'NGN', 'cents' => 'kobo', 'symbol' => '₦'],
            'NOR' => ['country' => 'Norwegian', 'name' => 'Krone', 'code' => 'NOK', 'cents' => 'øre', 'symbol' => 'kr'],
            'OMN' => ['country' => 'Omani', 'name' => 'Rial', 'code' => 'OMR', 'cents' => 'Baisa', 'symbol' => 'ر.ع.'],
            'NVA' => ['country' => 'Navajo Nation', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'PAK' => ['country' => 'Pakistani', 'name' => 'Rupee', 'code' => 'PKR', 'cents' => 'paisa', 'symbol' => '₨'],
            'PLW' => ['country' => 'Palauan', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'PAN' => ['country' => 'Panamanian', 'name' => 'Balboa', 'code' => 'PAB', 'cents' => 'centavo', 'symbol' => 'B/.'],
            'PNG' => ['country' => 'Papua New Guinea', 'name' => 'Kina', 'code' => 'PGK', 'cents' => 'toea', 'symbol' => 'K'],
            'PRY' => ['country' => 'Paraguayan', 'name' => 'Guarani', 'code' => 'PYG', 'cents' => 'Céntimo', 'symbol' => '₲'],
            'PER' => ['country' => 'Peruvian', 'name' => 'Nuevo Sol', 'code' => 'PEN', 'cents' => 'Céntimo', 'symbol' => 'S/.'],
            'PHL' => ['country' => 'Philippine', 'name' => 'Peso', 'code' => 'PHP', 'cents' => 'Sentimo', 'symbol' => '₱'],
            'POL' => ['country' => 'Polish', 'name' => 'Złoty', 'code' => 'PLN', 'cents' => 'Grosz', 'symbol' => 'zł'],
            'PRT' => ['country' => 'Portuguese', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'QAT' => ['country' => 'Qatari', 'name' => 'Riyal', 'code' => 'QAR', 'cents' => 'dirham', 'symbol' => 'ر.ق'],
            'ROU' => ['country' => 'Romanian', 'name' => 'Leu', 'code' => 'RON', 'cents' => 'bani', 'symbol' => 'lei'],
            'RUS' => ['country' => 'Russian', 'name' => 'Ruble', 'code' => 'RUB', 'cents' => 'kopeck', 'symbol' => '₽'],
            'RWA' => ['country' => 'Rwandan', 'name' => 'Franc', 'code' => 'RWF', 'cents' => 'centime', 'symbol' => 'FRw'],
            'WSM' => ['country' => 'Samoan', 'name' => 'Tala', 'code' => 'WST', 'cents' => 'Sene', 'symbol' => 'WS$'],
            'KNA' => ['country' => 'Saint Kitts and Nevis', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cent', 'symbol' => '$'],
            'VCT' => ['country' => 'Saint Vincent and the Grenadines', 'name' => 'Dollar', 'code' => 'XCD', 'cents' => 'cent', 'symbol' => '$'],
            'SMR' => ['country' => 'San Marino', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'STP' => ['country' => 'São Toméan', 'name' => 'Dobra', 'code' => 'STN', 'cents' => 'cent', 'symbol' => 'Db'],
            'SAU' => ['country' => 'Saudi', 'name' => 'Riyal', 'code' => 'SAR', 'cents' => 'halala', 'symbol' => 'ر.س'],
            'SEN' => ['country' => 'Senegalese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'SRB' => ['country' => 'Serbian', 'name' => 'Dinar', 'code' => 'RSD', 'cents' => 'Para', 'symbol' => 'дин.'],
            'SYC' => ['country' => 'Seychellois', 'name' => 'Rupee', 'code' => 'SCR', 'cents' => 'cent', 'symbol' => '₨'],
            'SLE' => ['country' => 'Sierra Leonean', 'name' => 'Leone', 'code' => 'SLL', 'cents' => 'cent', 'symbol' => 'Le'],
            'SGP' => ['country' => 'Singaporean', 'name' => 'Dollar', 'code' => 'SGD', 'cents' => 'cent', 'symbol' => '$'],
            'SVK' => ['country' => 'Slovak', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'SVN' => ['country' => 'Slovenian', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'SLB' => ['country' => 'Solomon Islands', 'name' => 'Dollar', 'code' => 'SBD', 'cents' => 'cent', 'symbol' => '$'],
            'SOM' => ['country' => 'Somali', 'name' => 'Shilling', 'code' => 'SOS', 'cents' => 'cent', 'symbol' => 'S'],
            'ZAF' => ['country' => 'South African', 'name' => 'Rand', 'code' => 'ZAR', 'cents' => 'cent', 'symbol' => 'R'],
            'SSD' => ['country' => 'South Sudanese', 'name' => 'Pound', 'code' => 'SSP', 'cents' => 'Piaster', 'symbol' => '£'],
            'SDN' => ['country' => 'Sudanese', 'name' => 'Pound', 'code' => 'SDG', 'cents' => 'Piastre', 'symbol' => '£'],
            'ESP' => ['country' => 'Spanish', 'name' => 'Euro', 'code' => 'EUR', 'cents' => 'cent', 'symbol' => '€'],
            'LKA' => ['country' => 'Sri Lankan', 'name' => 'Rupee', 'code' => 'LKR', 'cents' => 'Cent', 'symbol' => '₨'],
            'SUR' => ['country' => 'Surinamese', 'name' => 'Dollar', 'code' => 'SRD', 'cents' => 'cent', 'symbol' => '$'],
            'SWZ' => ['country' => 'Swazi', 'name' => 'Lilangeni', 'code' => 'SZL', 'cents' => 'cent', 'symbol' => 'E'],
            'SWE' => ['country' => 'Swedish', 'name' => 'Krona', 'code' => 'SEK', 'cents' => 'öre', 'symbol' => 'kr'],
            'CHE' => ['country' => 'Swiss', 'name' => 'Franc', 'code' => 'CHF', 'cents' => 'rappen', 'symbol' => 'CHF'],
            'SYR' => ['country' => 'Syrian', 'name' => 'Pound', 'code' => 'SYP', 'cents' => 'piastre', 'symbol' => '£'],
            'TWN' => ['country' => 'Taiwanese', 'name' => 'Dollar', 'code' => 'TWD', 'cents' => 'cent', 'symbol' => 'NT$'],
            'TJK' => ['country' => 'Tajik', 'name' => 'Somoni', 'code' => 'TJS', 'cents' => 'diram', 'symbol' => 'ЅМ'],
            'TZA' => ['country' => 'Tanzanian', 'name' => 'Shilling', 'code' => 'TZS', 'cents' => 'Cent', 'symbol' => 'TSh'],
            'THA' => ['country' => 'Thai', 'name' => 'Baht', 'code' => 'THB', 'cents' => 'Satang', 'symbol' => '฿'],
            'TGO' => ['country' => 'Togolese', 'name' => 'CFA Franc', 'code' => 'XOF', 'cents' => 'centime', 'symbol' => 'CFA'],
            'TON' => ['country' => 'Tongan', 'name' => 'Paʻanga', 'code' => 'TOP', 'cents' => 'seniti', 'symbol' => 'T$'],
            'TTO' => ['country' => 'Trinidad and Tobagonian', 'name' => 'Dollar', 'code' => 'TTD', 'cents' => 'Cent', 'symbol' => 'TT$'],
            'TUN' => ['country' => 'Tunisian', 'name' => 'Dinar', 'code' => 'TND', 'cents' => 'Millime', 'symbol' => 'د.ت'],
            'TUR' => ['country' => 'Turkish', 'name' => 'Lira', 'code' => 'TRY', 'cents' => 'kuruş', 'symbol' => '₺'],
            'TKM' => ['country' => 'Turkmen', 'name' => 'Manat', 'code' => 'TMT', 'cents' => 'tenge', 'symbol' => 'm'],
            'TUV' => ['country' => 'Tuvaluan', 'name' => 'Dollar', 'code' => 'AUD', 'cents' => 'cents', 'symbol' => '$'],
            'UGA' => ['country' => 'Ugandan', 'name' => 'Shilling', 'code' => 'UGX', 'cents' => 'cent', 'symbol' => 'USh'],
            'UKR' => ['country' => 'Ukrainian', 'name' => 'Hryvnia', 'code' => 'UAH', 'cents' => 'kopiyka', 'symbol' => '₴'],
            'ARE' => ['country' => 'Emirati', 'name' => 'Dirham', 'code' => 'AED', 'cents' => 'fils', 'symbol' => 'د.إ'],
            'GBR' => ['country' => 'British', 'name' => 'Pound', 'code' => 'GBP', 'cents' => 'pence', 'symbol' => '£'],
            'USA' => ['country' => 'United States', 'name' => 'Dollar', 'code' => 'USD', 'cents' => 'cent', 'symbol' => '$'],
            'URY' => ['country' => 'Uruguayan', 'name' => 'Peso', 'code' => 'UYU', 'cents' => 'centésimo', 'symbol' => '$'],
            'UZB' => ['country' => 'Uzbekistani', 'name' => 'Som', 'code' => 'UZS', 'cents' => 'tiyin', 'symbol' => 'лв'],
            'VUT' => ['country' => 'Vanuatu', 'name' => 'Vatu', 'code' => 'VUV', 'cents' => 'cent', 'symbol' => 'VT'],
            'VEN' => ['country' => 'Venezuelan', 'name' => 'Bolívar', 'code' => 'VES', 'cents' => 'centimo', 'symbol' => 'Bs.'],
            'VNM' => ['country' => 'Vietnamese', 'name' => 'Dong', 'code' => 'VND', 'cents' => 'hào', 'symbol' => '₫'],
            'WLF' => ['country' => 'Wallis and Futuna', 'name' => 'CFP Franc', 'code' => 'CFP', 'cents' => 'centime', 'symbol' => '₣'],
            'YEM' => ['country' => 'Yemeni', 'name' => 'Rial', 'code' => 'YER', 'cents' => 'fils', 'symbol' => '﷼'],
            'ZMB' => ['country' => 'Zambian', 'name' => 'Kwacha', 'code' => 'ZMW', 'cents' => 'ngwee', 'symbol' => 'ZK'],
            'ZWE' => ['country' => 'Zimbabwean', 'name' => 'Dollar', 'code' => 'ZWD', 'cents' => 'cent', 'symbol' => '$'],
        ];

        return $array[$iso3] ?? $array;
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
            'iso', 'iso3', 'locale' => '__iso',
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
