# Idea

https://github.com/xalaida/laravel-geonames
https://github.com/michaeldrennen/Geonames

## Changes

Languages

-   native_name -> native_names - explain
-   other_names added - explain

Countries

-   native_name -> native_names - explain
-   other_names added - explain
-   driving_side added
-   tax_information
-   business_hours

Currencies

-   native_names added - explain
-   other_names added - explain
-   subunit_name
-   subunit_to_unit
-   is_active_subunit
-   subunit_native_names
-   subunit_exonyms
-   is_virtual

is_virtual, yes, but are there countries using them?
subunit_decimal_places AND subunit_to_unit?

Todo: Tax, Business hours, address format

```json
{
    "code": "BHD",
    "common_name": "Bahraini Dinar",
    "symbol": "BD",
    "subunit": "Fils",
    "subunit_decimal_places": 3,
    "native_names": { "ar": "دينار بحريني" },
    "other_names": { "historical": "Gulf Rupee" }
}
```

```json
{
    "common_name": "German",
    "native_names": {
        "deu": {
            "common": "Deutsch",
            "official": "Hochdeutsch"
        }
    },
    "exonyms": {
        "eng": "German",
        "fra": "Allemand",
        "spa": "Alemán",
        "zho": "德语",
        "rus": "Немецкий"
    },
    "alternative_names": {
        "historical": "Old High German",
        "dialect": "Alemannic",
        "nickname": "Language of poets and thinkers"
    }
}
```

```json
	Schema::create('static_countries_currencies', function (Blueprint $table) {
	$table->id();
	$table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
	$table->foreignId('currency_id')->constrained('static_currencies')->onDelete('cascade');
	$table->boolean('is_primary')->default(false); // For the default or most widely used currency
	$table->date('valid_from')->nullable(); // Start date for currency usage
	$table->date('valid_to')->nullable(); // End date for currency usage
	$table->unique(['country_id', 'currency_id']); $table->timestamps();
});
```

## Languages

```sql
CREATE TABLE static_languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alpha2 VARCHAR(2) NOT NULL UNIQUE,
    alpha3_b VARCHAR(3) DEFAULT NULL,
    alpha3_t VARCHAR(3) DEFAULT NULL,
    common_name VARCHAR(255) NOT NULL,
    official_name VARCHAR(255) NOT NULL,
    script ENUM('Latin', 'Cyrillic', 'Arabic', 'Devanagari', 'Other') NOT NULL,
    direction ENUM('LTR', 'RTL') NOT NULL,
    native_names JSON DEFAULT NULL
	other_names JSON DEFAULT NULL
    exonyms JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticLanguagesTable extends Migration
{
    public function up()
    {
        Schema::create('static_languages', function (Blueprint $table) {
            $table->id();
            $table->string('alpha2', 2)->unique();
            $table->string('alpha3_b', 3)->nullable();
            $table->string('alpha3_t', 3)->nullable();
            $table->string('common_name');
            $table->enum('script', ['Latin', 'Cyrillic', 'Arabic', 'Devanagari', 'Other']);
            $table->enum('direction', ['LTR', 'RTL']);
            $table->json('native_names')->nullable();
            $table->json('other_names')->nullable();
            $table->json('exonyms')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_languages');
    }
}
```

### Exonyms

Alternate names for the language in other languages.

```JSON
{
  "en": "German",
  "fr": "Allemand",
  "es": "Alemán"
}
```

## Countries

