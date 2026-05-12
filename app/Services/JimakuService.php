<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JimakuService
{
    private string $apiKey;
    private string $baseUrl = 'https://jimaku.cc/api';
    private string $cacheDir;

    public function __construct()
    {
        $this->apiKey = (string) config('services.jimaku.api_key', '');
        $this->cacheDir = storage_path('app/public/subtitles');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function fetchSubtitles(int $anilistId, int $episode, string $lang = 'en'): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cacheKey = $this->cacheKey($anilistId, $episode, $lang);
        $localPath = "{$this->cacheDir}/{$cacheKey}.vtt";
        $webPath = url("/subtitles/{$cacheKey}.vtt");

        if (file_exists($localPath)) {
            return [[
                'language' => $lang,
                'label' => strtoupper($lang),
                'file_path' => $webPath,
                'is_default' => $lang === 'en',
            ]];
        }

        $entry = $this->findEntry($anilistId);
        if (!$entry) {
            return [];
        }

        $files = $this->getFiles($entry['id'], $episode);
        if (empty($files)) {
            return [];
        }

        $file = $this->pickFile($files, $lang);
        if (!$file) {
            return [];
        }

        $content = $this->downloadFile($file['url']);
        if (!$content) {
            return [];
        }

        $vttContent = $this->convertToVtt($content, $file['name']);
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        file_put_contents($localPath, $vttContent);

        $label = $this->inferLabel($file['name']);

        return [[
            'language' => $lang,
            'label' => $label,
            'file_path' => $webPath,
            'is_default' => $lang === 'en',
        ]];
    }

    private function findEntry(int $anilistId): ?array
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/entries/search", [
                'anilist_id' => $anilistId,
                'anime' => true,
            ]);

            if (!$resp->ok()) {
                Log::warning('Jimaku search failed', [
                    'anilist_id' => $anilistId, 'status' => $resp->status(),
                ]);
                return null;
            }

            $entries = $resp->json();
            if (empty($entries) || !is_array($entries)) {
                return null;
            }

            return $entries[0];
        } catch (\Exception $e) {
            Log::warning('Jimaku search error: ' . $e->getMessage());
            return null;
        }
    }

    private function getFiles(int $entryId, int $episode): array
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/entries/{$entryId}/files", [
                'episode' => $episode,
            ]);

            if (!$resp->ok()) {
                Log::warning('Jimaku files failed', [
                    'entry_id' => $entryId, 'status' => $resp->status(),
                ]);
                return [];
            }

            return $resp->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('Jimaku files error: ' . $e->getMessage());
            return [];
        }
    }

    private function pickFile(array $files, string $lang): ?array
    {
        if (empty($files)) {
            return null;
        }

        foreach ($files as $file) {
            $name = $file['name'] ?? '';
            if ($this->isLang($name, $lang)) {
                return $file;
            }
        }

        return $files[0];
    }

    private function isLang(string $filename, string $lang): bool
    {
        if ($lang === 'en') {
            return (bool) preg_match('/\[(English|EN)\]|\((English|EN)\)|\.eng\.|_eng_|\bEnglish\b/i', $filename);
        }
        return true;
    }

    private function inferLabel(string $filename): string
    {
        if (preg_match('/\[English\]|\((English)\)|\bEnglish\b/i', $filename)) {
            return 'English';
        }
        if (preg_match('/\[Japanese\]|\((Japanese)\)|\bJapanese\b|\bJPN\b/i', $filename)) {
            return '日本語';
        }
        return 'English';
    }

    private function downloadFile(string $url): ?string
    {
        try {
            $resp = Http::timeout(15)->get($url);
            if (!$resp->ok()) {
                return null;
            }
            return $resp->body();
        } catch (\Exception $e) {
            Log::warning('Jimaku download error: ' . $e->getMessage());
            return null;
        }
    }

    private function convertToVtt(string $content, string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext === 'vtt') {
            if (!str_starts_with(trim($content), 'WEBVTT')) {
                return "WEBVTT\n\n" . $content;
            }
            return $content;
        }

        if ($ext === 'srt') {
            return $this->srtToVtt($content);
        }

        if (in_array($ext, ['ass', 'ssa'])) {
            return $this->assToVtt($content);
        }

        return $this->srtToVtt($content);
    }

    private function srtToVtt(string $srt): string
    {
        $vtt = "WEBVTT\n\n";
        $blocks = preg_split('/\n\s*\n/', trim($srt));

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $lines = explode("\n", $block);
            $cleaned = [];

            foreach ($lines as $line) {
                $line = rtrim($line);
                if (preg_match('/^\d+$/', trim($line))) {
                    continue;
                }
                if (preg_match('/^(\d{2}:\d{2}:\d{2}),(\d{3}) --> (\d{2}:\d{2}:\d{2}),(\d{3})$/', $line, $m)) {
                    $line = "{$m[1]}.{$m[2]} --> {$m[3]}.{$m[4]}";
                }
                $cleaned[] = $line;
            }

            $vtt .= implode("\n", $cleaned) . "\n\n";
        }

        return $vtt;
    }

    private function assToVtt(string $ass): string
    {
        $vtt = "WEBVTT\n\n";
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $ass));
        $inEvents = false;

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (preg_match('/^\[Events\]/i', $line)) {
                $inEvents = true;
                continue;
            }

            if ($inEvents && preg_match('/^\[/', $line)) {
                break;
            }

            if (!$inEvents) {
                continue;
            }

            if (!preg_match('/^Dialogue:\s*/', $line)) {
                continue;
            }

            $rest = substr($line, 10);
            $parts = explode(',', $rest, 10);

            if (count($parts) < 10) {
                continue;
            }

            $start = trim($parts[1]);
            $end = trim($parts[2]);
            $text = $parts[9];

            $startVtt = $this->assTimeToVtt($start);
            $endVtt = $this->assTimeToVtt($end);

            $text = preg_replace('/\{[^}]*\}/', '', $text);

            $text = str_replace(['\\N', '\\n', '\N', '\n'], "\n", $text);

            $text = preg_replace('/\n+/', "\n", $text);
            $text = trim($text);
            if ($text === '') {
                continue;
            }

            $vtt .= "{$startVtt} --> {$endVtt}\n{$text}\n\n";
        }

        return $vtt;
    }

    private function assTimeToVtt(string $time): string
    {
        if (preg_match('/^(\d+):(\d{2}):(\d{2})\.(\d+)$/', trim($time), $m)) {
            $h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $ms = str_pad(substr($m[4] . '000', 0, 3), 3, '0');
            return "{$h}:{$m[2]}:{$m[3]}.{$ms}";
        }
        return $time;
    }

    private function cacheKey(int $anilistId, int $episode, string $lang): string
    {
        return "jimaku_{$anilistId}_ep{$episode}_{$lang}";
    }
}
