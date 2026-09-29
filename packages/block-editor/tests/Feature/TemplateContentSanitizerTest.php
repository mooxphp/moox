<?php

use Moox\BlockEditor\Support\TemplateContentSanitizer;

it('removes unsafe html and javascript urls from blocks', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'content' => '<p onclick="evil()">A</p><script>alert(1)</script>',
            'href' => 'javascript:alert(1)',
            'src' => 'https://example.com/image.png',
            'title' => '<b>Safe title</b>',
        ],
    ]);

    expect($result[0]['content'])->not->toContain('onclick=')
        ->and($result[0]['content'])->not->toContain('<script>')
        ->and($result[0]['href'])->toBe('')
        ->and($result[0]['src'])->toBe('https://example.com/image.png')
        ->and($result[0]['title'])->toBe('Safe title');
});

it('adds noopener noreferrer for target blank links', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeMeta([
        'content' => '<a href="https://example.com" target="_blank">Example</a>',
    ]);

    expect($result['content'])->toContain('rel="noopener noreferrer"');
});

it('sanitizes nested tabs child blocks recursively', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'id' => '1',
            'type' => 'tabs',
            'tabsData' => [
                'activeTabId' => 'tab-1',
                'items' => [
                    [
                        'id' => 'tab-1',
                        'title' => '<b>Erster Tab</b>',
                        'content' => '<p onclick="evil()">Intro</p>',
                        'children' => [
                            [
                                'id' => 'child-1',
                                'type' => 'paragraph',
                                'content' => '<p><script>alert(1)</script>Text</p>',
                                'href' => 'javascript:alert(1)',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $tab = $result[0]['tabsData']['items'][0];
    $child = $tab['children'][0];

    expect($tab['title'])->toBe('Erster Tab')
        ->and($tab['content'])->not->toContain('onclick=')
        ->and($child['content'])->not->toContain('<script>')
        ->and($child['href'])->toBe('');
});

it('sanitizes accordion question fields as html content', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'id' => '1',
            'type' => 'accordion',
            'accordionData' => [
                'items' => [
                    [
                        'id' => 'acc-1',
                        'question' => '<p onclick="evil()">Question<script>alert(1)</script></p>',
                    ],
                ],
            ],
        ],
    ]);

    $question = $result[0]['accordionData']['items'][0]['question'];

    expect($question)->not->toContain('onclick=')
        ->and($question)->not->toContain('<script>')
        ->and($question)->toContain('Question');
});

it('sanitizes media url fields and rejects svg data urls', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'imageUrl' => 'javascript:alert(1)',
            'videoUrl' => 'https://example.com/video.mp4',
            'videoPoster' => 'data:image/svg+xml,<svg></svg>',
            'embedUrl' => 'https://www.youtube.com/embed/abc',
            'linkText' => '<b>Click</b>',
            'src' => 'data:image/png;base64,abc',
        ],
    ]);

    expect($result[0]['imageUrl'])->toBe('')
        ->and($result[0]['videoUrl'])->toBe('https://example.com/video.mp4')
        ->and($result[0]['videoPoster'])->toBe('')
        ->and($result[0]['embedUrl'])->toBe('https://www.youtube.com/embed/abc')
        ->and($result[0]['linkText'])->toBe('Click')
        ->and($result[0]['src'])->toBe('data:image/png;base64,abc');
});

it('strips unsafe characters from block ids', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'id' => '"><img src=x onerror=alert(1)>',
            'type' => 'paragraph',
            'content' => '<p>Safe</p>',
        ],
    ]);

    expect($result[0]['id'])->toBe('imgsrcxonerroralert1')
        ->and($result[0]['id'])->not->toContain('"')
        ->and($result[0]['id'])->not->toContain('<');
});

it('sanitizes children after unwrapping disallowed wrapper tags', function (): void {
    $sanitizer = new TemplateContentSanitizer;

    $result = $sanitizer->sanitizeBlocks([
        [
            'content' => '<svg><img src=x onerror=alert(1)></svg>',
            'text' => '<math><img src=x onerror=alert(1)></math>',
            'question' => '<custom-wrapper><p onclick="evil()">Q</p></custom-wrapper>',
        ],
    ]);

    expect($result[0]['content'])->not->toContain('onerror')
        ->and($result[0]['content'])->not->toContain('<svg')
        ->and($result[0]['text'])->not->toContain('onerror')
        ->and($result[0]['text'])->not->toContain('<math')
        ->and($result[0]['question'])->not->toContain('onclick')
        ->and($result[0]['question'])->toContain('Q');
});