```sql
CREATE TABLE static_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alpha2 VARCHAR(2) NOT NULL UNIQUE,
    alpha3_b VARCHAR(3) DEFAULT NULL,
    alpha3_t VARCHAR(3) DEFAULT NULL,
    common_name VARCHAR(255) NOT NULL,
    native_names JSON DEFAULT NULL
	other_names JSON DEFAULT NULL
    exonyms JSON DEFAULT NULL,
    region ENUM('Africa', 'Americas', 'Asia', 'Europe', 'Oceania', 'Antarctica') DEFAULT NULL,
    subregion ENUM(
        'Northern Africa', 'Sub-Saharan Africa', 'Eastern Africa', 'Middle Africa',
        'Southern Africa', 'Western Africa', 'Latin America and the Caribbean',
        'Northern America', 'Caribbean', 'Central America', 'South America',
        'Central Asia', 'Eastern Asia', 'South-Eastern Asia', 'Southern Asia',
        'Western Asia', 'Eastern Europe', 'Northern Europe', 'Southern Europe',
        'Western Europe', 'Australia and New Zealand', 'Melanesia', 'Micronesia',
        'Polynesia'
    ) DEFAULT NULL,
    calling_code SMALLINT DEFAULT NULL,
    capital VARCHAR(255) DEFAULT NULL,
    population BIGINT DEFAULT NULL,
    area FLOAT DEFAULT NULL,
    links JSON DEFAULT NULL,
    tlds JSON DEFAULT NULL,
    membership JSON DEFAULT NULL,
    embargo BOOLEAN DEFAULT FALSE,
    embargo_data JSON DEFAULT NULL,
    address_format JSON DEFAULT NULL,
    postal_code_regex VARCHAR(255) DEFAULT NULL,
    dialing_prefix VARCHAR(10) DEFAULT NULL,
    phone_number_formatting JSON DEFAULT NULL,
    date_format VARCHAR(10) DEFAULT 'YYYY-MM-DD',
    currency_format JSON DEFAULT NULL
	driving_side ENUM('left', 'right') NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticCountriesTable extends Migration
{
    public function up()
    {
        Schema::create('static_countries', function (Blueprint $table) {
            $table->id();
            $table->string('alpha2', 2)->unique();
            $table->string('alpha3_b', 3)->nullable();
            $table->string('alpha3_t', 3)->nullable();
            $table->string('common_name');
            $table->json('native_names')->nullable();
            $table->json('other_names')->nullable();
            $table->json('exonyms')->nullable();
            $table->enum('region', ['Africa', 'Americas', 'Asia', 'Europe', 'Oceania', 'Antarctica'])->nullable();
            $table->enum('subregion', [
                'Northern Africa', 'Sub-Saharan Africa', 'Eastern Africa', 'Middle Africa',
                'Southern Africa', 'Western Africa', 'Latin America and the Caribbean',
                'Northern America', 'Caribbean', 'Central America', 'South America',
                'Central Asia', 'Eastern Asia', 'South-Eastern Asia', 'Southern Asia',
                'Western Asia', 'Eastern Europe', 'Northern Europe', 'Southern Europe',
                'Western Europe', 'Australia and New Zealand', 'Melanesia', 'Micronesia',
                'Polynesia'
            ])->nullable();
            $table->smallInteger('calling_code')->nullable();
            $table->string('capital')->nullable();
            $table->bigInteger('population')->nullable();
            $table->float('area')->nullable();
            $table->json('links')->nullable();
            $table->json('tlds')->nullable();
            $table->json('membership')->nullable();
            $table->boolean('embargo')->default(false);
            $table->json('embargo_data')->nullable();
            $table->json('address_format')->nullable();
            $table->string('postal_code_regex')->nullable();
            $table->string('dialing_prefix', 10)->nullable();
            $table->json('phone_number_length')->nullable();
            $table->string('date_format', 10)->default('YYYY-MM-DD');
            $table->json('currency_format')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_countries');
    }
}
```

### Exonyms

Alternate names for the country in different languages.

```JSON
{
  "en": "Germany",
  "fr": "Allemagne",
  "es": "Alemania"
}
```

### Links

URLs related to the country.

```JSON
{
  "wiki": "https://en.wikipedia.org/wiki/Germany",
  "official": "https://www.bundesregierung.de"
}
```

### TLDs

Top-level domains for the country. Do not add .eu for example, as it is not clearly connected to Germany.

```JSON
{
	".de": "Official Toplevel Domain for Germany",
	".berlin": "Toplevel Domain for the capital of Germany",
	".cologne": "Toplevel Domain for the German city Cologne"
}
```

### Membership

Memberships in international organizations.

```JSON
["EU", "UN", "NATO"]
```

### Embargo Data

Detailed embargo information.

