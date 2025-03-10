<select onchange="if(this.value) window.location.href = window.location.pathname + '?lang=' + this.value"
    class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500">
    <option value="">Select Language</option>
    @foreach(\Moox\Localization\Models\Localization::all() as $locale)
        <option value="{{ $locale->language->alpha2 }}" {{ request()->get('lang') == $locale->language->alpha2 ? 'selected' : '' }}>
            {{ $locale->language->common_name }}
        </option>
    @endforeach
</select>