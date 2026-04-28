<?php

declare(strict_types=1);

namespace PermitSales;

final class View
{
    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        $base = dirname(__DIR__) . '/views/';
        $contentFile = $base . $template . '.php';
        $layoutFile = $base . $layout . '.php';

        if (!is_readable($contentFile)) {
            throw new \RuntimeException("Missing view: {$template}");
        }
        if (!is_readable($layoutFile)) {
            throw new \RuntimeException("Missing layout: {$layout}");
        }

        $data['__user'] = Auth::user();
        $data['__csrf'] = Session::csrfToken();
        $data['__flash'] = [
            'success' => Session::flash('success'),
            'error'   => Session::flash('error'),
        ];

        extract($data, EXTR_SKIP);
        ob_start();
        require $contentFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
