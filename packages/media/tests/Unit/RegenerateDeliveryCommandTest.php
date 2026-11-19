<?php

declare(strict_types=1);

use Moox\Media\Console\RegenerateDeliveryCommand;
use Moox\Media\Support\MediaDeliveryConversions;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.delivery.sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'contain'],
            'medium' => ['width' => 300],
            'medium_large' => ['width' => 768],
            'large' => ['width' => 1024],
        ],
        'media.delivery.extra_widths' => [480, 960, 1440],
        'media.delivery.profiles' => [
            'web' => [
                'enabled' => true,
                'mode' => 'responsive',
                'formats' => ['avif', 'webp', 'original'],
                'upscale' => false,
                'min_responsive_width' => 480,
                'fallback_to_original' => true,
                'svg' => 'original',
            ],
            'mail' => [
                'enabled' => true,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 600,
                'fallback_to_original' => true,
                'svg' => 'png',
            ],
            'customer_api' => [
                'enabled' => true,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 1024,
                'fallback_to_original' => false,
                'svg' => 'skip',
            ],
        ],
    ]);
});

function recordingRegenerateDeliveryCommand(): RegenerateDeliveryCommand
{
    $command = new class extends RegenerateDeliveryCommand
    {
        /** @var array{command: string, arguments: array<string, mixed>}|null */
        public ?array $passed = null;

        public function call($command, array $arguments = []): int
        {
            $this->passed = [
                'command' => $command,
                'arguments' => $arguments,
            ];

            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    return $command;
}

it('passes only mail conversion names and ids to media-library:regenerate', function (): void {
    $command = recordingRegenerateDeliveryCommand();
    $output = new BufferedOutput;

    $status = $command->run(new ArrayInput([
        '--profile' => ['mail'],
        '--ids' => ['12'],
    ]), $output);

    expect($status)->toBe(0)
        ->and($command->passed)->not->toBeNull()
        ->and($command->passed['command'])->toBe('media-library:regenerate');

    $arguments = $command->passed['arguments'];
    $only = $arguments['--only'] ?? [];

    expect($only)->toContain('mail', 'mail-png')
        ->and($only)->not->toContain('web-480-avif')
        ->and($only)->not->toContain('customer_api')
        ->and($arguments['--ids'] ?? null)->toBe(['12'])
        ->and($arguments)->toHaveKey('--only-missing')
        ->and($output->fetch())->toContain('profiles: mail');
});

it('warns when scanning the full library without ids', function (): void {
    $command = recordingRegenerateDeliveryCommand();
    $output = new BufferedOutput;

    $status = $command->run(new ArrayInput([
        '--profile' => ['web'],
    ]), $output);

    $text = $output->fetch();

    expect($status)->toBe(0)
        ->and($text)->toContain('No --ids given')
        ->and($command->passed['arguments']['--only'] ?? [])->toContain('web-480-avif')
        ->and($command->passed['arguments'])->not->toHaveKey('--ids');
});

it('resolves mail profile conversion names without image processing', function (): void {
    expect(MediaDeliveryConversions::allConversionNames(['mail']))
        ->toContain('mail', 'mail-png')
        ->and(MediaDeliveryConversions::allConversionNames(['mail']))
        ->not->toContain('customer_api');
});