```JSON
[
  {
    "type": "arms",
    "start_date": "2017-08-01",
    "end_date": null,
    "reason": "Nuclear program violations",
    "origin": "UN"
  },
  {
    "type": "travel",
    "start_date": "2017-09-01",
    "end_date": null,
    "reason": "Does not play well with tourists",
    "origin": "US Gov"
  }
]
```

### Address Format

Country-specific address formatting.

Not finished! Need to implement PO Box, Services like Poste Bastante and areas without formal addresses using Coordinates or Landmarks as well as handle edge cases like houses without numbers in Spain.

Unfinished example (not valid in Germany as Coords or Landmarks are not supported):

```JSON
{
  "format": [
    "Company",
    "AddressDetail",
    "Salutation Name",
    "Division",
    "PrimaryDeliveryField",
    "PostalCode City",
    "Country"
  ],
  "rules": {
    "PrimaryDeliveryField": [
      {
        "type": "StreetAddress",
        "fields": ["Street", "HouseNumber"],
        "example": "Main Street 5"
      },
      {
        "type": "POBox",
        "fields": ["POBox"],
        "example": "PO Box 123"
      },
      {
        "type": "PosteRestante",
        "fields": ["PosteRestante"],
        "example": "Poste Restante"
      },
      {
        "type": "GeographicCoordinates",
        "fields": ["Latitude", "Longitude"],
        "example": "48.775845, 9.182932"
      }
    ],
    "PostalCodeRegex": "^[0-9]{5}$",
    "mandatory": ["PostalCode", "City", "Country"],
    "example": [
      "heco GmbH",
      "Headquarter",
      "Mr. Alf Drollinger",
      "Web Division",
      "Main Street 5",
      "75334 Remchingen",
      "GERMANY"
    ]
  }
}
```

OLD - For Germany:

```json
{
    "format": [
        "Company",
        "AddressDetail",
        "Salutation Name",
        "Division",
        "Street HouseNumber",
        "PostalCode City",
        "Country"
    ],
    "rules": {
        "PostalCodeRegex": "^[0-9]{5}$",
        "mandatory": ["Street", "HouseNumber", "PostalCode", "City", "Country"],
        "example": [
            "heco GmbH",
            "Headquarter",
            "Mr. Alf Drollinger",
            "Web Division",
            "Am Eisengraben 5",
            "75334 Remchingen",
            "GERMANY"
        ]
    }
}
```

OLD - For the US:

```JSON
{
  "format": [
    "Name",
    "Company",
    "Division",
    "AddressDetail",
    "Street Address",
    "City State ZIP",
    "Country"
  ],
  "rules": {
    "PostalCodeRegex": "^[0-9]{5}(-[0-9]{4})?$",
    "mandatory": [
      "Street Address",
      "City",
      "State",
      "ZIP",
      "Country"
    ],
    "example": [
      "John Doe",
      "IBM",
      "Finance Division",
      "Building 5",
      "123 Main Street",
      "Rochester NY 14623",
      "UNITED STATES"
    ]
  }
}
```

OLD - For Japan:

```JSON
{
  "format": [
    "PostalCode",
    "Prefecture City",
    "Division",
    "AddressDetail",
    "Street HouseNumber",
    "Name",
    "Company",
    "Country"
  ],
  "rules": {
    "PostalCodeRegex": "^[0-9]{3}-[0-9]{4}$",
    "mandatory": [
      "PostalCode",
      "Prefecture",
      "City",
      "Street",
      "HouseNumber",
      "Country"
    ],
    "example": [
      "123-4567",
      "Tokyo-to Chiyoda-ku",
      "IT Department",
      "Building C",
      "Marunouchi 1-1",
      "Katsuhiro Yamada",
      "Yamada Corp",
      "JAPAN"
    ]
  }
}
```

OLD - For India:

```JSON
{
  "format": [
    "Name",
    "Company",
    "Division",
    "AddressDetail",
    "Street Address",
    "Locality",
    "City State PostalCode",
    "Country"
  ],
  "rules": {
    "PostalCodeRegex": "^[1-9][0-9]{5}$",
    "mandatory": [
      "Street Address",
      "City",
      "State",
      "PostalCode",
      "Country"
    ],
    "example": [
      "Rahul Sharma",
      "Infosys Ltd",
      "HR Division",
      "Building 44A",
      "Plot 44, Sector 30",
      "Hitech City",
      "Hyderabad Telangana 500081",
      "INDIA"
    ]
  }
}

```

