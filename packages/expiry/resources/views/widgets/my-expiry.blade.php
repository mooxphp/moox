@php
    $tabs = $this->getTabs();
    $activeTab = request('activeTab', 'all');
@endphp

<div>
    <ul class="nav nav-tabs">
        @foreach ($tabs as $tabKey => $tab)
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === $tabKey ? 'active' : '' }}"
                   href="?activeTab={{ $tabKey }}">
                    <i class="{{ $tab->getIcon() }}"></i> {{ $tab->getLabel() }}
                    <span class="badge">{{ $tab->getBadge() }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $tabKey => $tab)
            <div class="tab-pane {{ $activeTab === $tabKey ? 'active' : '' }}">
                {{ $this->table }}
            </div>
        @endforeach
    </div>
</div>
