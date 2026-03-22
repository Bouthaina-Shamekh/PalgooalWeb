<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Portfolio;
use App\Models\Template;
use App\Models\TranslationValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocaleController extends Controller
{
    //  Change language
    public function change($locale)
    {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        $redirect = trim((string) request()->query('redirect', ''));
        $safeRedirect = $this->normalizeRedirectUrl($redirect);

        if ($safeRedirect) {
            return redirect()->to($safeRedirect);
        }

        $previousUrl = url()->previous();
        $parsed = parse_url($previousUrl);
        $path = $parsed['path'] ?? '/';

        // التحقق من صفحة قالب Template
        if (preg_match('#^/templates/([^/]+)(?:/(redesign|preview))?$#', $path, $matches)) {
            $currentSlug = $matches[1];
            $variant = $matches[2] ?? null;

            $template = Template::whereHas('translations', function ($q) use ($currentSlug) {
                $q->where('slug', $currentSlug);
            })->first();

            if ($template) {
                $translated = $template->getTranslation($locale);
                if ($translated) {
                    return match ($variant) {
                        'redesign' => redirect()->route('template.show.redesign', ['slug' => $translated->slug]),
                        'preview' => redirect()->route('template.preview', ['slug' => $translated->slug]),
                        default => redirect()->route('template.show', ['slug' => $translated->slug]),
                    };
                }
            }
        }

        // التحقق من صفحة Portfolio
        if (preg_match('#/portfolio/([^/]+)#', $path, $matches)) {
            $currentSlug = $matches[1];

            $portfolio = Portfolio::whereHas('translations', function ($q) use ($currentSlug) {
                $q->where('slug', $currentSlug);
            })->first();

            if ($portfolio) {
                $translated = $portfolio->getTranslation($locale);
                if ($translated) {
                    return redirect()->route('portfolio.show', ['slug' => $translated->slug]);
                }
            }
        }

        if ($this->isUnsafePreviousPath($path)) {
            return redirect()->to(url('/'));
        }

        return redirect()->to($previousUrl ?: url('/'));
    }

    // Return all translations as JSON (for Frontend, SPA or JS)
    public function translateJson($locale)
    {
        $translations = TranslationValue::where('locale', $locale)->pluck('value', 'key');

        return response()->json($translations);
    }

    private function isUnsafePreviousPath(string $path): bool
    {
        $normalizedPath = ltrim(strtolower(trim($path)), '/');

        if ($normalizedPath === '') {
            return false;
        }

        if (str_starts_with($normalizedPath, 'assets/') || str_starts_with($normalizedPath, 'storage/')) {
            return true;
        }

        return preg_match('/\.(?:css|js|map|png|jpe?g|svg|gif|webp|ico|woff2?|ttf|eot)$/', $normalizedPath) === 1;
    }

    private function normalizeRedirectUrl(?string $redirect): ?string
    {
        $redirect = trim((string) $redirect);

        if ($redirect === '') {
            return null;
        }

        if (Str::startsWith($redirect, '/')) {
            return url($redirect);
        }

        if (! filter_var($redirect, FILTER_VALIDATE_URL)) {
            return null;
        }

        $redirectHost = strtolower((string) parse_url($redirect, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url(config('app.url') ?: url('/'), PHP_URL_HOST));

        if ($redirectHost === '' || $appHost === '' || $redirectHost !== $appHost) {
            return null;
        }

        return $redirect;
    }
}