OLD - For UK:

```JSON
{
  "format": [
    "Name",
    "Company",
    "Division",
    "AddressDetail",
    "Street Address",
    "Locality",
    "Town County PostalCode",
    "Country"
  ],
  "rules": {
    "PostalCodeRegex": "^[A-Z]{1,2}[0-9][A-Z0-9]?\\s?[0-9][A-Z]{2}$",
    "mandatory": [
      "Street Address",
      "Town",
      "PostalCode",
      "Country"
    ],
    "example": [
      "Emma Watson",
      "Hogwarts School",
      "Admissions Office",
      "Building D",
      "4 Privet Drive",
      "Little Whinging",
      "Surrey GU22 7LF",
      "UNITED KINGDOM"
    ]
  }
}
```

OLD - For China:

```JSON
{
  "format": [
    "PostalCode",
    "Province City District",
    "Street HouseNumber",
    "AddressDetail",
    "Division",
    "Name",
    "Company",
    "Country"
  ],
  "rules": {
    "PostalCodeRegex": "^[0-9]{6}$",
    "mandatory": [
      "PostalCode",
      "Province",
      "City",
      "District",
      "Street",
      "HouseNumber",
      "Country"
    ],
    "example": [
      "100000",
      "Beijing Chaoyang District",
      "Chang'an Avenue 10",
      "Building 3, Room 202",
      "Sales Department",
      "Li Wei",
      "Alibaba Group",
      "CHINA"
    ]
  }
}
```

### Phone Number Formatting

Minimum and maximum length of phone numbers.

```json
{
    "format": {
        "country_code": "+49",
        "area_code_length": [2, 5],
        "subscriber_number_length": [4, 10],
        "extension": true
    },
    "examples": ["+49 7248 21312", "+49 7248 932180-123", "+49 170 2343434"]
}
```

### Currency Format

Currency display formatting rules.

```json
{
    "symbol_position": "prefix",
    "decimal_separator": ".",
    "thousand_separator": ","
}
```

## Locales

```sql
CREATE TABLE static_locales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    country_id INT NOT NULL,
    locale VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_official_language BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (language_id) REFERENCES static_languages(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES static_countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticLocalesTable extends Migration
{
    public function up()
    {
        Schema::create('static_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('static_languages')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->string('locale', 5);
            $table->string('name');
            $table->boolean('is_official_language')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_locales');
    }
}
```

## Currencies

```sql
CREATE TABLE static_currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    common_name VARCHAR(255) NOT NULL,
    native_names JSON DEFAULT NULL
	other_names JSON DEFAULT NULL
    symbol VARCHAR(10) DEFAULT NULL,
    exonyms JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticCurrenciesTable extends Migration
{
    public function up()
    {
        Schema::create('static_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('common_name');
            $table->json('native_names')->nullable();
            $table->json('other_names')->nullable();
            $table->string('symbol', 10)->nullable();
            $table->json('exonyms')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_currencies');
    }
}
```

### Exonyms

Alternate names for the currency in other languages.

```json
{
    "en": "Dollar",
    "de": "US-Dollar",
    "fr": "Dollar américain",
    "ru": "Доллар",
    "ar": "دولار أمريكي"
}
```

## Countries Currencies

```sql
CREATE TABLE static_countries_currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    currency_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (country_id) REFERENCES static_countries(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES static_currencies(id) ON DELETE CASCADE,
    UNIQUE KEY (country_id, currency_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticCountriesCurrenciesTable extends Migration
{
    public function up()
    {
        Schema::create('static_countries_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('static_currencies')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->unique(['country_id', 'currency_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_countries_currencies');
    }
}
```

## Timezones

```sql
CREATE TABLE static_timezones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    offset_standard VARCHAR(6) NOT NULL,
    offset_dst VARCHAR(6) DEFAULT NULL,
    dst BOOLEAN DEFAULT FALSE,
    dst_start DATE DEFAULT NULL,
    dst_end DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticTimezonesTable extends Migration
{
    public function up()
    {
        Schema::create('static_timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('offset_standard', 6);
            $table->string('offset_dst', 6)->nullable();
            $table->boolean('dst')->default(false);
            $table->date('dst_start')->nullable();
            $table->date('dst_end')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_timezones');
    }
}
```

