<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\Icon;
use App\Models\IconSet;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use PhpParser\Node\Expr\Cast\String_;

use function JmesPath\search;

final class IconSearch extends Component
{
    public string $search = '';

    public string $set = '';

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

    public function render(): View
    {


        return view('livewire.icon-search', [
            'total' => Icon::query()->withSet($this->set)->count(),
            'icons' => $this->icons(),
            'sets' => IconSet::orderBy('name')->get(),
        ]);
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
         return $this->searched();
    }

    protected function shouldShowRandomIcons(): bool
    {
        return empty(trim($this->search));
    }

    protected function searched(): Collection
    {
        $allicons =
        Icon::query()
        ->withSet($this->set)
        ->get();

        $search= $this->search;
        $ressult = new Collection();
        $search = strtolower($search);
        $len=strlen($search);
        foreach($allicons as $icon) {
            $substring= substr($icon->name, strpos($icon->name, "-") + 1);
            if(stristr($search, substr($substring, 0, $len))) {
            $ressult->add($icon);
          }
        }
       return $ressult;
    }

}
