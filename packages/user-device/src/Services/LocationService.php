<?php

namespace Moox\UserDevice\Services;

use GeoIp2\Database\Reader;

class LocationService
{
    protected $reader;

    public function __construct()
    {
        $mmdbPath = __DIR__.'/../../database/geoip/GeoLite2-City.mmdb';
        $this->reader = new Reader($mmdbPath);
    }

    public function getLocation($ipAddress)
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
        } catch (\Exception $e) {
            // Handle errors or unknown IPs gracefully
            return null;
        }
    }
}
