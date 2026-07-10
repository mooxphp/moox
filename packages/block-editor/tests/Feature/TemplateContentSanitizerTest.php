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

    $result = $sanitizer->sanitizeBlocks([
        [
            'content' => '<a href="https://example.com" target="_blank">Example</a>',
        ],
    ]);

    expect($result[0]['content'])->toContain('rel="noopener noreferrer"');
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
