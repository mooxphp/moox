<?php

/**
 * ******************************************************************
 * ************************ TALLUI Installer ************************
 *
 * @see https://tallui.io/docs/install
 *
 * @version 0.1
 *
 * @author alf.drollinger@tallui.io
 *
 * */
session_start();

/** Settings */
$prechecks = [
    [1, 'PHP Version', 8.1, phpversion(), 'The PHP Version must be version 8.1 or higher to install TALLUI.', 'required'],
    [2, 'BCMath PHP Extension', 1, extension_loaded('bcmath'), 'The PHP Extension BCMath is required to run TALLUI.', 'required'],
    [3, 'Ctype PHP Extension', 1, extension_loaded('ctype'), 'The PHP Extension Ctype is required to run TALLUI.', 'required'],
    [4, 'Fileinfo PHP extension', 1, extension_loaded('fileinfo'), 'The PHP Extension Fileinfo is required to run TALLUI.', 'required'],
    [5, 'JSON PHP Extension', 1, extension_loaded('json'), 'The PHP Extension JSON is required to run TALLUI.', 'required'],
    [6, 'Mbstring PHP Extension', 1, extension_loaded('mbstring'), 'The PHP Extension Mbstring is required to run TALLUI.', 'required'],
    [7, 'OpenSSL PHP Extension', 1, extension_loaded('openssl'), 'The PHP Extension OpenSSL is required to run TALLUI.', 'required'],
    [8, 'PDO PHP Extension', 1, extension_loaded('pdo'), 'The PHP Extension PDO is required to run TALLUI.', 'required'],
    [9, 'Tokenizer PHP Extension', 1, extension_loaded('tokenizer'), 'The PHP Extension Tokenizer is required to run TALLUI.', 'required'],
    [10, 'XML PHP Extension', 1, extension_loaded('xml'), 'The PHP Extension XML is required to run TALLUI.', 'required'],
    [11, 'PHP Memory Limit', 1, check_memory_limit('64'), 'The PHP Memory Limit must be 64 MB or higher.', 'required'],
    [12, 'PHP Memory Limit', 1, check_memory_limit('128'), 'A PHP Memory Limit of 128 MB or higher is recommended.', 'recommended'],
    [13, 'Rewrite engine', 1, check_mod_rewrite(), 'Apache mod_rewrite or similar is required.', 'recommended'],
];

/** HTML Head */
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
/** Prechecks */
function check_memory_limit(mixed $required_memory):mixed
{
    $memory_limit = ini_get('memory_limit');
    if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
        $memory = (int) $matches[1];
        if ($matches[2] == 'M') {
            $memory_limit = $memory * 1024 * 1024;
        } elseif ($matches[2] == 'K') {
            $memory_limit = $memory * 1024;
        }
    }

    $memory_ok = ($memory_limit >= 64 * 1024 * 1024);

    return $memory_ok ? 1 : 0;
}

function check_mod_rewrite():int
{
    // https://www.webune.com/forums/testing-script-to-test-mod-rewrite.html
    // https://stackoverflow.com/questions/9021425/how-to-check-if-mod-rewrite-is-enabled-in-php
    return 0;
}

echo '
<div class="mx-auto max-w-7xl sm:px-2 md:px-6 lg:px-8">

<img class="absolute inset-0 object-cover w-full h-full" src="https://images.unsplash.com/photo-1505904267569-f02eaeb45a4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1908&q=80" alt="">

<div class="relative m-5 lg:m-20">

    <div class="flex">
        <img class="w-auto h-12 mt-4 mr-2" src="logo.svg" alt="TALLUI">
        <div>
            <h2 class="mt-6 text-3xl text-gray-700">TALL<b>UI</b> Installer</h2>
            <div class="mt-2 text-gray-600 mb-7">
                see
                <a href="#" class="font-medium text-cyan-600 hover:text-cyan-500"> TALL<b>UI</b> Docs </a>
                <br>
                <br>
                <b>We are pre-checking your environment. Done in a second ...</b>
            </div>
        </div>
    </div>

    <ul role="list" class="space-y-3 md:ml-7 md:mr-7">';

foreach ($prechecks as $precheck) {
    if ($precheck[3] >= $precheck[2]) {
        echo '
                <li class="px-2 py-2 overflow-hidden text-gray-600 bg-white rounded-md shadow md:flex md:ml-6 md:mr-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-1 fill-green-700" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg><b>'.$precheck[1].' passed</b>&nbsp;<br> '.$precheck[4].'
                </li>';
    } else {
        echo '
                <li class="px-2 py-2 overflow-hidden text-gray-600 bg-white rounded-md shadow md:flex md:ml-6 md:mr-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-1 fill-red-700" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <b>'.$precheck[1].' failed!</b>&nbsp; '.$precheck[4].'
                </li>';
    }
}

echo '
    </ul>
    </div>

    </div>

';

// Do Prechecks
// Output Prechecks
// Set prechecks to passed, failed or skipped in session-var

/** Installation Steps */

// If prechecks passed or skipped
// Make steps

/** Installing */

// If Steps all completed
// Install

/** Success */

// If Install finished
// Partytime

/** HTML Footer */
?>
</body></html>
