<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate flag paths from local `flags/*.png` files
 * to the flagcdn.com CDN so no local image files are required.
 *
 * Mapping (language code → ISO 3166-1 alpha-2 country code used on flagcdn.com):
 *   ar → sa (Saudi Arabia)
 *   en → gb (United Kingdom)
 */
return new class extends Migration
{
    /** @var array<string, string> code → CDN URL */
    private array $cdnMap = [
        'ar' => 'https://flagcdn.com/w40/sa.png',
        'en' => 'https://flagcdn.com/w40/gb.png',
        'fr' => 'https://flagcdn.com/w40/fr.png',
        'de' => 'https://flagcdn.com/w40/de.png',
        'es' => 'https://flagcdn.com/w40/es.png',
        'it' => 'https://flagcdn.com/w40/it.png',
        'tr' => 'https://flagcdn.com/w40/tr.png',
        'ru' => 'https://flagcdn.com/w40/ru.png',
        'zh' => 'https://flagcdn.com/w40/cn.png',
        'pt' => 'https://flagcdn.com/w40/pt.png',
        'ja' => 'https://flagcdn.com/w40/jp.png',
        'ko' => 'https://flagcdn.com/w40/kr.png',
        'hi' => 'https://flagcdn.com/w40/in.png',
        'fa' => 'https://flagcdn.com/w40/ir.png',
        'ur' => 'https://flagcdn.com/w40/pk.png',
        'id' => 'https://flagcdn.com/w40/id.png',
    ];

    public function up(): void
    {
        foreach ($this->cdnMap as $code => $cdnUrl) {
            DB::table('languages')
                ->where('code', $code)
                ->where(function ($query) {
                    // Only update rows that still reference local flag paths
                    $query->where('flag', 'LIKE', 'flags/%')
                          ->orWhereNull('flag')
                          ->orWhere('flag', '');
                })
                ->update(['flag' => $cdnUrl]);
        }
    }

    public function down(): void
    {
        // Restore local path references (files may not exist on disk,
        // but the paths match the original seeder values)
        $localMap = [
            'ar' => 'flags/ar.png',
            'en' => 'flags/gb.png',
            'fr' => 'flags/fr.png',
            'de' => 'flags/de.png',
            'es' => 'flags/es.png',
            'it' => 'flags/it.png',
            'tr' => 'flags/tr.png',
            'ru' => 'flags/ru.png',
            'zh' => 'flags/cn.png',
            'pt' => 'flags/pt.png',
            'ja' => 'flags/jp.png',
            'ko' => 'flags/kr.png',
            'hi' => 'flags/in.png',
            'fa' => 'flags/ir.png',
            'ur' => 'flags/pk.png',
            'id' => 'flags/id.png',
        ];

        foreach ($localMap as $code => $localPath) {
            DB::table('languages')
                ->where('code', $code)
                ->where('flag', $this->cdnMap[$code])
                ->update(['flag' => $localPath]);
        }
    }
};
