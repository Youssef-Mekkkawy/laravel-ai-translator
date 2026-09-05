<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use Illuminate\Http\Request;

class LockedKeysController extends DashboardController
{
    public function index(Request $request)
    {
        $storage     = new LockStorage();
        $lockManager = new LockManager($storage);

        $filterLang = $request->query('lang');
        $search     = $request->query('search');

        $rawLocks = $lockManager->getAll($filterLang);

        // Flatten to a list for the view
        $locks = [];

        if ($filterLang) {
            foreach ($rawLocks as $key => $info) {
                $locks[] = $this->formatLock($filterLang, $key, $info);
            }
        } else {
            foreach ($rawLocks as $lang => $langLocks) {
                foreach ($langLocks as $key => $info) {
                    $locks[] = $this->formatLock($lang, $key, $info);
                }
            }
        }

        // Apply search filter
        if ($search) {
            $locks = array_filter($locks, fn ($l) =>
                str_contains($l['key'], $search) ||
                str_contains($l['value'] ?? '', $search) ||
                str_contains($l['lang'], $search)
            );
        }

        // Sort newest first
        usort($locks, fn ($a, $b) => strcmp($b['locked_at'], $a['locked_at']));

        return $this->view('locked-keys', [
            'locks'      => array_values($locks),
            'filterLang' => $filterLang,
            'search'     => $search,
            'totalCount' => count($locks),
        ]);
    }

    private function formatLock(string $lang, string $key, array $info): array
    {
        return [
            'lang'      => $lang,
            'key'       => $key,
            'value'     => $info['value']     ?? null,
            'locked_by' => $info['locked_by'] ?? 'system',
            'locked_at' => $info['locked_at'] ?? null,
            'reason'    => $info['reason']    ?? null,
        ];
    }
}