## Country Timezones

```sql
CREATE TABLE static_country_timezones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    timezone_id INT NOT NULL,
    FOREIGN KEY (country_id) REFERENCES static_countries(id) ON DELETE CASCADE,
    FOREIGN KEY (timezone_id) REFERENCES static_timezones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticCountryTimezonesTable extends Migration
{
    public function up()
    {
        Schema::create('static_country_timezones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->foreignId('timezone_id')->constrained('static_timezones')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_country_timezones');
    }
}
```

## Country Borders

```sql
CREATE TABLE static_country_borders (
    country_id INT NOT NULL,
    border_country_id INT NOT NULL,
    FOREIGN KEY (country_id) REFERENCES static_countries(id) ON DELETE CASCADE,
    FOREIGN KEY (border_country_id) REFERENCES static_countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaticCountryBordersTable extends Migration
{
    public function up()
    {
        Schema::create('static_country_borders', function (Blueprint $table) {
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->foreignId('border_country_id')->constrained('static_countries')->onDelete('cascade');
            $table->primary(['country_id', 'border_country_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('static_country_borders');
    }
}
```

## Geodata (GIS) - other package

-   country_id
-   latitude
-   longitude
-   geometry

```sql

```

```php

```

## Regional / Country specific packages

country_specific data like postal codes, caller prefixes etc. will go into separate packages and allow to build efficient help for filling out forms with address information

```sql

```

```php

```

#### **3. Fields**

