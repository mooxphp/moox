<?php

/**
 * Changelogger - reads CHANGELOG.md to craft new releases
 *
 * returns $version e. g. 0.0.2
 * returns $date e. g. 2022-02-01
 * returns array $packages
 *
 * */
$lines = file('../../CHANGELOG.md');

$current = getcurrent($lines);
$find = array_search($current, $lines);
$lines = array_values(array_slice($lines, $find + 1, null, true));

[$version, $date] = version_date($current);
echo $version.' at '.$date.'<br><br>';

$current = getcurrent($lines);
$find = array_search($current, $lines);
$lines = array_slice($lines, 0, $find - 1, true);

$packages[] = find_packages($lines);

if ($packages) {
    echo 'Packages:<br><ul>';
    foreach ($packages as $package) {
        echo '<li>'.$package.'</li>';
    }
    echo '</ul>';
}

function find_packages($lines)
{
    $packages = [];

    foreach ($lines as $line) {
        if (str_starts_with($line, '### ')) {
            $packages[] = trim($line, '### ');
        }
    }

    return $packages;
}

function version_date($current)
{
    $version_date = explode(' @ ', $current);
    $version = trim($version_date[0], '## v');
    $date = $version_date[1];

    return [$version, $date];
}

function getcurrent($lines)
{
    foreach ($lines as $line) {
        if (str_starts_with($line, '## v')) {
            return $line;
        }
    }
}
