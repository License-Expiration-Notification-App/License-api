<?php
function scoreInPercentage($numerator, $denominator)
{
    if ($denominator > 0) {
        return ($numerator / $denominator) * 100;
    }
    return 0;
}
function rainbowColor($color)
{
    $arr = [
        "1" =>    "bg-blue",
        "2" =>    "bg-orange",
        "3" =>    "bg-green",
        "4" =>    "bg-red",
        "5" =>    "bg-yellow",
        "6" =>    "bg-brown",
        "7" =>    "bg-pink",

    ];

    if ($color) {
        return $arr[$color];
    }
    return $arr;
}

/**
 * Delete Message
 * @return String
 */
function formatToTwoDecimalPlaces($gpa)
{
    return sprintf("%01.2f", $gpa);
}

function deleteMessage()
{
    return 'yes';
}
/**
 * @param null $status
 * @return array|mixed
 */
function status($status = null)
{
    $arr = [
        0 => 'De-active',
        1 => 'Active'
    ];
    if ($status !== null) {
        return $arr[$status];
    }
    return $arr;
}

function feeFecurrence($status = null)
{
    $arr = [
        'Termly' => 'Every Term',
        'Once Per Session' => 'Once Per Session'

    ];
    if ($status !== null) {
        return $arr[$status];
    }
    return $arr;
}


function countries()
{
    return  array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
}

function defaultPasswordStatus()
{
    return 'default';
}

function todayDateTime()
{
    return date('Y-m-d H:i:s', strtotime('now'));
}

function todayDate()
{
    return date('Y-m-d', strtotime('now'));
}

function getDateFormat($dateTime)
{
    return date('Y-m-d', strtotime($dateTime));
}

function getDateFormatWords($dateTime)
{
    return date('l M d, Y', strtotime($dateTime));
}

function fromDate()
{
    return date('Y-m-d' . ' 07:30:00', time());
}

function toDate()
{
    return date('Y-m-d' . ' 16:00:00', time());
}
function deleteSingleElementFromString($parent_string, $child_string)
{
    $string_array = explode('~', $parent_string);

    $count_array = count($string_array);

    for ($i = 0; $i < ($count_array); $i++) {

        if ($string_array[$i] == $child_string) {

            unset($string_array[$i]);
        }
    }
    return implode('~', array_unique($string_array));
}
function addSingleElementToString($parent_string, $child_string)
{
    if ($parent_string == '') {
        $str =  $child_string;
    } else {
        $str =  $parent_string . '~' . $child_string;
    }


    $string_array = array_unique(explode('~', $str));

    return implode('~', $string_array);
}

function randomColorCode()
{
    $tokens = 'ABC0123456789'; //'ABCDEF0123456789';
    $serial = '';
    for ($i = 0; $i < 6; $i++) {
        $serial .= $tokens[mt_rand(0, strlen($tokens) - 1)];
    }
    return '#' . $serial;
}
function randomPassword()
{
    $tokens = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789!@#$%&*{}[]';
    $serial = '';
    for ($i = 0; $i < 3; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $serial .= $tokens[mt_rand(0, strlen($tokens) - 1)];
        }
        // if ($i < 2) {
        //     $serial .= '-';
        // }
    }
    return $serial;
}
function randomNumber()
{
    $tokens = '0123456789';
    $serial = '';
    for ($j = 0; $j < 6; $j++) {
        $serial .= $tokens[mt_rand(0, strlen($tokens) - 1)];
    }
    return $serial;
}
function randomcode()
{
    $tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';
    $serial = '';
    for ($i = 0; $i < 3; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $serial .= $tokens[mt_rand(0, strlen($tokens) - 1)];
        }
        if ($i < 2) {
            $serial .= '-';
        }
    }
    return $serial;
}
function gender($gender = null)
{
    $arr = [
        'Male'   => 'Male',
        'Female' => 'Female',


    ];

    if ($gender) {
        return $arr[$gender];
    }
    return $arr;
}

function sections($name = null)
{
    $arr = [
        'A'   => 'A',
        'B'   => 'B',
        'C'   => 'C',
        'D'   => 'D',
        'E'   => 'E',
        'F'   => 'F'



    ];

    if ($name) {
        return $arr[$name];
    }
    return $arr;
}

function hashing($string)
{
    $hash = hash('sha512', $string);
    return $hash;
}

function formatUniqNo($no)
{
    $no = $no * 1;
    if ($no < 10) {
        return '000' . $no;
    } else if ($no >= 10 && $no < 100) {
        return '00' . $no;
    } else if ($no >= 100 && $no < 1000) {
        return '0' . $no;
    } else {
        return $no;
    }
}
function mainDomainPublicPath($folder = null)
{
    return "https://edu-drive.com/" . $folder;
}

function portalPulicPath($folder = null)
{
    // return storage_path('app/public/' . $folder);
    return "/home/edudrive/license.edu-drive.com/storage/" . $folder;
}

function folderSize($dir)
{
    $size = 0;

    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }

    // this size is in Byte
    // we want to convert it to GB
    // 1Gb = 1024 ^ 3 Bytes OR 1Gb = 2 ^ 30

    return $size;
    // return sizeFilter($size); //byteToGB($size);
}

function byteToGB($byte)
{
    $gb =  $byte / 1024 / 1024 / 1024;
    return $gb;
}

function percentageDirUsage($dir_size, $total_usable)
{
    $used =  $dir_size / $total_usable * 100;
    return (float) sprintf('%01.2f', $used);
}
function folderSizeFilter($bytes)
{
    $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

    for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

    return (round($bytes, 2) . $label[$i]);
}
