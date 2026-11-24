<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslationCoverageTest extends TestCase
{
    public function test_every_literal_translation_key_exists_in_arabic_and_english(): void
    {
        $keys = [];

        $directories = [resource_path('views'), app_path()];

        foreach ($directories as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if (! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                $contents = File::get($file->getPathname());

                preg_match_all("/__\(\s*'([^']+)'/u", $contents, $single);
                preg_match_all('/__\(\s*"([^"]+)"/u', $contents, $double);
                preg_match_all("/trans_choice\(\s*'([^']+)'/u", $contents, $choice);
                preg_match_all("/@lang\(\s*'([^']+)'/u", $contents, $lang);

                foreach (array_merge($single[1], $double[1], $choice[1], $lang[1]) as $key) {
                    if (str_contains($key, '$') || str_ends_with($key, '.') || ! str_contains($key, '.')) {
                        continue;
                    }

                    $keys[$key] = true;
                }
            }
        }

        $missingAr = [];
        $missingEn = [];

        foreach (array_keys($keys) as $key) {
            if (! Lang::has($key, 'ar')) {
                $missingAr[] = $key;
            }

            if (! Lang::has($key, 'en')) {
                $missingEn[] = $key;
            }
        }

        $this->assertSame([], $missingAr, 'Missing Arabic translations: '.implode(', ', $missingAr));
        $this->assertSame([], $missingEn, 'Missing English translations: '.implode(', ', $missingEn));
    }

    public function test_arabic_and_english_files_have_identical_key_sets(): void
    {
        $englishDirectory = lang_path('en');

        foreach (File::files(lang_path('ar')) as $file) {
            $englishPath = $englishDirectory.DIRECTORY_SEPARATOR.$file->getFilename();

            if (! File::exists($englishPath)) {
                continue;
            }

            $arabic = $this->flattenKeys(require $file->getPathname());
            $english = $this->flattenKeys(require $englishPath);

            sort($arabic);
            sort($english);

            $this->assertSame(
                $english,
                $arabic,
                'Arabic and English key sets differ in '.$file->getFilename(),
            );
        }
    }

    /**
     * @param  array<string|int, mixed>  $array
     * @return array<int, string>
     */
    protected function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $full));
            } else {
                $keys[] = $full;
            }
        }

        return $keys;
    }
}
