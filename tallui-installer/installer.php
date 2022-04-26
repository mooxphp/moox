<?php

/**
 * ******************************************************************
 * ************************ TALLUI Installer ************************
 * 
 * @see https://tallui.io/docs/install
 * @version 0.1
 * @author alf.drollinger@tallui.io
 * 
 * */

session_start();

/* Settings */

$base_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];
$current_url = $base_url . $_SERVER["REQUEST_URI"];
$plain_url = $base_url . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if (isset($_GET['step'])) {
    $current_step = $_GET['step'];
    $next_step = $current_step + 1;
} else {
    $current_step = 1;
    $next_step = 2;
}

$steps = [
    [1, "PROJECT SETTINGS", "Enter name, claim, URLs, language and timezone.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/H9GZgI6jU7Y", 'Photo by <a href="https://unsplash.com/@ricsard">Richard Balog</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
    [2, "ADMIN USER", "Enter login and e-mail for the admin user.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/gblpm9PBrO4", 'Photo by <a href="https://unsplash.com/@jan_culturelab">Jan KIM</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
    [3, "DATABASE CONFIG", "Select and enter the database credentials.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/DFtjXYd5Pto", 'Photo by <a href="https://unsplash.com/@jblesly">Lesly Juarez</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
    [4, "SELECT PACKAGE", "Select version, bundle and theme.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/Vc1pJfvoQvY", 'Photo by <a href="https://unsplash.com/@drew_beamer">Drew Beamer</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
    [5, "EXTENSIONS AND COMPONENTS", "Select extensions and components.", "from-white/30 to-cyan-400/70", 100, "https://source.unsplash.com/kmz39UAtKZ0", 'Photo by <a href="https://unsplash.com/@norevisions">No Revisions</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-700"],
    [6, "DEMO CONTENT", "Add demo data like pages, posts and products.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/ukzHlkoz1IE", 'Photo by <a href="https://unsplash.com/@austinchan">Austin Chan</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
    [7, "SETTINGS", "Configure TALLUI to your needs.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/Y_LgXwQEx2c", 'Photo by <a href="https://unsplash.com/@mbaumi">Mika Baumeister</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-700"],
    [8, "DESIGN", "Finally adjust basic design, font and colors.", "from-white/90 to-cyan-400/50", 100, "https://source.unsplash.com/fv6BZ7MRjlY", 'Photo by <a href="https://unsplash.com/@bridgetbart">Bridget Bartos</a> on <a href="https://unsplash.com/">Unsplash</a>', "text-zinc-200"],
];

$languages = array(
    'af' => 'Afrikaans',
    'sq' => 'Albanian - shqip',
    'am' => 'Amharic - አማርኛ',
    'ar' => 'Arabic - العربية',
    'an' => 'Aragonese - aragonés',
    'hy' => 'Armenian - հայերեն',
    'ast' => 'Asturian - asturianu',
    'az' => 'Azerbaijani - azərbaycan dili',
    'eu' => 'Basque - euskara',
    'be' => 'Belarusian - беларуская',
    'bn' => 'Bengali - বাংলা',
    'bs' => 'Bosnian - bosanski',
    'br' => 'Breton - brezhoneg',
    'bg' => 'Bulgarian - български',
    'ca' => 'Catalan - català',
    'ckb' => 'Central Kurdish - کوردی (دەستنوسی عەرەبی)',
    'zh' => 'Chinese - 中文',
    'zh-HK' => 'Chinese (Hong Kong) - 中文（香港）',
    'zh-CN' => 'Chinese (Simplified) - 中文（简体）',
    'zh-TW' => 'Chinese (Traditional) - 中文（繁體）',
    'co' => 'Corsican',
    'hr' => 'Croatian - hrvatski',
    'cs' => 'Czech - čeština',
    'da' => 'Danish - dansk',
    'nl' => 'Dutch - Nederlands',
    'en' => 'English',
    'en-AU' => 'English (Australia)',
    'en-CA' => 'English (Canada)',
    'en-IN' => 'English (India)',
    'en-NZ' => 'English (New Zealand)',
    'en-ZA' => 'English (South Africa)',
    'en-GB' => 'English (United Kingdom)',
    'en-US' => 'English (United States)',
    'eo' => 'Esperanto - esperanto',
    'et' => 'Estonian - eesti',
    'fo' => 'Faroese - føroyskt',
    'fil' => 'Filipino',
    'fi' => 'Finnish - suomi',
    'fr' => 'French - français',
    'fr-CA' => 'French (Canada) - français (Canada)',
    'fr-FR' => 'French (France) - français (France)',
    'fr-CH' => 'French (Switzerland) - français (Suisse)',
    'gl' => 'Galician - galego',
    'ka' => 'Georgian - ქართული',
    'de' => 'German - Deutsch',
    'de-AT' => 'German (Austria) - Deutsch (Österreich)',
    'de-DE' => 'German (Germany) - Deutsch (Deutschland)',
    'de-LI' => 'German (Liechtenstein) - Deutsch (Liechtenstein)',
    'de-CH' => 'German (Switzerland) - Deutsch (Schweiz)',
    'el' => 'Greek - Ελληνικά',
    'gn' => 'Guarani',
    'gu' => 'Gujarati - ગુજરાતી',
    'ha' => 'Hausa',
    'haw' => 'Hawaiian - ʻŌlelo Hawaiʻi',
    'he' => 'Hebrew - עברית',
    'hi' => 'Hindi - हिन्दी',
    'hu' => 'Hungarian - magyar',
    'is' => 'Icelandic - íslenska',
    'id' => 'Indonesian - Indonesia',
    'ia' => 'Interlingua',
    'ga' => 'Irish - Gaeilge',
    'it' => 'Italian - italiano',
    'it-IT' => 'Italian (Italy) - italiano (Italia)',
    'it-CH' => 'Italian (Switzerland) - italiano (Svizzera)',
    'ja' => 'Japanese - 日本語',
    'kn' => 'Kannada - ಕನ್ನಡ',
    'kk' => 'Kazakh - қазақ тілі',
    'km' => 'Khmer - ខ្មែរ',
    'ko' => 'Korean - 한국어',
    'ku' => 'Kurdish - Kurdî',
    'ky' => 'Kyrgyz - кыргызча',
    'lo' => 'Lao - ລາວ',
    'la' => 'Latin',
    'lv' => 'Latvian - latviešu',
    'ln' => 'Lingala - lingála',
    'lt' => 'Lithuanian - lietuvių',
    'mk' => 'Macedonian - македонски',
    'ms' => 'Malay - Bahasa Melayu',
    'ml' => 'Malayalam - മലയാളം',
    'mt' => 'Maltese - Malti',
    'mr' => 'Marathi - मराठी',
    'mn' => 'Mongolian - монгол',
    'ne' => 'Nepali - नेपाली',
    'no' => 'Norwegian - norsk',
    'nb' => 'Norwegian Bokmål - norsk bokmål',
    'nn' => 'Norwegian Nynorsk - nynorsk',
    'oc' => 'Occitan',
    'or' => 'Oriya - ଓଡ଼ିଆ',
    'om' => 'Oromo - Oromoo',
    'ps' => 'Pashto - پښتو',
    'fa' => 'Persian - فارسی',
    'pl' => 'Polish - polski',
    'pt' => 'Portuguese - português',
    'pt-BR' => 'Portuguese (Brazil) - português (Brasil)',
    'pt-PT' => 'Portuguese (Portugal) - português (Portugal)',
    'pa' => 'Punjabi - ਪੰਜਾਬੀ',
    'qu' => 'Quechua',
    'ro' => 'Romanian - română',
    'mo' => 'Romanian (Moldova) - română (Moldova)',
    'rm' => 'Romansh - rumantsch',
    'ru' => 'Russian - русский',
    'gd' => 'Scottish Gaelic',
    'sr' => 'Serbian - српски',
    'sh' => 'Serbo-Croatian - Srpskohrvatski',
    'sn' => 'Shona - chiShona',
    'sd' => 'Sindhi',
    'si' => 'Sinhala - සිංහල',
    'sk' => 'Slovak - slovenčina',
    'sl' => 'Slovenian - slovenščina',
    'so' => 'Somali - Soomaali',
    'st' => 'Southern Sotho',
    'es' => 'Spanish - español',
    'es-AR' => 'Spanish (Argentina) - español (Argentina)',
    'es-419' => 'Spanish (Latin America) - español (Latinoamérica)',
    'es-MX' => 'Spanish (Mexico) - español (México)',
    'es-ES' => 'Spanish (Spain) - español (España)',
    'es-US' => 'Spanish (United States) - español (Estados Unidos)',
    'su' => 'Sundanese',
    'sw' => 'Swahili - Kiswahili',
    'sv' => 'Swedish - svenska',
    'tg' => 'Tajik - тоҷикӣ',
    'ta' => 'Tamil - தமிழ்',
    'tt' => 'Tatar',
    'te' => 'Telugu - తెలుగు',
    'th' => 'Thai - ไทย',
    'ti' => 'Tigrinya - ትግርኛ',
    'to' => 'Tongan - lea fakatonga',
    'tr' => 'Turkish - Türkçe',
    'tk' => 'Turkmen',
    'tw' => 'Twi',
    'uk' => 'Ukrainian - українська',
    'ur' => 'Urdu - اردو',
    'ug' => 'Uyghur',
    'uz' => 'Uzbek - o‘zbek',
    'vi' => 'Vietnamese - Tiếng Việt',
    'wa' => 'Walloon - wa',
    'cy' => 'Welsh - Cymraeg',
    'fy' => 'Western Frisian',
    'xh' => 'Xhosa',
    'yi' => 'Yiddish',
    'yo' => 'Yoruba - Èdè Yorùbá',
    'zu' => 'Zulu - isiZulu'
);

$timezones = array(
  'America/Adak' => '(GMT-10:00) America/Adak (Hawaii-Aleutian Standard Time)',
  'America/Atka' => '(GMT-10:00) America/Atka (Hawaii-Aleutian Standard Time)',
  'America/Anchorage' => '(GMT-9:00) America/Anchorage (Alaska Standard Time)',
  'America/Juneau' => '(GMT-9:00) America/Juneau (Alaska Standard Time)',
  'America/Nome' => '(GMT-9:00) America/Nome (Alaska Standard Time)',
  'America/Yakutat' => '(GMT-9:00) America/Yakutat (Alaska Standard Time)',
  'America/Dawson' => '(GMT-8:00) America/Dawson (Pacific Standard Time)',
  'America/Ensenada' => '(GMT-8:00) America/Ensenada (Pacific Standard Time)',
  'America/Los_Angeles' => '(GMT-8:00) America/Los_Angeles (Pacific Standard Time)',
  'America/Tijuana' => '(GMT-8:00) America/Tijuana (Pacific Standard Time)',
  'America/Vancouver' => '(GMT-8:00) America/Vancouver (Pacific Standard Time)',
  'America/Whitehorse' => '(GMT-8:00) America/Whitehorse (Pacific Standard Time)',
  'Canada/Pacific' => '(GMT-8:00) Canada/Pacific (Pacific Standard Time)',
  'Canada/Yukon' => '(GMT-8:00) Canada/Yukon (Pacific Standard Time)',
  'Mexico/BajaNorte' => '(GMT-8:00) Mexico/BajaNorte (Pacific Standard Time)',
  'America/Boise' => '(GMT-7:00) America/Boise (Mountain Standard Time)',
  'America/Cambridge_Bay' => '(GMT-7:00) America/Cambridge_Bay (Mountain Standard Time)',
  'America/Chihuahua' => '(GMT-7:00) America/Chihuahua (Mountain Standard Time)',
  'America/Dawson_Creek' => '(GMT-7:00) America/Dawson_Creek (Mountain Standard Time)',
  'America/Denver' => '(GMT-7:00) America/Denver (Mountain Standard Time)',
  'America/Edmonton' => '(GMT-7:00) America/Edmonton (Mountain Standard Time)',
  'America/Hermosillo' => '(GMT-7:00) America/Hermosillo (Mountain Standard Time)',
  'America/Inuvik' => '(GMT-7:00) America/Inuvik (Mountain Standard Time)',
  'America/Mazatlan' => '(GMT-7:00) America/Mazatlan (Mountain Standard Time)',
  'America/Phoenix' => '(GMT-7:00) America/Phoenix (Mountain Standard Time)',
  'America/Shiprock' => '(GMT-7:00) America/Shiprock (Mountain Standard Time)',
  'America/Yellowknife' => '(GMT-7:00) America/Yellowknife (Mountain Standard Time)',
  'Canada/Mountain' => '(GMT-7:00) Canada/Mountain (Mountain Standard Time)',
  'Mexico/BajaSur' => '(GMT-7:00) Mexico/BajaSur (Mountain Standard Time)',
  'America/Belize' => '(GMT-6:00) America/Belize (Central Standard Time)',
  'America/Cancun' => '(GMT-6:00) America/Cancun (Central Standard Time)',
  'America/Chicago' => '(GMT-6:00) America/Chicago (Central Standard Time)',
  'America/Costa_Rica' => '(GMT-6:00) America/Costa_Rica (Central Standard Time)',
  'America/El_Salvador' => '(GMT-6:00) America/El_Salvador (Central Standard Time)',
  'America/Guatemala' => '(GMT-6:00) America/Guatemala (Central Standard Time)',
  'America/Knox_IN' => '(GMT-6:00) America/Knox_IN (Central Standard Time)',
  'America/Managua' => '(GMT-6:00) America/Managua (Central Standard Time)',
  'America/Menominee' => '(GMT-6:00) America/Menominee (Central Standard Time)',
  'America/Merida' => '(GMT-6:00) America/Merida (Central Standard Time)',
  'America/Mexico_City' => '(GMT-6:00) America/Mexico_City (Central Standard Time)',
  'America/Monterrey' => '(GMT-6:00) America/Monterrey (Central Standard Time)',
  'America/Rainy_River' => '(GMT-6:00) America/Rainy_River (Central Standard Time)',
  'America/Rankin_Inlet' => '(GMT-6:00) America/Rankin_Inlet (Central Standard Time)',
  'America/Regina' => '(GMT-6:00) America/Regina (Central Standard Time)',
  'America/Swift_Current' => '(GMT-6:00) America/Swift_Current (Central Standard Time)',
  'America/Tegucigalpa' => '(GMT-6:00) America/Tegucigalpa (Central Standard Time)',
  'America/Winnipeg' => '(GMT-6:00) America/Winnipeg (Central Standard Time)',
  'Canada/Central' => '(GMT-6:00) Canada/Central (Central Standard Time)',
  'Canada/East-Saskatchewan' => '(GMT-6:00) Canada/East-Saskatchewan (Central Standard Time)',
  'Canada/Saskatchewan' => '(GMT-6:00) Canada/Saskatchewan (Central Standard Time)',
  'Chile/EasterIsland' => '(GMT-6:00) Chile/EasterIsland (Easter Is. Time)',
  'Mexico/General' => '(GMT-6:00) Mexico/General (Central Standard Time)',
  'America/Atikokan' => '(GMT-5:00) America/Atikokan (Eastern Standard Time)',
  'America/Bogota' => '(GMT-5:00) America/Bogota (Colombia Time)',
  'America/Cayman' => '(GMT-5:00) America/Cayman (Eastern Standard Time)',
  'America/Coral_Harbour' => '(GMT-5:00) America/Coral_Harbour (Eastern Standard Time)',
  'America/Detroit' => '(GMT-5:00) America/Detroit (Eastern Standard Time)',
  'America/Fort_Wayne' => '(GMT-5:00) America/Fort_Wayne (Eastern Standard Time)',
  'America/Grand_Turk' => '(GMT-5:00) America/Grand_Turk (Eastern Standard Time)',
  'America/Guayaquil' => '(GMT-5:00) America/Guayaquil (Ecuador Time)',
  'America/Havana' => '(GMT-5:00) America/Havana (Cuba Standard Time)',
  'America/Indianapolis' => '(GMT-5:00) America/Indianapolis (Eastern Standard Time)',
  'America/Iqaluit' => '(GMT-5:00) America/Iqaluit (Eastern Standard Time)',
  'America/Jamaica' => '(GMT-5:00) America/Jamaica (Eastern Standard Time)',
  'America/Lima' => '(GMT-5:00) America/Lima (Peru Time)',
  'America/Louisville' => '(GMT-5:00) America/Louisville (Eastern Standard Time)',
  'America/Montreal' => '(GMT-5:00) America/Montreal (Eastern Standard Time)',
  'America/Nassau' => '(GMT-5:00) America/Nassau (Eastern Standard Time)',
  'America/New_York' => '(GMT-5:00) America/New_York (Eastern Standard Time)',
  'America/Nipigon' => '(GMT-5:00) America/Nipigon (Eastern Standard Time)',
  'America/Panama' => '(GMT-5:00) America/Panama (Eastern Standard Time)',
  'America/Pangnirtung' => '(GMT-5:00) America/Pangnirtung (Eastern Standard Time)',
  'America/Port-au-Prince' => '(GMT-5:00) America/Port-au-Prince (Eastern Standard Time)',
  'America/Resolute' => '(GMT-5:00) America/Resolute (Eastern Standard Time)',
  'America/Thunder_Bay' => '(GMT-5:00) America/Thunder_Bay (Eastern Standard Time)',
  'America/Toronto' => '(GMT-5:00) America/Toronto (Eastern Standard Time)',
  'Canada/Eastern' => '(GMT-5:00) Canada/Eastern (Eastern Standard Time)',
  'America/Caracas' => '(GMT-4:-30) America/Caracas (Venezuela Time)',
  'America/Anguilla' => '(GMT-4:00) America/Anguilla (Atlantic Standard Time)',
  'America/Antigua' => '(GMT-4:00) America/Antigua (Atlantic Standard Time)',
  'America/Aruba' => '(GMT-4:00) America/Aruba (Atlantic Standard Time)',
  'America/Asuncion' => '(GMT-4:00) America/Asuncion (Paraguay Time)',
  'America/Barbados' => '(GMT-4:00) America/Barbados (Atlantic Standard Time)',
  'America/Blanc-Sablon' => '(GMT-4:00) America/Blanc-Sablon (Atlantic Standard Time)',
  'America/Boa_Vista' => '(GMT-4:00) America/Boa_Vista (Amazon Time)',
  'America/Campo_Grande' => '(GMT-4:00) America/Campo_Grande (Amazon Time)',
  'America/Cuiaba' => '(GMT-4:00) America/Cuiaba (Amazon Time)',
  'America/Curacao' => '(GMT-4:00) America/Curacao (Atlantic Standard Time)',
  'America/Dominica' => '(GMT-4:00) America/Dominica (Atlantic Standard Time)',
  'America/Eirunepe' => '(GMT-4:00) America/Eirunepe (Amazon Time)',
  'America/Glace_Bay' => '(GMT-4:00) America/Glace_Bay (Atlantic Standard Time)',
  'America/Goose_Bay' => '(GMT-4:00) America/Goose_Bay (Atlantic Standard Time)',
  'America/Grenada' => '(GMT-4:00) America/Grenada (Atlantic Standard Time)',
  'America/Guadeloupe' => '(GMT-4:00) America/Guadeloupe (Atlantic Standard Time)',
  'America/Guyana' => '(GMT-4:00) America/Guyana (Guyana Time)',
  'America/Halifax' => '(GMT-4:00) America/Halifax (Atlantic Standard Time)',
  'America/La_Paz' => '(GMT-4:00) America/La_Paz (Bolivia Time)',
  'America/Manaus' => '(GMT-4:00) America/Manaus (Amazon Time)',
  'America/Marigot' => '(GMT-4:00) America/Marigot (Atlantic Standard Time)',
  'America/Martinique' => '(GMT-4:00) America/Martinique (Atlantic Standard Time)',
  'America/Moncton' => '(GMT-4:00) America/Moncton (Atlantic Standard Time)',
  'America/Montserrat' => '(GMT-4:00) America/Montserrat (Atlantic Standard Time)',
  'America/Port_of_Spain' => '(GMT-4:00) America/Port_of_Spain (Atlantic Standard Time)',
  'America/Porto_Acre' => '(GMT-4:00) America/Porto_Acre (Amazon Time)',
  'America/Porto_Velho' => '(GMT-4:00) America/Porto_Velho (Amazon Time)',
  'America/Puerto_Rico' => '(GMT-4:00) America/Puerto_Rico (Atlantic Standard Time)',
  'America/Rio_Branco' => '(GMT-4:00) America/Rio_Branco (Amazon Time)',
  'America/Santiago' => '(GMT-4:00) America/Santiago (Chile Time)',
  'America/Santo_Domingo' => '(GMT-4:00) America/Santo_Domingo (Atlantic Standard Time)',
  'America/St_Barthelemy' => '(GMT-4:00) America/St_Barthelemy (Atlantic Standard Time)',
  'America/St_Kitts' => '(GMT-4:00) America/St_Kitts (Atlantic Standard Time)',
  'America/St_Lucia' => '(GMT-4:00) America/St_Lucia (Atlantic Standard Time)',
  'America/St_Thomas' => '(GMT-4:00) America/St_Thomas (Atlantic Standard Time)',
  'America/St_Vincent' => '(GMT-4:00) America/St_Vincent (Atlantic Standard Time)',
  'America/Thule' => '(GMT-4:00) America/Thule (Atlantic Standard Time)',
  'America/Tortola' => '(GMT-4:00) America/Tortola (Atlantic Standard Time)',
  'America/Virgin' => '(GMT-4:00) America/Virgin (Atlantic Standard Time)',
  'Antarctica/Palmer' => '(GMT-4:00) Antarctica/Palmer (Chile Time)',
  'Atlantic/Bermuda' => '(GMT-4:00) Atlantic/Bermuda (Atlantic Standard Time)',
  'Atlantic/Stanley' => '(GMT-4:00) Atlantic/Stanley (Falkland Is. Time)',
  'Brazil/Acre' => '(GMT-4:00) Brazil/Acre (Amazon Time)',
  'Brazil/West' => '(GMT-4:00) Brazil/West (Amazon Time)',
  'Canada/Atlantic' => '(GMT-4:00) Canada/Atlantic (Atlantic Standard Time)',
  'Chile/Continental' => '(GMT-4:00) Chile/Continental (Chile Time)',
  'America/St_Johns' => '(GMT-3:-30) America/St_Johns (Newfoundland Standard Time)',
  'Canada/Newfoundland' => '(GMT-3:-30) Canada/Newfoundland (Newfoundland Standard Time)',
  'America/Araguaina' => '(GMT-3:00) America/Araguaina (Brasilia Time)',
  'America/Bahia' => '(GMT-3:00) America/Bahia (Brasilia Time)',
  'America/Belem' => '(GMT-3:00) America/Belem (Brasilia Time)',
  'America/Buenos_Aires' => '(GMT-3:00) America/Buenos_Aires (Argentine Time)',
  'America/Catamarca' => '(GMT-3:00) America/Catamarca (Argentine Time)',
  'America/Cayenne' => '(GMT-3:00) America/Cayenne (French Guiana Time)',
  'America/Cordoba' => '(GMT-3:00) America/Cordoba (Argentine Time)',
  'America/Fortaleza' => '(GMT-3:00) America/Fortaleza (Brasilia Time)',
  'America/Godthab' => '(GMT-3:00) America/Godthab (Western Greenland Time)',
  'America/Jujuy' => '(GMT-3:00) America/Jujuy (Argentine Time)',
  'America/Maceio' => '(GMT-3:00) America/Maceio (Brasilia Time)',
  'America/Mendoza' => '(GMT-3:00) America/Mendoza (Argentine Time)',
  'America/Miquelon' => '(GMT-3:00) America/Miquelon (Pierre & Miquelon Standard Time)',
  'America/Montevideo' => '(GMT-3:00) America/Montevideo (Uruguay Time)',
  'America/Paramaribo' => '(GMT-3:00) America/Paramaribo (Suriname Time)',
  'America/Recife' => '(GMT-3:00) America/Recife (Brasilia Time)',
  'America/Rosario' => '(GMT-3:00) America/Rosario (Argentine Time)',
  'America/Santarem' => '(GMT-3:00) America/Santarem (Brasilia Time)',
  'America/Sao_Paulo' => '(GMT-3:00) America/Sao_Paulo (Brasilia Time)',
  'Antarctica/Rothera' => '(GMT-3:00) Antarctica/Rothera (Rothera Time)',
  'Brazil/East' => '(GMT-3:00) Brazil/East (Brasilia Time)',
  'America/Noronha' => '(GMT-2:00) America/Noronha (Fernando de Noronha Time)',
  'Atlantic/South_Georgia' => '(GMT-2:00) Atlantic/South_Georgia (South Georgia Standard Time)',
  'Brazil/DeNoronha' => '(GMT-2:00) Brazil/DeNoronha (Fernando de Noronha Time)',
  'America/Scoresbysund' => '(GMT-1:00) America/Scoresbysund (Eastern Greenland Time)',
  'Atlantic/Azores' => '(GMT-1:00) Atlantic/Azores (Azores Time)',
  'Atlantic/Cape_Verde' => '(GMT-1:00) Atlantic/Cape_Verde (Cape Verde Time)',
  'Africa/Abidjan' => '(GMT+0:00) Africa/Abidjan (Greenwich Mean Time)',
  'Africa/Accra' => '(GMT+0:00) Africa/Accra (Ghana Mean Time)',
  'Africa/Bamako' => '(GMT+0:00) Africa/Bamako (Greenwich Mean Time)',
  'Africa/Banjul' => '(GMT+0:00) Africa/Banjul (Greenwich Mean Time)',
  'Africa/Bissau' => '(GMT+0:00) Africa/Bissau (Greenwich Mean Time)',
  'Africa/Casablanca' => '(GMT+0:00) Africa/Casablanca (Western European Time)',
  'Africa/Conakry' => '(GMT+0:00) Africa/Conakry (Greenwich Mean Time)',
  'Africa/Dakar' => '(GMT+0:00) Africa/Dakar (Greenwich Mean Time)',
  'Africa/El_Aaiun' => '(GMT+0:00) Africa/El_Aaiun (Western European Time)',
  'Africa/Freetown' => '(GMT+0:00) Africa/Freetown (Greenwich Mean Time)',
  'Africa/Lome' => '(GMT+0:00) Africa/Lome (Greenwich Mean Time)',
  'Africa/Monrovia' => '(GMT+0:00) Africa/Monrovia (Greenwich Mean Time)',
  'Africa/Nouakchott' => '(GMT+0:00) Africa/Nouakchott (Greenwich Mean Time)',
  'Africa/Ouagadougou' => '(GMT+0:00) Africa/Ouagadougou (Greenwich Mean Time)',
  'Africa/Sao_Tome' => '(GMT+0:00) Africa/Sao_Tome (Greenwich Mean Time)',
  'Africa/Timbuktu' => '(GMT+0:00) Africa/Timbuktu (Greenwich Mean Time)',
  'America/Danmarkshavn' => '(GMT+0:00) America/Danmarkshavn (Greenwich Mean Time)',
  'Atlantic/Canary' => '(GMT+0:00) Atlantic/Canary (Western European Time)',
  'Atlantic/Faeroe' => '(GMT+0:00) Atlantic/Faeroe (Western European Time)',
  'Atlantic/Faroe' => '(GMT+0:00) Atlantic/Faroe (Western European Time)',
  'Atlantic/Madeira' => '(GMT+0:00) Atlantic/Madeira (Western European Time)',
  'Atlantic/Reykjavik' => '(GMT+0:00) Atlantic/Reykjavik (Greenwich Mean Time)',
  'Atlantic/St_Helena' => '(GMT+0:00) Atlantic/St_Helena (Greenwich Mean Time)',
  'Europe/Belfast' => '(GMT+0:00) Europe/Belfast (Greenwich Mean Time)',
  'Europe/Dublin' => '(GMT+0:00) Europe/Dublin (Greenwich Mean Time)',
  'Europe/Guernsey' => '(GMT+0:00) Europe/Guernsey (Greenwich Mean Time)',
  'Europe/Isle_of_Man' => '(GMT+0:00) Europe/Isle_of_Man (Greenwich Mean Time)',
  'Europe/Jersey' => '(GMT+0:00) Europe/Jersey (Greenwich Mean Time)',
  'Europe/Lisbon' => '(GMT+0:00) Europe/Lisbon (Western European Time)',
  'Europe/London' => '(GMT+0:00) Europe/London (Greenwich Mean Time)',
  'Africa/Algiers' => '(GMT+1:00) Africa/Algiers (Central European Time)',
  'Africa/Bangui' => '(GMT+1:00) Africa/Bangui (Western African Time)',
  'Africa/Brazzaville' => '(GMT+1:00) Africa/Brazzaville (Western African Time)',
  'Africa/Ceuta' => '(GMT+1:00) Africa/Ceuta (Central European Time)',
  'Africa/Douala' => '(GMT+1:00) Africa/Douala (Western African Time)',
  'Africa/Kinshasa' => '(GMT+1:00) Africa/Kinshasa (Western African Time)',
  'Africa/Lagos' => '(GMT+1:00) Africa/Lagos (Western African Time)',
  'Africa/Libreville' => '(GMT+1:00) Africa/Libreville (Western African Time)',
  'Africa/Luanda' => '(GMT+1:00) Africa/Luanda (Western African Time)',
  'Africa/Malabo' => '(GMT+1:00) Africa/Malabo (Western African Time)',
  'Africa/Ndjamena' => '(GMT+1:00) Africa/Ndjamena (Western African Time)',
  'Africa/Niamey' => '(GMT+1:00) Africa/Niamey (Western African Time)',
  'Africa/Porto-Novo' => '(GMT+1:00) Africa/Porto-Novo (Western African Time)',
  'Africa/Tunis' => '(GMT+1:00) Africa/Tunis (Central European Time)',
  'Africa/Windhoek' => '(GMT+1:00) Africa/Windhoek (Western African Time)',
  'Arctic/Longyearbyen' => '(GMT+1:00) Arctic/Longyearbyen (Central European Time)',
  'Atlantic/Jan_Mayen' => '(GMT+1:00) Atlantic/Jan_Mayen (Central European Time)',
  'Europe/Amsterdam' => '(GMT+1:00) Europe/Amsterdam (Central European Time)',
  'Europe/Andorra' => '(GMT+1:00) Europe/Andorra (Central European Time)',
  'Europe/Belgrade' => '(GMT+1:00) Europe/Belgrade (Central European Time)',
  'Europe/Berlin' => '(GMT+1:00) Europe/Berlin (Central European Time)',
  'Europe/Bratislava' => '(GMT+1:00) Europe/Bratislava (Central European Time)',
  'Europe/Brussels' => '(GMT+1:00) Europe/Brussels (Central European Time)',
  'Europe/Budapest' => '(GMT+1:00) Europe/Budapest (Central European Time)',
  'Europe/Copenhagen' => '(GMT+1:00) Europe/Copenhagen (Central European Time)',
  'Europe/Gibraltar' => '(GMT+1:00) Europe/Gibraltar (Central European Time)',
  'Europe/Ljubljana' => '(GMT+1:00) Europe/Ljubljana (Central European Time)',
  'Europe/Luxembourg' => '(GMT+1:00) Europe/Luxembourg (Central European Time)',
  'Europe/Madrid' => '(GMT+1:00) Europe/Madrid (Central European Time)',
  'Europe/Malta' => '(GMT+1:00) Europe/Malta (Central European Time)',
  'Europe/Monaco' => '(GMT+1:00) Europe/Monaco (Central European Time)',
  'Europe/Oslo' => '(GMT+1:00) Europe/Oslo (Central European Time)',
  'Europe/Paris' => '(GMT+1:00) Europe/Paris (Central European Time)',
  'Europe/Podgorica' => '(GMT+1:00) Europe/Podgorica (Central European Time)',
  'Europe/Prague' => '(GMT+1:00) Europe/Prague (Central European Time)',
  'Europe/Rome' => '(GMT+1:00) Europe/Rome (Central European Time)',
  'Europe/San_Marino' => '(GMT+1:00) Europe/San_Marino (Central European Time)',
  'Europe/Sarajevo' => '(GMT+1:00) Europe/Sarajevo (Central European Time)',
  'Europe/Skopje' => '(GMT+1:00) Europe/Skopje (Central European Time)',
  'Europe/Stockholm' => '(GMT+1:00) Europe/Stockholm (Central European Time)',
  'Europe/Tirane' => '(GMT+1:00) Europe/Tirane (Central European Time)',
  'Europe/Vaduz' => '(GMT+1:00) Europe/Vaduz (Central European Time)',
  'Europe/Vatican' => '(GMT+1:00) Europe/Vatican (Central European Time)',
  'Europe/Vienna' => '(GMT+1:00) Europe/Vienna (Central European Time)',
  'Europe/Warsaw' => '(GMT+1:00) Europe/Warsaw (Central European Time)',
  'Europe/Zagreb' => '(GMT+1:00) Europe/Zagreb (Central European Time)',
  'Europe/Zurich' => '(GMT+1:00) Europe/Zurich (Central European Time)',
  'Africa/Blantyre' => '(GMT+2:00) Africa/Blantyre (Central African Time)',
  'Africa/Bujumbura' => '(GMT+2:00) Africa/Bujumbura (Central African Time)',
  'Africa/Cairo' => '(GMT+2:00) Africa/Cairo (Eastern European Time)',
  'Africa/Gaborone' => '(GMT+2:00) Africa/Gaborone (Central African Time)',
  'Africa/Harare' => '(GMT+2:00) Africa/Harare (Central African Time)',
  'Africa/Johannesburg' => '(GMT+2:00) Africa/Johannesburg (South Africa Standard Time)',
  'Africa/Kigali' => '(GMT+2:00) Africa/Kigali (Central African Time)',
  'Africa/Lubumbashi' => '(GMT+2:00) Africa/Lubumbashi (Central African Time)',
  'Africa/Lusaka' => '(GMT+2:00) Africa/Lusaka (Central African Time)',
  'Africa/Maputo' => '(GMT+2:00) Africa/Maputo (Central African Time)',
  'Africa/Maseru' => '(GMT+2:00) Africa/Maseru (South Africa Standard Time)',
  'Africa/Mbabane' => '(GMT+2:00) Africa/Mbabane (South Africa Standard Time)',
  'Africa/Tripoli' => '(GMT+2:00) Africa/Tripoli (Eastern European Time)',
  'Asia/Amman' => '(GMT+2:00) Asia/Amman (Eastern European Time)',
  'Asia/Beirut' => '(GMT+2:00) Asia/Beirut (Eastern European Time)',
  'Asia/Damascus' => '(GMT+2:00) Asia/Damascus (Eastern European Time)',
  'Asia/Gaza' => '(GMT+2:00) Asia/Gaza (Eastern European Time)',
  'Asia/Istanbul' => '(GMT+2:00) Asia/Istanbul (Eastern European Time)',
  'Asia/Jerusalem' => '(GMT+2:00) Asia/Jerusalem (Israel Standard Time)',
  'Asia/Nicosia' => '(GMT+2:00) Asia/Nicosia (Eastern European Time)',
  'Asia/Tel_Aviv' => '(GMT+2:00) Asia/Tel_Aviv (Israel Standard Time)',
  'Europe/Athens' => '(GMT+2:00) Europe/Athens (Eastern European Time)',
  'Europe/Bucharest' => '(GMT+2:00) Europe/Bucharest (Eastern European Time)',
  'Europe/Chisinau' => '(GMT+2:00) Europe/Chisinau (Eastern European Time)',
  'Europe/Helsinki' => '(GMT+2:00) Europe/Helsinki (Eastern European Time)',
  'Europe/Istanbul' => '(GMT+2:00) Europe/Istanbul (Eastern European Time)',
  'Europe/Kaliningrad' => '(GMT+2:00) Europe/Kaliningrad (Eastern European Time)',
  'Europe/Kiev' => '(GMT+2:00) Europe/Kiev (Eastern European Time)',
  'Europe/Mariehamn' => '(GMT+2:00) Europe/Mariehamn (Eastern European Time)',
  'Europe/Minsk' => '(GMT+2:00) Europe/Minsk (Eastern European Time)',
  'Europe/Nicosia' => '(GMT+2:00) Europe/Nicosia (Eastern European Time)',
  'Europe/Riga' => '(GMT+2:00) Europe/Riga (Eastern European Time)',
  'Europe/Simferopol' => '(GMT+2:00) Europe/Simferopol (Eastern European Time)',
  'Europe/Sofia' => '(GMT+2:00) Europe/Sofia (Eastern European Time)',
  'Europe/Tallinn' => '(GMT+2:00) Europe/Tallinn (Eastern European Time)',
  'Europe/Tiraspol' => '(GMT+2:00) Europe/Tiraspol (Eastern European Time)',
  'Europe/Uzhgorod' => '(GMT+2:00) Europe/Uzhgorod (Eastern European Time)',
  'Europe/Vilnius' => '(GMT+2:00) Europe/Vilnius (Eastern European Time)',
  'Europe/Zaporozhye' => '(GMT+2:00) Europe/Zaporozhye (Eastern European Time)',
  'Africa/Addis_Ababa' => '(GMT+3:00) Africa/Addis_Ababa (Eastern African Time)',
  'Africa/Asmara' => '(GMT+3:00) Africa/Asmara (Eastern African Time)',
  'Africa/Asmera' => '(GMT+3:00) Africa/Asmera (Eastern African Time)',
  'Africa/Dar_es_Salaam' => '(GMT+3:00) Africa/Dar_es_Salaam (Eastern African Time)',
  'Africa/Djibouti' => '(GMT+3:00) Africa/Djibouti (Eastern African Time)',
  'Africa/Kampala' => '(GMT+3:00) Africa/Kampala (Eastern African Time)',
  'Africa/Khartoum' => '(GMT+3:00) Africa/Khartoum (Eastern African Time)',
  'Africa/Mogadishu' => '(GMT+3:00) Africa/Mogadishu (Eastern African Time)',
  'Africa/Nairobi' => '(GMT+3:00) Africa/Nairobi (Eastern African Time)',
  'Antarctica/Syowa' => '(GMT+3:00) Antarctica/Syowa (Syowa Time)',
  'Asia/Aden' => '(GMT+3:00) Asia/Aden (Arabia Standard Time)',
  'Asia/Baghdad' => '(GMT+3:00) Asia/Baghdad (Arabia Standard Time)',
  'Asia/Bahrain' => '(GMT+3:00) Asia/Bahrain (Arabia Standard Time)',
  'Asia/Kuwait' => '(GMT+3:00) Asia/Kuwait (Arabia Standard Time)',
  'Asia/Qatar' => '(GMT+3:00) Asia/Qatar (Arabia Standard Time)',
  'Europe/Moscow' => '(GMT+3:00) Europe/Moscow (Moscow Standard Time)',
  'Europe/Volgograd' => '(GMT+3:00) Europe/Volgograd (Volgograd Time)',
  'Indian/Antananarivo' => '(GMT+3:00) Indian/Antananarivo (Eastern African Time)',
  'Indian/Comoro' => '(GMT+3:00) Indian/Comoro (Eastern African Time)',
  'Indian/Mayotte' => '(GMT+3:00) Indian/Mayotte (Eastern African Time)',
  'Asia/Tehran' => '(GMT+3:30) Asia/Tehran (Iran Standard Time)',
  'Asia/Baku' => '(GMT+4:00) Asia/Baku (Azerbaijan Time)',
  'Asia/Dubai' => '(GMT+4:00) Asia/Dubai (Gulf Standard Time)',
  'Asia/Muscat' => '(GMT+4:00) Asia/Muscat (Gulf Standard Time)',
  'Asia/Tbilisi' => '(GMT+4:00) Asia/Tbilisi (Georgia Time)',
  'Asia/Yerevan' => '(GMT+4:00) Asia/Yerevan (Armenia Time)',
  'Europe/Samara' => '(GMT+4:00) Europe/Samara (Samara Time)',
  'Indian/Mahe' => '(GMT+4:00) Indian/Mahe (Seychelles Time)',
  'Indian/Mauritius' => '(GMT+4:00) Indian/Mauritius (Mauritius Time)',
  'Indian/Reunion' => '(GMT+4:00) Indian/Reunion (Reunion Time)',
  'Asia/Kabul' => '(GMT+4:30) Asia/Kabul (Afghanistan Time)',
  'Asia/Aqtau' => '(GMT+5:00) Asia/Aqtau (Aqtau Time)',
  'Asia/Aqtobe' => '(GMT+5:00) Asia/Aqtobe (Aqtobe Time)',
  'Asia/Ashgabat' => '(GMT+5:00) Asia/Ashgabat (Turkmenistan Time)',
  'Asia/Ashkhabad' => '(GMT+5:00) Asia/Ashkhabad (Turkmenistan Time)',
  'Asia/Dushanbe' => '(GMT+5:00) Asia/Dushanbe (Tajikistan Time)',
  'Asia/Karachi' => '(GMT+5:00) Asia/Karachi (Pakistan Time)',
  'Asia/Oral' => '(GMT+5:00) Asia/Oral (Oral Time)',
  'Asia/Samarkand' => '(GMT+5:00) Asia/Samarkand (Uzbekistan Time)',
  'Asia/Tashkent' => '(GMT+5:00) Asia/Tashkent (Uzbekistan Time)',
  'Asia/Yekaterinburg' => '(GMT+5:00) Asia/Yekaterinburg (Yekaterinburg Time)',
  'Indian/Kerguelen' => '(GMT+5:00) Indian/Kerguelen (French Southern & Antarctic Lands Time)',
  'Indian/Maldives' => '(GMT+5:00) Indian/Maldives (Maldives Time)',
  'Asia/Calcutta' => '(GMT+5:30) Asia/Calcutta (India Standard Time)',
  'Asia/Colombo' => '(GMT+5:30) Asia/Colombo (India Standard Time)',
  'Asia/Kolkata' => '(GMT+5:30) Asia/Kolkata (India Standard Time)',
  'Asia/Katmandu' => '(GMT+5:45) Asia/Katmandu (Nepal Time)',
  'Antarctica/Mawson' => '(GMT+6:00) Antarctica/Mawson (Mawson Time)',
  'Antarctica/Vostok' => '(GMT+6:00) Antarctica/Vostok (Vostok Time)',
  'Asia/Almaty' => '(GMT+6:00) Asia/Almaty (Alma-Ata Time)',
  'Asia/Bishkek' => '(GMT+6:00) Asia/Bishkek (Kirgizstan Time)',
  'Asia/Dacca' => '(GMT+6:00) Asia/Dacca (Bangladesh Time)',
  'Asia/Dhaka' => '(GMT+6:00) Asia/Dhaka (Bangladesh Time)',
  'Asia/Novosibirsk' => '(GMT+6:00) Asia/Novosibirsk (Novosibirsk Time)',
  'Asia/Omsk' => '(GMT+6:00) Asia/Omsk (Omsk Time)',
  'Asia/Qyzylorda' => '(GMT+6:00) Asia/Qyzylorda (Qyzylorda Time)',
  'Asia/Thimbu' => '(GMT+6:00) Asia/Thimbu (Bhutan Time)',
  'Asia/Thimphu' => '(GMT+6:00) Asia/Thimphu (Bhutan Time)',
  'Indian/Chagos' => '(GMT+6:00) Indian/Chagos (Indian Ocean Territory Time)',
  'Asia/Rangoon' => '(GMT+6:30) Asia/Rangoon (Myanmar Time)',
  'Indian/Cocos' => '(GMT+6:30) Indian/Cocos (Cocos Islands Time)',
  'Antarctica/Davis' => '(GMT+7:00) Antarctica/Davis (Davis Time)',
  'Asia/Bangkok' => '(GMT+7:00) Asia/Bangkok (Indochina Time)',
  'Asia/Ho_Chi_Minh' => '(GMT+7:00) Asia/Ho_Chi_Minh (Indochina Time)',
  'Asia/Hovd' => '(GMT+7:00) Asia/Hovd (Hovd Time)',
  'Asia/Jakarta' => '(GMT+7:00) Asia/Jakarta (West Indonesia Time)',
  'Asia/Krasnoyarsk' => '(GMT+7:00) Asia/Krasnoyarsk (Krasnoyarsk Time)',
  'Asia/Phnom_Penh' => '(GMT+7:00) Asia/Phnom_Penh (Indochina Time)',
  'Asia/Pontianak' => '(GMT+7:00) Asia/Pontianak (West Indonesia Time)',
  'Asia/Saigon' => '(GMT+7:00) Asia/Saigon (Indochina Time)',
  'Asia/Vientiane' => '(GMT+7:00) Asia/Vientiane (Indochina Time)',
  'Indian/Christmas' => '(GMT+7:00) Indian/Christmas (Christmas Island Time)',
  'Antarctica/Casey' => '(GMT+8:00) Antarctica/Casey (Western Standard Time (Australia))',
  'Asia/Brunei' => '(GMT+8:00) Asia/Brunei (Brunei Time)',
  'Asia/Choibalsan' => '(GMT+8:00) Asia/Choibalsan (Choibalsan Time)',
  'Asia/Chongqing' => '(GMT+8:00) Asia/Chongqing (China Standard Time)',
  'Asia/Chungking' => '(GMT+8:00) Asia/Chungking (China Standard Time)',
  'Asia/Harbin' => '(GMT+8:00) Asia/Harbin (China Standard Time)',
  'Asia/Hong_Kong' => '(GMT+8:00) Asia/Hong_Kong (Hong Kong Time)',
  'Asia/Irkutsk' => '(GMT+8:00) Asia/Irkutsk (Irkutsk Time)',
  'Asia/Kashgar' => '(GMT+8:00) Asia/Kashgar (China Standard Time)',
  'Asia/Kuala_Lumpur' => '(GMT+8:00) Asia/Kuala_Lumpur (Malaysia Time)',
  'Asia/Kuching' => '(GMT+8:00) Asia/Kuching (Malaysia Time)',
  'Asia/Macao' => '(GMT+8:00) Asia/Macao (China Standard Time)',
  'Asia/Macau' => '(GMT+8:00) Asia/Macau (China Standard Time)',
  'Asia/Makassar' => '(GMT+8:00) Asia/Makassar (Central Indonesia Time)',
  'Asia/Manila' => '(GMT+8:00) Asia/Manila (Philippines Time)',
  'Asia/Shanghai' => '(GMT+8:00) Asia/Shanghai (China Standard Time)',
  'Asia/Singapore' => '(GMT+8:00) Asia/Singapore (Singapore Time)',
  'Asia/Taipei' => '(GMT+8:00) Asia/Taipei (China Standard Time)',
  'Asia/Ujung_Pandang' => '(GMT+8:00) Asia/Ujung_Pandang (Central Indonesia Time)',
  'Asia/Ulaanbaatar' => '(GMT+8:00) Asia/Ulaanbaatar (Ulaanbaatar Time)',
  'Asia/Ulan_Bator' => '(GMT+8:00) Asia/Ulan_Bator (Ulaanbaatar Time)',
  'Asia/Urumqi' => '(GMT+8:00) Asia/Urumqi (China Standard Time)',
  'Australia/Perth' => '(GMT+8:00) Australia/Perth (Western Standard Time (Australia))',
  'Australia/West' => '(GMT+8:00) Australia/West (Western Standard Time (Australia))',
  'Australia/Eucla' => '(GMT+8:45) Australia/Eucla (Central Western Standard Time (Australia))',
  'Asia/Dili' => '(GMT+9:00) Asia/Dili (Timor-Leste Time)',
  'Asia/Jayapura' => '(GMT+9:00) Asia/Jayapura (East Indonesia Time)',
  'Asia/Pyongyang' => '(GMT+9:00) Asia/Pyongyang (Korea Standard Time)',
  'Asia/Seoul' => '(GMT+9:00) Asia/Seoul (Korea Standard Time)',
  'Asia/Tokyo' => '(GMT+9:00) Asia/Tokyo (Japan Standard Time)',
  'Asia/Yakutsk' => '(GMT+9:00) Asia/Yakutsk (Yakutsk Time)',
  'Australia/Adelaide' => '(GMT+9:30) Australia/Adelaide (Central Standard Time (South Australia))',
  'Australia/Broken_Hill' => '(GMT+9:30) Australia/Broken_Hill (Central Standard Time (South Australia/New South Wales))',
  'Australia/Darwin' => '(GMT+9:30) Australia/Darwin (Central Standard Time (Northern Territory))',
  'Australia/North' => '(GMT+9:30) Australia/North (Central Standard Time (Northern Territory))',
  'Australia/South' => '(GMT+9:30) Australia/South (Central Standard Time (South Australia))',
  'Australia/Yancowinna' => '(GMT+9:30) Australia/Yancowinna (Central Standard Time (South Australia/New South Wales))',
  'Antarctica/DumontDUrville' => '(GMT+10:00) Antarctica/DumontDUrville (Dumont-d\'Urville Time)',
  'Asia/Sakhalin' => '(GMT+10:00) Asia/Sakhalin (Sakhalin Time)',
  'Asia/Vladivostok' => '(GMT+10:00) Asia/Vladivostok (Vladivostok Time)',
  'Australia/ACT' => '(GMT+10:00) Australia/ACT (Eastern Standard Time (New South Wales))',
  'Australia/Brisbane' => '(GMT+10:00) Australia/Brisbane (Eastern Standard Time (Queensland))',
  'Australia/Canberra' => '(GMT+10:00) Australia/Canberra (Eastern Standard Time (New South Wales))',
  'Australia/Currie' => '(GMT+10:00) Australia/Currie (Eastern Standard Time (New South Wales))',
  'Australia/Hobart' => '(GMT+10:00) Australia/Hobart (Eastern Standard Time (Tasmania))',
  'Australia/Lindeman' => '(GMT+10:00) Australia/Lindeman (Eastern Standard Time (Queensland))',
  'Australia/Melbourne' => '(GMT+10:00) Australia/Melbourne (Eastern Standard Time (Victoria))',
  'Australia/NSW' => '(GMT+10:00) Australia/NSW (Eastern Standard Time (New South Wales))',
  'Australia/Queensland' => '(GMT+10:00) Australia/Queensland (Eastern Standard Time (Queensland))',
  'Australia/Sydney' => '(GMT+10:00) Australia/Sydney (Eastern Standard Time (New South Wales))',
  'Australia/Tasmania' => '(GMT+10:00) Australia/Tasmania (Eastern Standard Time (Tasmania))',
  'Australia/Victoria' => '(GMT+10:00) Australia/Victoria (Eastern Standard Time (Victoria))',
  'Australia/LHI' => '(GMT+10:30) Australia/LHI (Lord Howe Standard Time)',
  'Australia/Lord_Howe' => '(GMT+10:30) Australia/Lord_Howe (Lord Howe Standard Time)',
  'Asia/Magadan' => '(GMT+11:00) Asia/Magadan (Magadan Time)',
  'Antarctica/McMurdo' => '(GMT+12:00) Antarctica/McMurdo (New Zealand Standard Time)',
  'Antarctica/South_Pole' => '(GMT+12:00) Antarctica/South_Pole (New Zealand Standard Time)',
  'Asia/Anadyr' => '(GMT+12:00) Asia/Anadyr (Anadyr Time)',
  'Asia/Kamchatka' => '(GMT+12:00) Asia/Kamchatka (Petropavlovsk-Kamchatski Time)'
);

$googlefonts = array(
  "ABeeZee" => "ABeeZee",
  "Abel" => "Abel",
  "Abril Fatface" => "Abril+Fatface",
  "Aclonica" => "Aclonica",
  "Acme" => "Acme",
  "Actor" => "Actor",
  "Adamina" => "Adamina",
  "Advent Pro" => "Advent+Pro",
  "Aguafina Script" => "Aguafina+Script",
  "Akronim" => "Akronim",
  "Aladin" => "Aladin",
  "Aldrich" => "Aldrich",
  "Alegreya" => "Alegreya",
  "Alegreya SC" => "Alegreya+SC",
  "Alex Brush" => "Alex+Brush",
  "Alfa Slab One" => "Alfa+Slab+One",
  "Alice" => "Alice",
  "Alike" => "Alike",
  "Alike Angular" => "Alike+Angular",
  "Allan" => "Allan",
  "Allerta" => "Allerta",
  "Allerta Stencil" => "Allerta+Stencil",
  "Allura" => "Allura",
  "Almendra" => "Almendra",
  "Almendra Display" => "Almendra+Display",
  "Almendra SC" => "Almendra+SC",
  "Amarante" => "Amarante",
  "Amaranth" => "Amaranth",
  "Amatic SC" => "Amatic+SC",
  "Amethysta" => "Amethysta",
  "Anaheim" => "Anaheim",
  "Andada" => "Andada",
  "Andika" => "Andika",
  "Angkor" => "Angkor",
  "Annie Use Your Telescope" => "Annie+Use+Your+Telescope",
  "Anonymous Pro" => "Anonymous+Pro",
  "Antic" => "Antic",
  "Antic Didone" => "Antic+Didone",
  "Antic Slab" => "Antic+Slab",
  "Anton" => "Anton",
  "Arapey" => "Arapey",
  "Arbutus" => "Arbutus",
  "Arbutus Slab" => "Arbutus+Slab",
  "Architects Daughter" => "Architects+Daughter",
  "Archivo Black" => "Archivo+Black",
  "Archivo Narrow" => "Archivo+Narrow",
  "Arimo" => "Arimo",
  "Arizonia" => "Arizonia",
  "Armata" => "Armata",
  "Artifika" => "Artifika",
  "Arvo" => "Arvo",
  "Asap" => "Asap",
  "Asset" => "Asset",
  "Astloch" => "Astloch",
  "Asul" => "Asul",
  "Atomic Age" => "Atomic+Age",
  "Aubrey" => "Aubrey",
  "Audiowide" => "Audiowide",
  "Autour One" => "Autour+One",
  "Average" => "Average",
  "Average Sans" => "Average+Sans",
  "Averia Gruesa Libre" => "Averia+Gruesa+Libre",
  "Averia Libre" => "Averia+Libre",
  "Averia Sans Libre" => "Averia+Sans+Libre",
  "Averia Serif Libre" => "Averia+Serif+Libre",
  "Bad Script" => "Bad+Script",
  "Balthazar" => "Balthazar",
  "Bangers" => "Bangers",
  "Basic" => "Basic",
  "Battambang" => "Battambang",
  "Baumans" => "Baumans",
  "Bayon" => "Bayon",
  "Belgrano" => "Belgrano",
  "Belleza" => "Belleza",
  "BenchNine" => "BenchNine",
  "Bentham" => "Bentham",
  "Berkshire Swash" => "Berkshire+Swash",
  "Bevan" => "Bevan",
  "Bigelow Rules" => "Bigelow+Rules",
  "Bigshot One" => "Bigshot+One",
  "Bilbo" => "Bilbo",
  "Bilbo Swash Caps" => "Bilbo+Swash+Caps",
  "Bitter" => "Bitter",
  "Black Ops One" => "Black+Ops+One",
  "Bokor" => "Bokor",
  "Bonbon" => "Bonbon",
  "Boogaloo" => "Boogaloo",
  "Bowlby One" => "Bowlby+One",
  "Bowlby One SC" => "Bowlby+One+SC",
  "Brawler" => "Brawler",
  "Bree Serif" => "Bree+Serif",
  "Bubblegum Sans" => "Bubblegum+Sans",
  "Bubbler One" => "Bubbler+One",
  "Buda" => "Buda",
  "Buenard" => "Buenard",
  "Butcherman" => "Butcherman",
  "Butterfly Kids" => "Butterfly+Kids",
  "Cabin" => "Cabin",
  "Cabin Condensed" => "Cabin+Condensed",
  "Cabin Sketch" => "Cabin+Sketch",
  "Caesar Dressing" => "Caesar+Dressing",
  "Cagliostro" => "Cagliostro",
  "Calligraffitti" => "Calligraffitti",
  "Cambo" => "Cambo",
  "Candal" => "Candal",
  "Cantarell" => "Cantarell",
  "Cantata One" => "Cantata+One",
  "Cantora One" => "Cantora+One",
  "Capriola" => "Capriola",
  "Cardo" => "Cardo",
  "Carme" => "Carme",
  "Carrois Gothic" => "Carrois+Gothic",
  "Carrois Gothic SC" => "Carrois+Gothic+SC",
  "Carter One" => "Carter+One",
  "Caudex" => "Caudex",
  "Cedarville Cursive" => "Cedarville+Cursive",
  "Ceviche One" => "Ceviche+One",
  "Changa One" => "Changa+One",
  "Chango" => "Chango",
  "Chau Philomene One" => "Chau+Philomene+One",
  "Chela One" => "Chela+One",
  "Chelsea Market" => "Chelsea+Market",
  "Chenla" => "Chenla",
  "Cherry Cream Soda" => "Cherry+Cream+Soda",
  "Cherry Swash" => "Cherry+Swash",
  "Chewy" => "Chewy",
  "Chicle" => "Chicle",
  "Chivo" => "Chivo",
  "Cinzel" => "Cinzel",
  "Cinzel Decorative" => "Cinzel+Decorative",
  "Clicker Script" => "Clicker+Script",
  "Coda" => "Coda",
  "Coda Caption" => "Coda+Caption",
  "Codystar" => "Codystar",
  "Combo" => "Combo",
  "Comfortaa" => "Comfortaa",
  "Coming Soon" => "Coming+Soon",
  "Concert One" => "Concert+One",
  "Condiment" => "Condiment",
  "Content" => "Content",
  "Contrail One" => "Contrail+One",
  "Convergence" => "Convergence",
  "Cookie" => "Cookie",
  "Copse" => "Copse",
  "Corben" => "Corben",
  "Courgette" => "Courgette",
  "Cousine" => "Cousine",
  "Coustard" => "Coustard",
  "Covered By Your Grace" => "Covered+By+Your+Grace",
  "Crafty Girls" => "Crafty+Girls",
  "Creepster" => "Creepster",
  "Crete Round" => "Crete+Round",
  "Crimson Text" => "Crimson+Text",
  "Croissant One" => "Croissant+One",
  "Crushed" => "Crushed",
  "Cuprum" => "Cuprum",
  "Cutive" => "Cutive",
  "Cutive Mono" => "Cutive+Mono",
  "Damion" => "Damion",
  "Dancing Script" => "Dancing+Script",
  "Dangrek" => "Dangrek",
  "Dawning of a New Day" => "Dawning+of+a+New+Day",
  "Days One" => "Days+One",
  "Delius" => "Delius",
  "Delius Swash Caps" => "Delius+Swash+Caps",
  "Delius Unicase" => "Delius+Unicase",
  "Della Respira" => "Della+Respira",
  "Denk One" => "Denk+One",
  "Devonshire" => "Devonshire",
  "Didact Gothic" => "Didact+Gothic",
  "Diplomata" => "Diplomata",
  "Diplomata SC" => "Diplomata+SC",
  "Domine" => "Domine",
  "Donegal One" => "Donegal+One",
  "Doppio One" => "Doppio+One",
  "Dorsa" => "Dorsa",
  "Dosis" => "Dosis",
  "Dr Sugiyama" => "Dr+Sugiyama",
  "Droid Sans" => "Droid+Sans",
  "Droid Sans Mono" => "Droid+Sans+Mono",
  "Droid Serif" => "Droid+Serif",
  "Duru Sans" => "Duru+Sans",
  "Dynalight" => "Dynalight",
  "EB Garamond" => "EB+Garamond",
  "Eagle Lake" => "Eagle+Lake",
  "Eater" => "Eater",
  "Economica" => "Economica",
  "Electrolize" => "Electrolize",
  "Elsie" => "Elsie",
  "Elsie Swash Caps" => "Elsie+Swash+Caps",
  "Emblema One" => "Emblema+One",
  "Emilys Candy" => "Emilys+Candy",
  "Engagement" => "Engagement",
  "Englebert" => "Englebert",
  "Enriqueta" => "Enriqueta",
  "Erica One" => "Erica+One",
  "Esteban" => "Esteban",
  "Euphoria Script" => "Euphoria+Script",
  "Ewert" => "Ewert",
  "Exo" => "Exo",
  "Expletus Sans" => "Expletus+Sans",
  "Fanwood Text" => "Fanwood+Text",
  "Fascinate" => "Fascinate",
  "Fascinate Inline" => "Fascinate+Inline",
  "Faster One" => "Faster+One",
  "Fasthand" => "Fasthand",
  "Federant" => "Federant",
  "Federo" => "Federo",
  "Felipa" => "Felipa",
  "Fenix" => "Fenix",
  "Finger Paint" => "Finger+Paint",
  "Fjalla One" => "Fjalla+One",
  "Fjord One" => "Fjord+One",
  "Flamenco" => "Flamenco",
  "Flavors" => "Flavors",
  "Fondamento" => "Fondamento",
  "Fontdiner Swanky" => "Fontdiner+Swanky",
  "Forum" => "Forum",
  "Francois One" => "Francois+One",
  "Freckle Face" => "Freckle+Face",
  "Fredericka the Great" => "Fredericka+the+Great",
  "Fredoka One" => "Fredoka+One",
  "Freehand" => "Freehand",
  "Fresca" => "Fresca",
  "Frijole" => "Frijole",
  "Fruktur" => "Fruktur",
  "Fugaz One" => "Fugaz+One",
  "GFS Didot" => "GFS+Didot",
  "GFS Neohellenic" => "GFS+Neohellenic",
  "Gabriela" => "Gabriela",
  "Gafata" => "Gafata",
  "Galdeano" => "Galdeano",
  "Galindo" => "Galindo",
  "Gentium Basic" => "Gentium+Basic",
  "Gentium Book Basic" => "Gentium+Book+Basic",
  "Geo" => "Geo",
  "Geostar" => "Geostar",
  "Geostar Fill" => "Geostar+Fill",
  "Germania One" => "Germania+One",
  "Gilda Display" => "Gilda+Display",
  "Give You Glory" => "Give+You+Glory",
  "Glass Antiqua" => "Glass+Antiqua",
  "Glegoo" => "Glegoo",
  "Gloria Hallelujah" => "Gloria+Hallelujah",
  "Goblin One" => "Goblin+One",
  "Gochi Hand" => "Gochi+Hand",
  "Gorditas" => "Gorditas",
  "Goudy Bookletter 1911" => "Goudy+Bookletter+1911",
  "Graduate" => "Graduate",
  "Grand Hotel" => "Grand+Hotel",
  "Gravitas One" => "Gravitas+One",
  "Great Vibes" => "Great+Vibes",
  "Griffy" => "Griffy",
  "Gruppo" => "Gruppo",
  "Gudea" => "Gudea",
  "Habibi" => "Habibi",
  "Hammersmith One" => "Hammersmith+One",
  "Hanalei" => "Hanalei",
  "Hanalei Fill" => "Hanalei+Fill",
  "Handlee" => "Handlee",
  "Hanuman" => "Hanuman",
  "Happy Monkey" => "Happy+Monkey",
  "Headland One" => "Headland+One",
  "Henny Penny" => "Henny+Penny",
  "Herr Von Muellerhoff" => "Herr+Von+Muellerhoff",
  "Holtwood One SC" => "Holtwood+One+SC",
  "Homemade Apple" => "Homemade+Apple",
  "Homenaje" => "Homenaje",
  "IM Fell DW Pica" => "IM+Fell+DW+Pica",
  "IM Fell DW Pica SC" => "IM+Fell+DW+Pica+SC",
  "IM Fell Double Pica" => "IM+Fell+Double+Pica",
  "IM Fell Double Pica SC" => "IM+Fell+Double+Pica+SC",
  "IM Fell English" => "IM+Fell+English",
  "IM Fell English SC" => "IM+Fell+English+SC",
  "IM Fell French Canon" => "IM+Fell+French+Canon",
  "IM Fell French Canon SC" => "IM+Fell+French+Canon+SC",
  "IM Fell Great Primer" => "IM+Fell+Great+Primer",
  "IM Fell Great Primer SC" => "IM+Fell+Great+Primer+SC",
  "Iceberg" => "Iceberg",
  "Iceland" => "Iceland",
  "Imprima" => "Imprima",
  "Inconsolata" => "Inconsolata",
  "Inder" => "Inder",
  "Indie Flower" => "Indie+Flower",
  "Inika" => "Inika",
  "Irish Grover" => "Irish+Grover",
  "Istok Web" => "Istok+Web",
  "Italiana" => "Italiana",
  "Italianno" => "Italianno",
  "Jacques Francois" => "Jacques+Francois",
  "Jacques Francois Shadow" => "Jacques+Francois+Shadow",
  "Jim Nightshade" => "Jim+Nightshade",
  "Jockey One" => "Jockey+One",
  "Jolly Lodger" => "Jolly+Lodger",
  "Josefin Sans" => "Josefin+Sans",
  "Josefin Slab" => "Josefin+Slab",
  "Joti One" => "Joti+One",
  "Judson" => "Judson",
  "Julee" => "Julee",
  "Julius Sans One" => "Julius+Sans+One",
  "Junge" => "Junge",
  "Jura" => "Jura",
  "Just Another Hand" => "Just+Another+Hand",
  "Just Me Again Down Here" => "Just+Me+Again+Down+Here",
  "Kameron" => "Kameron",
  "Karla" => "Karla",
  "Kaushan Script" => "Kaushan+Script",
  "Kavoon" => "Kavoon",
  "Keania One" => "Keania+One",
  "Kelly Slab" => "Kelly+Slab",
  "Kenia" => "Kenia",
  "Khmer" => "Khmer",
  "Kite One" => "Kite+One",
  "Knewave" => "Knewave",
  "Kotta One" => "Kotta+One",
  "Koulen" => "Koulen",
  "Kranky" => "Kranky",
  "Kreon" => "Kreon",
  "Kristi" => "Kristi",
  "Krona One" => "Krona+One",
  "La Belle Aurore" => "La+Belle+Aurore",
  "Lancelot" => "Lancelot",
  "Lato" => "Lato",
  "League Script" => "League+Script",
  "Leckerli One" => "Leckerli+One",
  "Ledger" => "Ledger",
  "Lekton" => "Lekton",
  "Lemon" => "Lemon",
  "Libre Baskerville" => "Libre+Baskerville",
  "Life Savers" => "Life+Savers",
  "Lilita One" => "Lilita+One",
  "Limelight" => "Limelight",
  "Linden Hill" => "Linden+Hill",
  "Lobster" => "Lobster",
  "Lobster Two" => "Lobster+Two",
  "Londrina Outline" => "Londrina+Outline",
  "Londrina Shadow" => "Londrina+Shadow",
  "Londrina Sketch" => "Londrina+Sketch",
  "Londrina Solid" => "Londrina+Solid",
  "Lora" => "Lora",
  "Love Ya Like A Sister" => "Love+Ya+Like+A+Sister",
  "Loved by the King" => "Loved+by+the+King",
  "Lovers Quarrel" => "Lovers+Quarrel",
  "Luckiest Guy" => "Luckiest+Guy",
  "Lusitana" => "Lusitana",
  "Lustria" => "Lustria",
  "Macondo" => "Macondo",
  "Macondo Swash Caps" => "Macondo+Swash+Caps",
  "Magra" => "Magra",
  "Maiden Orange" => "Maiden+Orange",
  "Mako" => "Mako",
  "Marcellus" => "Marcellus",
  "Marcellus SC" => "Marcellus+SC",
  "Marck Script" => "Marck+Script",
  "Margarine" => "Margarine",
  "Marko One" => "Marko+One",
  "Marmelad" => "Marmelad",
  "Marvel" => "Marvel",
  "Mate" => "Mate",
  "Mate SC" => "Mate+SC",
  "Maven Pro" => "Maven+Pro",
  "McLaren" => "McLaren",
  "Meddon" => "Meddon",
  "MedievalSharp" => "MedievalSharp",
  "Medula One" => "Medula+One",
  "Megrim" => "Megrim",
  "Meie Script" => "Meie+Script",
  "Merienda" => "Merienda",
  "Merienda One" => "Merienda+One",
  "Merriweather" => "Merriweather",
  "Merriweather Sans" => "Merriweather+Sans",
  "Metal" => "Metal",
  "Metal Mania" => "Metal+Mania",
  "Metamorphous" => "Metamorphous",
  "Metrophobic" => "Metrophobic",
  "Michroma" => "Michroma",
  "Milonga" => "Milonga",
  "Miltonian" => "Miltonian",
  "Miltonian Tattoo" => "Miltonian+Tattoo",
  "Miniver" => "Miniver",
  "Miss Fajardose" => "Miss+Fajardose",
  "Modern Antiqua" => "Modern+Antiqua",
  "Molengo" => "Molengo",
  "Molle" => "Molle",
  "Monda" => "Monda",
  "Monofett" => "Monofett",
  "Monoton" => "Monoton",
  "Monsieur La Doulaise" => "Monsieur+La+Doulaise",
  "Montaga" => "Montaga",
  "Montez" => "Montez",
  "Montserrat" => "Montserrat",
  "Montserrat Alternates" => "Montserrat+Alternates",
  "Montserrat Subrayada" => "Montserrat+Subrayada",
  "Moul" => "Moul",
  "Moulpali" => "Moulpali",
  "Mountains of Christmas" => "Mountains+of+Christmas",
  "Mouse Memoirs" => "Mouse+Memoirs",
  "Mr Bedfort" => "Mr+Bedfort",
  "Mr Dafoe" => "Mr+Dafoe",
  "Mr De Haviland" => "Mr+De+Haviland",
  "Mrs Saint Delafield" => "Mrs+Saint+Delafield",
  "Mrs Sheppards" => "Mrs+Sheppards",
  "Muli" => "Muli",
  "Mystery Quest" => "Mystery+Quest",
  "Neucha" => "Neucha",
  "Neuton" => "Neuton",
  "New Rocker" => "New+Rocker",
  "News Cycle" => "News+Cycle",
  "Niconne" => "Niconne",
  "Nixie One" => "Nixie+One",
  "Nobile" => "Nobile",
  "Nokora" => "Nokora",
  "Norican" => "Norican",
  "Nosifer" => "Nosifer",
  "Nothing You Could Do" => "Nothing+You+Could+Do",
  "Noticia Text" => "Noticia+Text",
  "Nova Cut" => "Nova+Cut",
  "Nova Flat" => "Nova+Flat",
  "Nova Mono" => "Nova+Mono",
  "Nova Oval" => "Nova+Oval",
  "Nova Round" => "Nova+Round",
  "Nova Script" => "Nova+Script",
  "Nova Slim" => "Nova+Slim",
  "Nova Square" => "Nova+Square",
  "Numans" => "Numans",
  "Nunito" => "Nunito",
  "Odor Mean Chey" => "Odor+Mean+Chey",
  "Offside" => "Offside",
  "Old Standard TT" => "Old+Standard+TT",
  "Oldenburg" => "Oldenburg",
  "Oleo Script" => "Oleo+Script",
  "Oleo Script Swash Caps" => "Oleo+Script+Swash+Caps",
  "Open Sans" => "Open+Sans",
  "Open Sans Condensed" => "Open+Sans+Condensed",
  "Oranienbaum" => "Oranienbaum",
  "Orbitron" => "Orbitron",
  "Oregano" => "Oregano",
  "Orienta" => "Orienta",
  "Original Surfer" => "Original+Surfer",
  "Oswald" => "Oswald",
  "Over the Rainbow" => "Over+the+Rainbow",
  "Overlock" => "Overlock",
  "Overlock SC" => "Overlock+SC",
  "Ovo" => "Ovo",
  "Oxygen" => "Oxygen",
  "Oxygen Mono" => "Oxygen+Mono",
  "PT Mono" => "PT+Mono",
  "PT Sans" => "PT+Sans",
  "PT Sans Caption" => "PT+Sans+Caption",
  "PT Sans Narrow" => "PT+Sans+Narrow",
  "PT Serif" => "PT+Serif",
  "PT Serif Caption" => "PT+Serif+Caption",
  "Pacifico" => "Pacifico",
  "Paprika" => "Paprika",
  "Parisienne" => "Parisienne",
  "Passero One" => "Passero+One",
  "Passion One" => "Passion+One",
  "Patrick Hand" => "Patrick+Hand",
  "Patrick Hand SC" => "Patrick+Hand+SC",
  "Patua One" => "Patua+One",
  "Paytone One" => "Paytone+One",
  "Peralta" => "Peralta",
  "Permanent Marker" => "Permanent+Marker",
  "Petit Formal Script" => "Petit+Formal+Script",
  "Petrona" => "Petrona",
  "Philosopher" => "Philosopher",
  "Piedra" => "Piedra",
  "Pinyon Script" => "Pinyon+Script",
  "Pirata One" => "Pirata+One",
  "Plaster" => "Plaster",
  "Play" => "Play",
  "Playball" => "Playball",
  "Playfair Display" => "Playfair+Display",
  "Playfair Display SC" => "Playfair+Display+SC",
  "Podkova" => "Podkova",
  "Poiret One" => "Poiret+One",
  "Poller One" => "Poller+One",
  "Poly" => "Poly",
  "Pompiere" => "Pompiere",
  "Pontano Sans" => "Pontano+Sans",
  "Port Lligat Sans" => "Port+Lligat+Sans",
  "Port Lligat Slab" => "Port+Lligat+Slab",
  "Prata" => "Prata",
  "Preahvihear" => "Preahvihear",
  "Press Start 2P" => "Press+Start+2P",
  "Princess Sofia" => "Princess+Sofia",
  "Prociono" => "Prociono",
  "Prosto One" => "Prosto+One",
  "Puritan" => "Puritan",
  "Purple Purse" => "Purple+Purse",
  "Quando" => "Quando",
  "Quantico" => "Quantico",
  "Quattrocento" => "Quattrocento",
  "Quattrocento Sans" => "Quattrocento+Sans",
  "Questrial" => "Questrial",
  "Quicksand" => "Quicksand",
  "Quintessential" => "Quintessential",
  "Qwigley" => "Qwigley",
  "Racing Sans One" => "Racing+Sans+One",
  "Radley" => "Radley",
  "Raleway" => "Raleway",
  "Raleway Dots" => "Raleway+Dots",
  "Rambla" => "Rambla",
  "Rammetto One" => "Rammetto+One",
  "Ranchers" => "Ranchers",
  "Rancho" => "Rancho",
  "Rationale" => "Rationale",
  "Redressed" => "Redressed",
  "Reenie Beanie" => "Reenie+Beanie",
  "Revalia" => "Revalia",
  "Ribeye" => "Ribeye",
  "Ribeye Marrow" => "Ribeye+Marrow",
  "Righteous" => "Righteous",
  "Risque" => "Risque",
  "Roboto" => "Roboto",
  "Roboto Condensed" => "Roboto+Condensed",
  "Rochester" => "Rochester",
  "Rock Salt" => "Rock+Salt",
  "Rokkitt" => "Rokkitt",
  "Romanesco" => "Romanesco",
  "Ropa Sans" => "Ropa+Sans",
  "Rosario" => "Rosario",
  "Rosarivo" => "Rosarivo",
  "Rouge Script" => "Rouge+Script",
  "Ruda" => "Ruda",
  "Rufina" => "Rufina",
  "Ruge Boogie" => "Ruge+Boogie",
  "Ruluko" => "Ruluko",
  "Rum Raisin" => "Rum+Raisin",
  "Ruslan Display" => "Ruslan+Display",
  "Russo One" => "Russo+One",
  "Ruthie" => "Ruthie",
  "Rye" => "Rye",
  "Sacramento" => "Sacramento",
  "Sail" => "Sail",
  "Salsa" => "Salsa",
  "Sanchez" => "Sanchez",
  "Sancreek" => "Sancreek",
  "Sansita One" => "Sansita+One",
  "Sarina" => "Sarina",
  "Satisfy" => "Satisfy",
  "Scada" => "Scada",
  "Schoolbell" => "Schoolbell",
  "Seaweed Script" => "Seaweed+Script",
  "Sevillana" => "Sevillana",
  "Seymour One" => "Seymour+One",
  "Shadows Into Light" => "Shadows+Into+Light",
  "Shadows Into Light Two" => "Shadows+Into+Light+Two",
  "Shanti" => "Shanti",
  "Share" => "Share",
  "Share Tech" => "Share+Tech",
  "Share Tech Mono" => "Share+Tech+Mono",
  "Shojumaru" => "Shojumaru",
  "Short Stack" => "Short+Stack",
  "Siemreap" => "Siemreap",
  "Sigmar One" => "Sigmar+One",
  "Signika" => "Signika",
  "Signika Negative" => "Signika+Negative",
  "Simonetta" => "Simonetta",
  "Sintony" => "Sintony",
  "Sirin Stencil" => "Sirin+Stencil",
  "Six Caps" => "Six+Caps",
  "Skranji" => "Skranji",
  "Slackey" => "Slackey",
  "Smokum" => "Smokum",
  "Smythe" => "Smythe",
  "Sniglet" => "Sniglet",
  "Snippet" => "Snippet",
  "Snowburst One" => "Snowburst+One",
  "Sofadi One" => "Sofadi+One",
  "Sofia" => "Sofia",
  "Sonsie One" => "Sonsie+One",
  "Sorts Mill Goudy" => "Sorts+Mill+Goudy",
  "Source Code Pro" => "Source+Code+Pro",
  "Source Sans Pro" => "Source+Sans+Pro",
  "Special Elite" => "Special+Elite",
  "Spicy Rice" => "Spicy+Rice",
  "Spinnaker" => "Spinnaker",
  "Spirax" => "Spirax",
  "Squada One" => "Squada+One",
  "Stalemate" => "Stalemate",
  "Stalinist One" => "Stalinist+One",
  "Stardos Stencil" => "Stardos+Stencil",
  "Stint Ultra Condensed" => "Stint+Ultra+Condensed",
  "Stint Ultra Expanded" => "Stint+Ultra+Expanded",
  "Stoke" => "Stoke",
  "Strait" => "Strait",
  "Sue Ellen Francisco" => "Sue+Ellen+Francisco",
  "Sunshiney" => "Sunshiney",
  "Supermercado One" => "Supermercado+One",
  "Suwannaphum" => "Suwannaphum",
  "Swanky and Moo Moo" => "Swanky+and+Moo+Moo",
  "Syncopate" => "Syncopate",
  "Tangerine" => "Tangerine",
  "Taprom" => "Taprom",
  "Tauri" => "Tauri",
  "Telex" => "Telex",
  "Tenor Sans" => "Tenor+Sans",
  "Text Me One" => "Text+Me+One",
  "The Girl Next Door" => "The+Girl+Next+Door",
  "Tienne" => "Tienne",
  "Tinos" => "Tinos",
  "Titan One" => "Titan+One",
  "Titillium Web" => "Titillium+Web",
  "Trade Winds" => "Trade+Winds",
  "Trocchi" => "Trocchi",
  "Trochut" => "Trochut",
  "Trykker" => "Trykker",
  "Tulpen One" => "Tulpen+One",
  "Ubuntu" => "Ubuntu",
  "Ubuntu Condensed" => "Ubuntu+Condensed",
  "Ubuntu Mono" => "Ubuntu+Mono",
  "Ultra" => "Ultra",
  "Uncial Antiqua" => "Uncial+Antiqua",
  "Underdog" => "Underdog",
  "Unica One" => "Unica+One",
  "UnifrakturCook" => "UnifrakturCook",
  "UnifrakturMaguntia" => "UnifrakturMaguntia",
  "Unkempt" => "Unkempt",
  "Unlock" => "Unlock",
  "Unna" => "Unna",
  "VT323" => "VT323",
  "Vampiro One" => "Vampiro+One",
  "Varela" => "Varela",
  "Varela Round" => "Varela+Round",
  "Vast Shadow" => "Vast+Shadow",
  "Vibur" => "Vibur",
  "Vidaloka" => "Vidaloka",
  "Viga" => "Viga",
  "Voces" => "Voces",
  "Volkhov" => "Volkhov",
  "Vollkorn" => "Vollkorn",
  "Voltaire" => "Voltaire",
  "Waiting for the Sunrise" => "Waiting+for+the+Sunrise",
  "Wallpoet" => "Wallpoet",
  "Walter Turncoat" => "Walter+Turncoat",
  "Warnes" => "Warnes",
  "Wellfleet" => "Wellfleet",
  "Wendy One" => "Wendy+One",
  "Wire One" => "Wire+One",
  "Yanone Kaffeesatz" => "Yanone+Kaffeesatz",
  "Yellowtail" => "Yellowtail",
  "Yeseva One" => "Yeseva+One",
  "Yesteryear" => "Yesteryear",
  "Zeyada" => "Zeyada",
);

$versions = array(
  '1.0' => 'TALLUI Core V1.0',
);

$bundles = array(
  'full' => 'TALLUI Full Bundle',
  'cms' => 'TALLUI CMS Bundle',
  'admin' => 'TALLUI Admin Bundle',
  'no' => 'No Bundle',
);

$themes = array(
  'default' => 'TALLUI Default theme',
  'no' => 'No theme',
);

$extensions = [
  ["tui_admin", "TALLUI Admin Panel", true, true, true],
  ["tui_users", "TALLUI User Management", true, true, true],
  ["tui_permissions", "TALLUI Permissions", true, true, true],
  ["tui_frontend", "TALLUI Frontend Website", true, true, false],
];

$components = [
  ["tui_admin", "TALLUI Admin Panel", true, true, true],
  ["tui_users", "TALLUI User Management", true, true, true],
  ["tui_permissions", "TALLUI Permissions", true, true, true],
  ["tui_frontend", "TALLUI Frontend Website", true, true, false],
];

$databases = ["MySQL", "Postgres", "SQLite", "SQL Server"];

$fieldsets = [
  [
    [1, "title", "Project title", "e. g. TALLUI Open Source CMS", "text", "", true],
    [2, "claim", "Claim (optional)", "e. g. The free CMS & Blog for Laravel and the TALL-Stack.", "text", "", false],
    [3, "url", "URL (optional)", "e. g. https://tallui.io", "text", "", false],
    [4, "adminurl", "Admin URL (optional)", "e. g. https://tallui.io/cms, defaults to /admin", "text", "", false],
  ],
  [
    [1, "username", "Admin Username", "e. g. cmsadmin", "text", "", true],
    [2, "password", "Password", "a lot of stars", "password", "", true],
    [3, "adminmail", "Admin Email", "e. g. me@tallui.io", "email", "", true],
    [4, "firstname", "Firstname (optional)", "e. g. Admin", "text", "", false],
    [5, "lastname", "Lastname (optional)", "e. g. User", "text", "", false],
  ],
  [
    [1, "dbserver", "Database server", "", "select", $databases, true],
    [2, "dbhost", "Database host", "e. g. mysql.tallui.io", "text", "", true],
    [3, "dbuser", "Database user", "e. g. root", "text", "", true],
    [4, "dbpassword", "Database password", "enter some stars", "password", "", true],
    [5, "dbdatabase", "Database", "e. g. tallui", "text", "", true],
  ],
  [
    [1, "version", "TALLUI version", "", "select", $versions, true],
    [2, "bundle", "Bundle", "", "select", $bundles, true],
    [3, "theme", "Theme", "", "select", $themes, true],
    [5, "childtheme", "Child-theme", "Name your child-theme, leave blank for none", "text", "", false],
  ],
  [
    [1, "extensions", "Extensions", "", "multi", $extensions, true],
    [2, "components", "Components", "", "multi", $components, true],
  ],
  [
    [1, "seedpages", "Seed pages", "", "checkbox", $languages, false],
    [2, "seedblogposts", "Seed posts", "", "checkbox", $timezones, false],
  ],
  [
    [1, "language", "Language", "", "select", $languages, false],
    [2, "timezone", "Timezone", "", "select", $timezones, false],
  ],
  [
    [1, "logofile", "Logo", "Your logo, preferably SVG or PNG, also JPEG and GIF.", "file", "", false],
    [2, "googlefont", "Google font", "Choose a font type from Google Fonts.", "select", $googlefonts, false],
    [3, "primarycolor", "Primary color", "First color, used for UI elements like buttons.", "color", "", false],
    [4, "secondarycolor", "Seccondary color", "Second color, used somewhere else.", "color", "", false],
    [5, "backgroundcolor", "Background color", "Background color, used in light mode.", "color", "", false],
    [6, "textcolor", "Text color", "Font color, used in light mode.", "color", "", false],
  ],
];

$lastStep = end($steps);

/* Templates */

$stepHead = '<div class="min-h-full flex">
<div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
  <div class="mx-auto w-full max-w-sm lg:w-96">
    <div class="flex">
      <img class="h-12 w-auto mt-4 mr-2" src="logo.svg" alt="TALLUI">
        <div>
            <h2 class="mt-6 text-3xl text-gray-700">TALL<b>UI</b> Installer</h2>
            <p class="mt-2 text-sm text-gray-600">
                see
                <a href="#" class="font-medium text-cyan-600 hover:text-cyan-500"> TALL<b>UI</b> Docs </a>
            </p>
        </div>
    </div>

    <div class="mt-8">

      <div class="mt-6">
        <form action="' . $plain_url . '?step=' . $next_step . '" method="POST" class="space-y-6">';

$stepFoot = '<div><button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">Continue</button></div></form></div></div></div></div>';

?>

<!doctype html>
<html class="h-full bg-gray-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>TALLUI Installer - Open Source CMS for Laravel</title>

  <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            clifford: '#da373d',
          }
        }
      }
    }
  </script>

  <style type="text/tailwindcss">
    @layer utilities {
      .content-auto {
        content-visibility: auto;
      }
    }
  </style>

  <style>
    #image-caption A {
      border-bottom: 1px dotted;
    }
  </style>

</head>
<body class="h-full">

<?php

foreach ($steps as $step) {

    if ($current_step == $step['0']) {

        $gradient = $step[3];
        $opacity = $step[4];
        $background = $step[5];
        $caption = $step[6];
        $textcolor = $step[7];

        echo $stepHead;

        foreach ($fieldsets as $fieldset => $fieldsetvalue) {

            foreach ($fieldsetvalue as $field) {
              $fieldname = $field[1];

              if (isset($_POST[$fieldname])) {
                $_SESSION[$fieldname] = $_POST[$fieldname];
              }

            }

            if ($fieldset == $current_step - 1) {

                foreach ($fieldsetvalue as $field) {
                    $fieldname = $field[1];
                    $fieldtitle = $field[2];
                    $fieldplaceholder = $field[3];
                    $fieldtype = $field[4];
                    $fieldcontents = $field[5];

                    if ($field[6] == true) {
                        $fieldrequired = " required";
                    } else {
                        $fieldrequired = " ";
                    }
                    
                    $session_fieldvalue = "";
                    if (isset($_SESSION[$fieldname])) {
                      $session_fieldvalue = $_SESSION[$fieldname];
                    }

                    if ($fieldtype == "text" OR $fieldtype == "password" OR $fieldtype == "email") {
                      echo '<div><label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . ' </label><div class="mt-1">
                      <input value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="' . $fieldtype . '" placeholder="' . $fieldplaceholder . '"' . $fieldrequired . ' class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                      </div></div>';
                    }

                    if ($fieldtype == "checkbox") {
                      echo '<div class="mt-1 flex gap-2">
                      <input value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="' . $fieldtype . '" placeholder="' . $fieldplaceholder . '"' . $fieldrequired . ' class="appearance-none block border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 text-cyan-600 sm:text-sm mt-0.5">
                      <label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . ' </label></div>';
                    }

                    if ($fieldtype == "select") {
                      echo '<div><label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . ' </label><div class="mt-1">
                      <select value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="select" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">';

                      foreach ($fieldcontents as $fieldcontent) {
                        echo '<option value="3">' . $fieldcontent . '</option>';
                      }
                      
                      echo '</select>
                      </div></div>';
                    }

                    if ($fieldtype == "multi") {
                      echo '<div><label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . ' </label><div class="mt-1">
                      <select value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="select" multiple class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">';

                      foreach ($fieldcontents as $fieldcontent) {
                        echo '<option selected value="3">' . $fieldcontent[1] . '</option>';
                      }
                      
                      echo '</select>
                      </div></div>';
                    }

                    if ($fieldtype == "file") {
                      echo '<div><label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . '<br><span class="font-normal">' . $fieldplaceholder . '</span><br></label><div class="mt-1">
                      <input value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="' . $fieldtype . '" ' . $fieldrequired . ' class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                      </div></div>';
                    }

                    if ($fieldtype == "color") {
                      echo '<div class="flex justify-between"><label for="' . $fieldname . '" class="block text-sm font-medium text-gray-700"> ' . $fieldtitle . '<br><span class="font-normal">' . $fieldplaceholder . '</span></label>
                      <input value="' . $session_fieldvalue . '" id="' . $fieldname . '" name="' . $fieldname . '" type="' . $fieldtype . '" ' . $fieldrequired . ' class="form-control block px-1 py-0.5 mt-2 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                      </div>';
                    }
                }
            }
        }

        /* DEBUG Session and Post Data
        echo "<br><b>Session:</b><pre>";
        print_r($_SESSION);
        echo "</pre><br>";
        echo "<br><b>Post:</b><br><pre>";
        print_r($_POST);
        echo "</pre><br>";
        */
        
        echo $stepFoot;
    }
}

/*
TODO
- Finish Checkbox 
- Finish Multiselect (see Alpine.js or use a listing) - https://tailwind-elements.com/docs/standard/forms/multiselect/
- Pre-select packages and components by bundle
- Add approot and webroot to one of the first steps
- Check environment before (PHP Version, Limits and PHP Extensions)
- https://www.c-sharpcorner.com/article/write-and-append-data-in-json-file-using-php/
- Last: install from json (test but finish later)
    $fp = fopen('install.json', 'w');
    fwrite($fp, json_encode($response));
    fclose($fp);
- Make installing look great, add nice logging
- Send a beautiful mail if finished
- Refactor!
  - Sort the steps
    1. Pre-Checks
    2. Steps 1-x
    3. (Designer)
    4. Installing (with After-Checks)
    5. (Finished)
  - Get big arrays from APIs formed as JSON
  - Clean and sort code
*/


// Webserver: if Apache then mod_rewrite
// memory_limit 128 MB recommended for TYPO3
// GraphicsMagic, ImageMagick or GDLib
// Are approot and webroot writeable? (between?)
// run functions, add check memory_limit like https://stackoverflow.com/questions/10208698/checking-memory-limit-in-php

function check_memory_limit($required_memory) {
  
  $memory_limit = ini_get('memory_limit');
  if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
      if ($matches[2] == 'M') {
          $memory_limit = $matches[1] * 1024 * 1024;
      } else if ($matches[2] == 'K') {
          $memory_limit = $matches[1] * 1024;
      }
  }

  $memory_ok = ($memory_limit >= 64 * 1024 * 1024);

  return ($memory_ok ? 1 : 0);
}

function check_mod_rewrite() {
  // https://www.webune.com/forums/testing-script-to-test-mod-rewrite.html
  // https://stackoverflow.com/questions/9021425/how-to-check-if-mod-rewrite-is-enabled-in-php
  return 0;
}

$checkbefore = [
  [1, "PHP Version", 8.1, phpversion(), "The PHP Version must be higher than Version 8.1 to install TALLUI.", "required"],
  [2, "BCMath PHP Extension", 1, extension_loaded('bcmath'), "The PHP Extension BCMath is required to run TALLUI.", "required"],
  [3, "Ctype PHP Extension", 1, extension_loaded('ctype'), "The PHP Extension Ctype is required to run TALLUI.", "required"],
  [4, "Fileinfo PHP extension", 1, extension_loaded('fileinfo'), "The PHP Extension Fileinfo is required to run TALLUI.", "required"],
  [5, "JSON PHP Extension", 1, extension_loaded('json'), "The PHP Extension JSON is required to run TALLUI.", "required"],
  [6, "Mbstring PHP Extension", 1, extension_loaded('mbstring'), "The PHP Extension Mbstring is required to run TALLUI.", "required"],
  [7, "OpenSSL PHP Extension", 1, extension_loaded('openssl'), "The PHP Extension OpenSSL is required to run TALLUI.", "required"],
  [8, "PDO PHP Extension", 1, extension_loaded('pdo'), "The PHP Extension PDO is required to run TALLUI.", "required"],
  [9, "Tokenizer PHP Extension", 1, extension_loaded('tokenizer'), "The PHP Extension Tokenizer is required to run TALLUI.", "required"],
  [10, "XML PHP Extension", 1, extension_loaded('xml'), "The PHP Extension XML is required to run TALLUI.", "required"],
  [11, "PHP Memory Limit", 1, check_memory_limit('64'), "The PHP Memory Limit must be 64 MB or higher.", "required"],
  [12, "PHP Memory Limit", 1, check_memory_limit('128'), "A PHP Memory Limit of 128 MB or higher is recommended.", "recommended"],
  [13, "Apache mod_rewrite", 1, check_mod_rewrite(), "Mod-Rewrite or similar is required.", "recommended"],
];

/*
foreach ($checkbefore AS $check) {
  echo $check[1] . ": ";
  echo $check[3];
  if ($check[3] >= $check[2]) {
    echo " - passed " . $check[4];
  } else {
    echo " - failed! " . $check[4];
  }
  echo "<br>";
};

$checkbetween = [
  [""],
];

$checkafter = [
  [""],
];
*/

?>


<div class="hidden lg:block relative w-0 flex-1">
<div class="z-20 absolute h-full w-full bg-gradient-to-r <?php echo $gradient ?>"></div>
<img class="z-10 absolute inset-0 h-full w-full object-cover opacity-<?php echo  $opacity  ?>" src="<?php echo $background  ?>" alt="">
<div class="z-40 absolute bottom-0 right-0 p-3 <?php echo $textcolor ?>" id="image-caption"><?php echo $caption ?></div>

<div class="z-30 h-full flex items-center ml-20">

<nav aria-label="Progress">
  <ol role="list" class="overflow-hidden">
    <li class="relative z-30 pb-10">

      <?php 

        foreach ($steps as $step => $val) {

            if ($val == $lastStep) {
                echo '<li class="relative z-30">';
            } else {
                echo '<li class="relative z-30 pb-10">';
            }

            if ($current_step > $val['0']) {

                echo '
                <div class="-ml-px absolute mt-0.5 top-4 left-4 w-0.5 h-full bg-cyan-600" aria-hidden="true"></div>
                <a href="' . $plain_url . '?step=' . $val['0'] . '" class="relative flex items-start group">
                  <span class="h-9 flex items-center">
                    <span class="relative z-10 w-8 h-8 flex items-center justify-center bg-cyan-600 rounded-full group-hover:bg-cyan-800">
                      <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>';

            } elseif ($current_step == $val['0']) {

                echo '
                <div class="-ml-px absolute mt-0.5 top-4 left-4 w-0.5 h-full bg-gray-300" aria-hidden="true"></div>
                <a href="#" class="relative flex items-start group" aria-current="step">
                  <span class="h-9 flex items-center" aria-hidden="true">
                    <span class="relative z-10 w-8 h-8 flex items-center justify-center bg-white border-2 border-cyan-600 rounded-full">
                      <span class="h-2.5 w-2.5 bg-cyan-600 rounded-full"></span>';

            } else {

                echo '
                <div class="-ml-px absolute mt-0.5 top-4 left-4 w-0.5 h-full bg-gray-300" aria-hidden="true"></div>
                <a href="#" class="relative flex items-start group">
                  <span class="h-9 flex items-center" aria-hidden="true">
                    <span class="relative z-10 w-8 h-8 flex items-center justify-center bg-white border-2 border-gray-300 rounded-full group-hover:border-gray-400">
                      <span class="h-2.5 w-2.5 bg-transparent rounded-full group-hover:bg-gray-300"></span>';

            };

            echo '
                  </span>
                </span>
                <span class="ml-4 min-w-0 flex flex-col">
                  <span class="text-xs font-semibold tracking-wide uppercase">' . $val['1'] . '</span>
                  <span class="text-sm text-gray-500">' . $val['2'] . '</span>
                </span>
              </a>
            </li>';


        };

        ?>

  </ol>
</nav></div></div></div></body></html>