1. **`static_countries`**

    - `population` and `area` could benefit from **units clarification** (e.g., `population` is total people, `area` is in square kilometers).
    - **`region` and `subregion`**:
        - Consider enumerating standard region names (e.g., Africa, Europe, Asia).
        - Use established standards like [UN M49](https://unstats.un.org/unsd/methodology/m49/) for consistency.
    - **`links` (JSON)**:
        - Ensure this field is structured (e.g., `wiki`, `official_site`, `factbook`) for easier parsing.

2. **`static_locales`**

    - The `is_main_language` boolean is well-placed here for defining primary languages.

3. **`static_languages`**

    - **`script`**:
        - You might want to expand this field to include more specific scripts (e.g., Greek, Japanese, Chinese).
        - If “Other” suffices for now, ensure documentation clarifies when it applies.

4. **`embargo` Fields**

    - **`embargo` (boolean)**: Perfect for quick filtering.
    - **`embargo_data` (JSON)**:
        - Ensure a clear structure (e.g., `type`, `start_date`, `end_date`, `reason`, `origin`) for consistent usage.

https://github.com/datasets/language-codes/blob/main/data/language-codes-full.csv
https://github.com/haliaeetus/iso-639/blob/master/data/iso_639-2.csv
https://github.com/haliaeetus/iso-639/blob/master/data/iso_639-1.csv
https://github.com/cristiroma/countries
https://github.com/annexare/Countries

1. **Calling Codes**:

    - [ITU (International Telecommunication Union)](https://www.itu.int/)
    - [Wikipedia Country Calling Codes](https://en.wikipedia.org/wiki/List_of_country_calling_codes)

2. **TLDs**:

    - IANA Root Zone Database

3. **Timezones**:

    - IANA Time Zone Database

4. **Geo Data**:

    - [GeoNames](https://www.geonames.org/)
    - [Natural Earth Data](https://www.naturalearthdata.com/)

5. **Memberships**:

    - Wikipedia pages for organizations like NATO, ASEAN, and EU.

6. **Population, Area, Capital**:

    - [World Bank Open Data](https://data.worldbank.org/)
    - [UN Data](http://data.un.org/)

7. **Links**:

    - Wikipedia, CIA World Factbook, and official government websites.

https://restcountries.com/v3.1/all?fields=name

then

https://restcountries.com/v3.1/name/South%20Georgia
https://restcountries.com/v3.1/name/Switzerland

https://apilayer.com/ and https://countrylayer.com/

-   Languages - https://restcountries.com/
-   Countries - https://restcountries.com/#endpoints-all
-   Currencies - https://restcountries.com/
-   Timezones - https://timezonedb.com/download - Achtung Lizenz, lizenzfrei siehe https://www.iana.org/time-zones und https://data.iana.org/time-zones/tz-link.html

https://github.com/nevadskiy/laravel-geonames

-   Timezones - https://timezonedb.com/download

-   beide in Moox packen und verdrahten
-   icons folder
    -   languages
        -   circle
        -   ...
    -   countries
        -   circle
        -   ...
-   Iterationen ...

moox/locate

https://github.com/tomatophp/filament-locations/

-   Area (Continent, Subcontinent, Economic Area)
-   Country
-   Region (Federal states etc.)
-   Config:
    -   countries
        -   use_flag_style = circle
    -   languages
        -   use_flag_style = square
    -   areas
        -   use_flag_style = ...

moox/flags

-   https://de.wikipedia.org/wiki/Liste_der_ISO-639-Sprachcodes vs https://en.wikipedia.org/wiki/IETF_language_tag
-
-   https://flagpedia.net/us-states
-   https://flagpedia.net/organization
-   https://www.countryflags.com/
-   https://github.com/hampusborgos/country-flags
-   https://github.com/Yummygum/flagpack-core - https://flagpack.xyz/
-   https://nucleoapp.com/svg-flag-icons
-   https://flagicons.lipis.dev/
-   https://hatscripts.github.io/circle-flags/ - https://github.com/HatScripts/circle-flags/
-   https://github.com/kapowaz/square-flags

-   Blade Flags https://github.com/MohmmedAshraf/blade-flags News https://laravel-news.com/laravel-blade-country-language-icons und Video https://www.youtube.com/watch?v=XTnKU-VCOB8
-   https://github.com/stijnvanouplines/blade-country-flags other one

Versions:

-   Circle - https://github.com/HatScripts/circle-flags/
-   Rounded
-   Square
-   Rectangled
-   Original - https://github.com/hampusborgos/country-flags

https://github.com/propaganistas/laravel-phone, see https://laravel-phone.herokuapp.com/
https://filamentphp.com/plugins/ysfkaya-phone-input
https://filamentphp.com/plugins/ysfkaya-phone-input
https://filamentphp.com/plugins/ariaieboy-currency
https://filamentphp.com/plugins/tapp-network-timezone-field
https://filamentphp.com/plugins/omar-haris-timezone-field
https://filamentphp.com/plugins/parfaitementweb-country-field
https://filamentphp.com/plugins/mohammadhprp-ip-to-country-flag
https://filamentphp.com/plugins/marjose123-lockscreen
https://filamentphp.com/plugins/pelmered-money-field

-   Moox Data Areas - Countries, Languages and Currencies for Filament
    -   Continents
    -   Subcontinents
    -   Countries
    -   Languages
    -   Currencies
    -   country-languages
    -   country_currencies
    -   countries_subcontinent
    -   continent_subcontinents
-   Moox Data Germany - German area data for Filament
    -   RegionsDe
    -   SubregionsDe
    -   ZipDe
    -   PhoneDe
    -   region_de_subregions_de
    -   subregion_de_zips_de
    -   phones_de_zips_de

## Sanktionen

-   https://www.sanctions.io/
-   https://www.un.org/securitycouncil/content/un-sc-consolidated-list
-   https://www.sanktionslisten-screening.one/
-   https://www.sanctionsmap.eu/#/main
-   https://data.europa.eu/data/datasets/consolidated-list-of-persons-groups-and-entities-subject-to-eu-financial-sanctions?locale=en

## Static Data for Salutation

-   Moox Data Core

    -   Gender (Title, Slug, Icon, Active)
        -   Neutral
    -   Salutation

        -   None
        -   Ms.
        -   Mr.
        -   Mx.

    -   Honorific Title (Title, Slug, Icon, Active) - languages?
        -   Dr.
        -   Prof.
        -   Sir
        -   Dame
        -   Hon.
        -   Rev.

Salutation config

if ($salutation == 'none') {
$greeting = 'Dear ' . $firstname . " " . $lastname;
}
