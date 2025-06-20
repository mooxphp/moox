<?php

namespace Moox\UserDevice\Services;

use Exception;
use GeoIp2\Database\Reader;

class LocationService
{
    protected Reader $reader;

    public function __construct()
    {
        $mmdbPath = __DIR__.'/../../database/geoip/GeoLite2-City.mmdb';
        $this->reader = new Reader($mmdbPath);
    }

    public function getLocation(string $ipAddress): ?array
    {
        try {
            $record = $this->reader->city($ipAddress);

            return [
                'city' => $record->city->name,
                'state' => $record->mostSpecificSubdivision->name,
                'country' => $record->country->name,
                'postal' => $record->postal->code,
                'lat' => $record->location->latitude,
                'lon' => $record->location->longitude,
            ];
        } catch (Exception) {
            // Handle errors or unknown IPs gracefully
            return null;
        }
    }
}
