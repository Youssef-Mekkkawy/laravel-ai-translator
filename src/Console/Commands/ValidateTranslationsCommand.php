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

    protected array $config;

    public function handle(): int
    {
        // FIX: correct config key is 'ai-translator', not 'laravel-ai-translator'
        $this->config = config('ai-translator', []);

        $this->info("\n🔍 Validating translations...\n");

        $sourceLang      = $this->config['default_language'] ?? 'en';
        $allLanguages    = $this->config['languages']         ?? ['ar', 'fr', 'es'];
        $specificLang    = $this->option('lang');

        $targetLanguages = $specificLang
            ? [$specificLang]
            : array_values(array_filter($allLanguages, fn ($lang) => $lang !== $sourceLang));

        $sourceTranslations = $this->loadTranslations($sourceLang);

        if (empty($sourceTranslations)) {
            $this->warn("⚠️  No source translations found for language: {$sourceLang}");
            return self::SUCCESS;
        }

        $totalMissing           = 0;
        $totalPlaceholderIssues = 0;
        $totalHtmlIssues        = 0;
        $totalLengthWarnings    = 0;

        foreach ($targetLanguages as $lang) {
            $this->line("Checking <fg=cyan>{$lang}</>:");

            $targetTranslations = $this->loadTranslations($lang);

            // Missing translations
            $missing = $this->checkMissingTranslations($sourceTranslations, $targetTranslations);
            if (empty($missing)) {
                $this->line('  <fg=green>✓</> All ' . count($sourceTranslations) . ' keys present');
            } else {
                $this->line('  <fg=red>✗</> ' . count($missing) . ' keys missing:');
                foreach ($missing as $key) {
                    $this->line("    - {$key}");
                }
                $totalMissing += count($missing);
            }

            // Placeholder mismatches
            $placeholderIssues = $this->checkPlaceholders($sourceTranslations, $targetTranslations);
            if (empty($placeholderIssues)) {
                $this->line('  <fg=green>✓</> All placeholders preserved');
            } else {
                $this->line('  <fg=yellow>⚠</> ' . count($placeholderIssues) . ' placeholder mismatch(es) found:');
                foreach ($placeholderIssues as $issue) {
                    $this->line("    - {$issue['key']}: Missing " . implode(', ', $issue['missing']) . ' placeholder(s)');
                }
                $totalPlaceholderIssues += count($placeholderIssues);
            }

            // HTML tag mismatches
            $htmlIssues = $this->checkHtmlTags($sourceTranslations, $targetTranslations);
            if (empty($htmlIssues)) {
                $this->line('  <fg=green>✓</> All HTML tags preserved');
            } else {
                $this->line('  <fg=yellow>⚠</> ' . count($htmlIssues) . ' HTML tag mismatch(es) found:');
                foreach ($htmlIssues as $issue) {
                    $this->line("    - {$issue['key']}: Missing " . implode(', ', $issue['missing']) . ' tag(s)');
                }
                $totalHtmlIssues += count($htmlIssues);
            }

            // Length warnings
            $lengthWarnings = $this->checkLengths($sourceTranslations, $targetTranslations);
            if (empty($lengthWarnings)) {
                $this->line('  <fg=green>✓</> All lengths acceptable');
            } else {
                $this->line('  <fg=yellow>⚠</> ' . count($lengthWarnings) . ' length warning(s):');
                foreach ($lengthWarnings as $warning) {
                    $this->line("    - {$warning['key']}: {$warning['ratio']}x longer than source");
                }
                $totalLengthWarnings += count($lengthWarnings);
            }

            $this->newLine();
        }

        // Duplicate keys
        $duplicates = $this->checkDuplicateKeys($sourceLang);
        if (!empty($duplicates)) {
            $this->warn('⚠️  Duplicate keys found:');
            foreach ($duplicates as $key => $files) {
                $this->line("  - {$key}: defined in " . implode(', ', $files));
            }
            $this->newLine();
        }

        $this->displaySummary(
            count($sourceTranslations),
            count($targetLanguages),
            $totalMissing,
            $totalPlaceholderIssues,
            $totalHtmlIssues,
            $totalLengthWarnings,
            count($duplicates)
        );

        $hasErrors   = $totalMissing > 0 || $totalPlaceholderIssues > 0 || $totalHtmlIssues > 0;
        $hasWarnings = $totalLengthWarnings > 0 || !empty($duplicates);
        $strict      = $this->option('strict');

        if ($hasErrors || ($strict && $hasWarnings)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function loadTranslations(string $lang): array
    {
        $langPath = lang_path($lang);

        if (!File::exists($langPath)) {
            return [];
        }

        $translations = [];

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $data  = include $file->getPathname();

            if (!is_array($data)) {
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
            if (!isset($target[$key]) || !is_string($sourceValue)) {
                continue;
            }

            $sourcePlaceholders = $this->extractPlaceholders($sourceValue);

            if (empty($sourcePlaceholders)) {
                continue;
            }

            $missing = array_values(
                array_diff($sourcePlaceholders, $this->extractPlaceholders((string) $target[$key]))
            );

            if (!empty($missing)) {
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
            if (!isset($target[$key]) || !is_string($sourceValue)) {
                continue;
            }

            $sourceTags = $this->extractHtmlTags($sourceValue);

            if (empty($sourceTags)) {
                continue;
            }

            $missing = array_values(
                array_diff($sourceTags, $this->extractHtmlTags((string) $target[$key]))
            );

            if (!empty($missing)) {
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
            if (!isset($target[$key]) || !is_string($sourceValue) || $sourceValue === '') {
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

    protected function checkDuplicateKeys(string $lang): array
    {
        $langPath = lang_path($lang);

        if (!File::exists($langPath)) {
            return [];
        }

        $seen       = [];
        $duplicates = [];

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $data  = include $file->getPathname();

            if (!is_array($data)) {
                continue;
            }

            foreach (array_keys($this->flattenArray($data, $group)) as $key) {
                if (isset($seen[$key])) {
                    $duplicates[$key] ??= [$seen[$key]];
                    $duplicates[$key][] = $group;
                } else {
                    $seen[$key] = $group;
                }
            }
        }

        return $duplicates;
    }

    protected function displaySummary(
        int $totalKeys,
        int $languageCount,
        int $missing,
        int $placeholderIssues,
        int $htmlIssues,
        int $lengthWarnings,
        int $duplicates
    ): void {
        $this->info('📊 Summary:');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total keys checked',   number_format($totalKeys)],
                ['Languages',            $languageCount],
                ['Missing translations', $missing          > 0 ? "<fg=red>{$missing}</>"          : '<fg=green>0</>'],
                ['Placeholder issues',   $placeholderIssues > 0 ? "<fg=yellow>{$placeholderIssues}</>" : '<fg=green>0</>'],
                ['HTML tag issues',      $htmlIssues       > 0 ? "<fg=yellow>{$htmlIssues}</>"    : '<fg=green>0</>'],
                ['Length warnings',      $lengthWarnings   > 0 ? "<fg=yellow>{$lengthWarnings}</>" : '<fg=green>0</>'],
                ['Duplicate keys',       $duplicates       > 0 ? "<fg=yellow>{$duplicates}</>"    : '<fg=green>0</>'],
            ]
        );

        $this->newLine();

        if ($missing > 0 || $placeholderIssues > 0 || $htmlIssues > 0) {
            $this->error('⚠ Validation completed with errors.');
        } else {
            $this->info('✅ All translations are valid!');
        }

        $this->newLine();
    }
}
