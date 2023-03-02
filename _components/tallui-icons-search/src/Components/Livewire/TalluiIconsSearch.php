<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Components\Livewire;

use DirectoryIterator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Usetall\TalluiIconsSearch\Components\LivewireComponent;
use Usetall\TalluiIconsSearch\Models\Icon;
use Usetall\TalluiIconsSearch\Models\IconSet;

class TalluiIconsSearch extends LivewireComponent
{
    public string $search = '';

    public string $set = '';

    /** @var mixed */
    protected $queryString = [
        'search' => ['except' => ''],
        'set' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->search = (string) request()->query('search', $this->search);
        $this->set = (string) request()->query('set', $this->set);
    }

    public function resetSearch(): void
    {
        $this->reset('search');
    }

    protected function icons(): Collection
    {
        if ($this->shouldShowRandomIcons()) {
            return Icon::query()
                ->withSet($this->set)
                ->inRandomOrder()
                ->take(80)
                ->get();
        }

        return Icon::search($this->search)
            ->when(!empty($this->set), fn ($query) => $query->where('icon_set_id', $this->set))
            ->take(500)
            ->get();
    }

    protected function shouldShowRandomIcons(): bool
    {
        return empty(trim($this->search));
    }

    public function render(): View
    {
        return view('tallui-icons-search::components.livewire.tallui-icons-search', [
            'total' => Icon::query()->withSet($this->set)->count(),
            'icons' => $this->icons(),
            'sets' => IconSet::orderBy('name')->get(),
        ]);
    }

    public function collection()
    {
        $iconsset = 2;

        var_dump('click');

        $dir = new DirectoryIterator(base_path() . './_icons/tallui-flags-round/resources/svg');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot() && $iconsset <= 5) {
                echo '<h2>' . $fileinfo->getFilename() . '</h2>' . '<br>';
                $dir = base_path() . './_icons/tallui-flags-round/resources/svg' . $fileinfo->getFilename();
                $files = scandir($dir);

                foreach ($files as $file) {
                    if (basename($file, '.svg') != '.' and basename($file, '.svg') != '..') {
                        print_r($fileinfo->getFilename() . '-' . basename($file, '.svg') . '<br>');
                        // if ($this->doesIconAlreadyExists($fileinfo->getFilename() . '-' . basename($file, ".svg"))) {
                        //     echo 'Insert<br>';
                        //     Icon::insert(
                        //         [
                        //             'icon_set_id' => $iconsset,
                        //             'name' => $fileinfo->getFilename() . '-' . basename($file, ".svg"),
                        //             'keywords' => '{"keewords": 30}',
                        //             'outlined' => 0

                        //         ]

                        //     );
                        // }
                    }
                }

                $iconsset++;
            }
        }
    }
}
