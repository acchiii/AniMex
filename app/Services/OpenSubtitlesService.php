<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenSubtitlesService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.opensubtitles.com/api/v1';
    private string $cacheDir;

    public function __construct()
    {
        $this->apiKey = (string) config('services.opensubtitles.api_key', '');
        $this->cacheDir = storage_path('app/public/subtitles');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function fetchSubtitles(string $title, int $season, int $episode, string $lang = 'en'): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cacheKey = $this->cacheKey($title, $season, $episode, $lang);
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

        $files = $this->search($title, $season, $episode, $lang);
        if (empty($files)) {
            return [];
        }

        $fileId = $files[0]['id'] ?? null;
        if (!$fileId) {
            return [];
        }

        $downloadLink = $this->getDownloadLink($fileId);
        if (!$downloadLink) {
            return [];
        }

        $srtContent = $this->fetchContent($downloadLink);
        if (!$srtContent) {
            return [];
        }

        $vttContent = $this->srtToVtt($srtContent);
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        file_put_contents($localPath, $vttContent);

        return [[
            'language' => $lang,
            'label' => strtoupper($lang),
            'file_path' => $webPath,
            'is_default' => $lang === 'en',
        ]];
    }

    private function search(string $title, int $season, int $episode, string $lang): array
    {
        try {
            $resp = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'User-Agent' => 'AniMex v1.0',
                'Accept' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/subtitles", [
                'query' => $title,
                'season_number' => $season,
                'episode_number' => $episode,
                'languages' => $lang,
                'type' => 'episode',
                'order_by' => 'download_count',
                'order_direction' => 'desc',
            ]);

            if (!$resp->ok()) {
                Log::warning('OpenSubtitles search failed', [
                    'title' => $title, 'status' => $resp->status(), 'body' => $resp->body(),
                ]);
                return [];
            }

            return $resp->json('data') ?? [];
        } catch (\Exception $e) {
            Log::warning('OpenSubtitles search error: ' . $e->getMessage());
            return [];
        }
    }

    private function getDownloadLink(int $fileId): ?string
    {
        try {
            $resp = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'User-Agent' => 'AniMex v1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/download", [
                'file_id' => $fileId,
            ]);

            if (!$resp->ok()) {
                Log::warning('OpenSubtitles download failed', [
                    'file_id' => $fileId, 'status' => $resp->status(),
                ]);
                return null;
            }

            return $resp->json('link');
        } catch (\Exception $e) {
            Log::warning('OpenSubtitles download error: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchContent(string $url): ?string
    {
        try {
            $resp = Http::timeout(15)->get($url);
            if (!$resp->ok()) {
                return null;
            }
            return $resp->body();
        } catch (\Exception $e) {
            Log::warning('OpenSubtitles fetch content error: ' . $e->getMessage());
            return null;
        }
    }

    private function srtToVtt(string $srt): string
    {
        $vtt = "WEBVTT\n\n";

        $blocks = preg_split('/\n\s*\n/', trim($srt));

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;

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

    private function cacheKey(string $title, int $season, int $episode, string $lang): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');
        return "{$slug}_s{$season}_ep{$episode}_{$lang}";
    }

    public function guessSeason(int $episodeNumber): int
    {
        return 1;
    }
}
