import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Press/**/*.php',
        './vendor/ralphjsmit/laravel-filament-media-library/resources/**/*.blade.php',
        './resources/views/filament/press/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
