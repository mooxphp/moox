<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Components\Livewire;

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
        if (is_string(request()->query('search', $this->search))) {
            $this->search = request()->query('search', $this->search);
        } elseif (is_string(request()->query('set', $this->set))) {
            $this->set = request()->query('set', $this->set);
        }
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
}
