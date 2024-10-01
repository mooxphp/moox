<?php

namespace Moox\Core\Http\Controllers\Api;

use Illuminate\Routing\Controller;

class ModelController extends Controller
{
    public function index()
    {
        $packages = config('core.packages');
        $available_models = [];

        foreach ($packages as $package) {
            $namespace = str_replace(' ', '\\', $package['package']).'\\Models\\';

            foreach ($package['models'] as $model => $api) {
                $fullModelName = $namespace.$model;
                if (class_exists($fullModelName)) {
                    $available_models[] = $fullModelName;
                }
            }
        }

        return response()->json(['models' => $available_models]);
    }
}
