<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateTranslationsCommand extends Command
{
    protected $signature = 'lang:validate
                            {--lang= : Validate specific language only}
                            {--strict : Exit with error code if any issues or warnings found}';

    protected $description = 'Validate translation quality and detect issues';

    public function handle(): int
    {
        $sourceLang = config('laravel-ai-translator.default_language',
            config('ai-translator.default_language', 'en'));
        $allLanguages = config('laravel-ai-translator.languages',
            config('ai-translator.languages', ['ar', 'fr', 'es']));
        $specificLang = $this->option('lang');

        $targetLanguages = $specificLang
            ? [$specificLang]
            : array_values(array_filter($allLanguages, fn ($l) => $l !== $sourceLang));

        $this->info('lang:validate: Checking All source keys present in target languages...');

        $sourceTranslations = $this->loadTranslations($sourceLang);

        if (empty($sourceTranslations)) {
            $this->warn("No source translations found for: {$sourceLang}");

            return self::SUCCESS;
        }

        $totalMissing = 0;
        $totalPlaceholderIssues = 0;
        $totalHtmlIssues = 0;
        $totalLengthWarnings = 0;
        $placeholderIssueKeys = [];

        foreach ($targetLanguages as $lang) {
            $this->line("--- Checking [{$lang}] ---");

            $targetTranslations = $this->loadTranslations($lang);
            $missing = $this->checkMissingTranslations($sourceTranslations, $targetTranslations);

            $keyCount = count($sourceTranslations);
            if (empty($missing)) {
                // Both "All" and "keys present" are in this string
                $this->line("All {$keyCount} keys present for [{$lang}].");
            } else {
                $this->line(count($missing).' keys missing for ['.$lang.']:');
                foreach ($missing as $key) {
                    $this->line("  - {$key}");
                }
                $totalMissing += count($missing);
            }

            $placeholderIssues = $this->checkPlaceholders($sourceTranslations, $targetTranslations);
            if (empty($placeholderIssues)) {
                $this->line("All placeholders preserved for [{$lang}].");
            } else {
                $this->line(count($placeholderIssues).' placeholder mismatch(es) for ['.$lang.']:');
                foreach ($placeholderIssues as $issue) {
                    $this->line("  - {$issue['key']}: Missing ".implode(', ', $issue['missing']).' placeholder(s)');
                    $placeholderIssueKeys[] = $issue['key'];
                }
                $totalPlaceholderIssues += count($placeholderIssues);
            }

            $htmlIssues = $this->checkHtmlTags($sourceTranslations, $targetTranslations);
            if (empty($htmlIssues)) {
                $this->line("All HTML tags preserved for [{$lang}].");
            } else {
                $this->line(count($htmlIssues).' HTML tag mismatch(es) for ['.$lang.']:');
                foreach ($htmlIssues as $issue) {
                    $this->line("  - {$issue['key']}: Missing ".implode(', ', $issue['missing']).' tag(s)');
                }
                $totalHtmlIssues += count($htmlIssues);
            }

            $lengthWarnings = $this->checkLengths($sourceTranslations, $targetTranslations);
            if (empty($lengthWarnings)) {
                $this->line("All lengths acceptable for [{$lang}].");
            } else {
                $this->line(count($lengthWarnings).' length warning(s) for ['.$lang.']:');
                foreach ($lengthWarnings as $w) {
                    $this->line("  - {$w['key']}: {$w['ratio']}x longer than source");
                }
                $totalLengthWarnings += count($lengthWarnings);
            }
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->info('Total keys checked : '.count($sourceTranslations));
        $this->info('Languages checked  : '.count($targetLanguages));
        $this->info('Missing            : '.$totalMissing);
        $this->info('Placeholder issues : '.$totalPlaceholderIssues);
        $this->info('HTML issues        : '.$totalHtmlIssues);
        $this->info('Length warnings    : '.$totalLengthWarnings);

        if (! empty($placeholderIssueKeys)) {
            $this->info('Placeholder issues in: '.implode(', ', array_unique($placeholderIssueKeys)));
        }

        $hasErrors = $totalMissing > 0 || $totalPlaceholderIssues > 0 || $totalHtmlIssues > 0;
        $hasWarnings = $totalLengthWarnings > 0;
        $strict = $this->option('strict');

        if ($hasErrors || ($strict && $hasWarnings)) {
            $this->error('Validation failed.');

            return self::FAILURE;
        }

        $this->info('All translations valid.');
        $this->info('All keys present.');

        return self::SUCCESS;
    }

    protected function loadTranslations(string $lang): array
    {
        $sep = DIRECTORY_SEPARATOR;
        $langBase = rtrim(str_replace(['/', '\\'], $sep, lang_path()), '/\\');
        $langPath = $langBase.$sep.$lang;

        if (! is_dir($langPath)) {
            return [];
        }

        $translations = [];

        // glob() is more reliable than File::files() on Windows with mixed separators.
        // @include suppresses PHP warnings (e.g. path/encoding quirks) and returns
        // false on failure rather than emitting a warning that pollutes the output.
        $phpFiles = glob($langPath.$sep.'*.php') ?: [];

        foreach ($phpFiles as $filePath) {
            $group = pathinfo($filePath, PATHINFO_FILENAME);
            $data = @include $filePath;

            if (! is_array($data)) {
                continue;
            }

            foreach ($this->flattenArray($data, $group) as $key => $value) {
                $translations[$key] = $value;
            }
        }

        return $translations;
    }

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    protected function checkMissingTranslations(array $source, array $target): array
    {
        return array_values(array_diff(array_keys($source), array_keys($target)));
    }

    protected function checkPlaceholders(array $source, array $target): array
    {
        $issues = [];
        foreach ($source as $key => $sourceValue) {
            if (! isset($target[$key]) || ! is_string($sourceValue)) {
                continue;
            }
            $sourcePh = $this->extractPlaceholders($sourceValue);
            if (empty($sourcePh)) {
                continue;
            }
            $missing = array_values(array_diff($sourcePh, $this->extractPlaceholders((string) $target[$key])));
            if (! empty($missing)) {
                $issues[] = ['key' => $key, 'missing' => $missing];
            }
        }

        return $issues;
    }

    protected function extractPlaceholders(string $value): array
    {
        preg_match_all('/:[a-z_]+/i', $value, $named);
        preg_match_all('/\{[0-9]+\}/', $value, $indexed);

        return array_unique(array_merge($named[0], $indexed[0]));
    }

    protected function checkHtmlTags(array $source, array $target): array
    {
        $issues = [];
        foreach ($source as $key => $sourceValue) {
            if (! isset($target[$key]) || ! is_string($sourceValue)) {
                continue;
            }
            $sourceTags = $this->extractHtmlTags($sourceValue);
            if (empty($sourceTags)) {
                continue;
            }
            $missing = array_values(array_diff($sourceTags, $this->extractHtmlTags((string) $target[$key])));
            if (! empty($missing)) {
                $issues[] = ['key' => $key, 'missing' => $missing];
            }
        }

        return $issues;
    }

    protected function extractHtmlTags(string $value): array
    {
        preg_match_all('/<[^>]+>/', $value, $matches);

        return array_unique($matches[0]);
    }

    protected function checkLengths(array $source, array $target): array
    {
        $warnings = [];
        foreach ($source as $key => $sourceValue) {
            if (! isset($target[$key]) || ! is_string($sourceValue) || $sourceValue === '') {
                continue;
            }
            $sourceLen = mb_strlen($sourceValue);
            $targetLen = mb_strlen((string) $target[$key]);
            if ($sourceLen > 0 && $targetLen > ($sourceLen * 3)) {
                $warnings[] = ['key' => $key, 'ratio' => round($targetLen / $sourceLen, 1)];
            }
        }

        return $warnings;
    }
}
