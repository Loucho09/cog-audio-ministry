<?php

it('renders one accessible theme toggle button in the navbar', function () {
    $dom = new DOMDocument;
    @$dom->loadHTML($this->get('/')->assertOk()->getContent());
    $xpath = new DOMXPath($dom);

    $toggle = $xpath->query('//header//button[@data-theme-toggle]');

    expect($toggle->length)->toBe(1)
        ->and($toggle->item(0)->getAttribute('type'))->toBe('button')
        ->and($toggle->item(0)->getAttribute('aria-label'))->toBe('Dark mode')
        ->and($toggle->item(0)->getAttribute('aria-pressed'))->toBe('false')
        ->and($toggle->item(0)->getAttribute(':aria-pressed'))->toBe('dark.toString()')
        ->and($toggle->item(0)->getAttribute('class'))->not->toContain('lg:hidden');
});

it('applies the saved or device theme in the head before styles load', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $head = substr($html, 0, strpos($html, '</head>'));
    $script = strpos($head, "localStorage.getItem('theme')");

    expect($script)->not->toBeFalse()
        ->and($head)->toContain('prefers-color-scheme: dark')
        ->and($script)->toBeLessThan(strpos($head, '<link rel="stylesheet"') ?: PHP_INT_MAX);
});

it('uses class-based dark mode with CSS-variable theme tokens', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($config)->toContain("darkMode: 'class'")
        ->and($config)->toContain('var(--color-background)')
        ->and($css)->toContain(':root')
        ->and($css)->toContain('.dark');
});
