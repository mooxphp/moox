<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Icon;
use DirectoryIterator;

final class ShowIconController
{
    public function __invoke(Icon $icon)
    {
        return view('blade-icons.show', [
            'icon' => $icon,
            'icons' => Icon::relatedIcons($icon),
        ]);
    }

    public function collection()
    {

        $iconsset = 3;


        // foreach (Icon::all() as $icon) {
        //     echo $icon->name;
        // }


        $dir = new DirectoryIterator(base_path() . '/_icons/tallui-web-icons/resources/svg');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot() && $iconsset <= 5) {
                echo '<h2>' . $fileinfo->getFilename() . '</h2>' . '<br>';
                $dir    = base_path() . '/_icons/tallui-web-icons/resources/svg/'.$fileinfo->getFilename();
                $files = scandir($dir);

                foreach ($files as $file)
                {

                    if (basename($file, ".svg") != "." and basename($file, ".svg") != "..") {
                        print_r($fileinfo->getFilename() . '-' . basename($file, ".svg") . '<br>');
                        if ($this->doesIconAlreadyExists($fileinfo->getFilename() . '-' . basename($file, ".svg"))) {
                            echo 'Insert<br>';
                            Icon::insert(
                                [
                                    'icon_set_id' => $iconsset,
                                    'name' => $fileinfo->getFilename() . '-' . basename($file, ".svg"),
                                    'keywords' => '{"keewords": 30}',
                                    'outlined' => 0

                                ]

                            );
                        }
                    }
                }

                $iconsset++;
            }
        }
    }

    public function doesIconAlreadyExists(string $filename): bool
    {

        $icon = Icon::where('name', $filename)->get()->first();
        if (isset($icon->name) and $icon->name == $filename) {
            return false;
        } else {
            return true;
        }

        return true;
    }
}
