<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class ResumeParserService
{
    private const LOCATION_HINTS = [
        'united kingdom',
        'uk',
        'england',
        'scotland',
        'wales',
        'northern ireland',
        'united states',
        'usa',
        'canada',
        'australia',
        'new zealand',
        'india',
        'pakistan',
        'germany',
        'france',
        'spain',
        'italy',
        'ireland',
        'netherlands',
        'sweden',
        'norway',
        'denmark',
        'finland',
        'switzerland',
        'uae',
        'saudi arabia',
        'qatar',
        'bahrain',
        'oman',
        'kuwait',
        'singapore',
        'malaysia',
        'remote',
        'on-site',
        'onsite',
        'hybrid',
    ];

    private const NON_LOCATION_KEYWORDS = [
        'provided',
        'developed',
        'managed',
        'responsible',
        'ensuring',
        'experience',
        'customer',
        'service',
        'dining',
        'project',
        'projects',
        'built',
        'implemented',
        'improved',
        'optimized',
        'created',
        'led',
        'team',
        'application',
        'applications',
        'using',
        'worked',
    ];

    public function __construct(private readonly AiCvAnalyserService $aiCvAnalyserService)
    {
    }

    /**
     * Parse a PDF CV and return normalized profile + extraction data.
     */
    public function parsePdf(string $absolutePath): array
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new \RuntimeException('The uploaded PDF could not be found or read.');
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $rawText = trim($pdf->getText());

        if ($rawText === '') {
            throw new \RuntimeException('No readable text could be extracted from the uploaded PDF.');
        }

        $binaryUrls = $this->extractUrlsFromPdfBinary($absolutePath);
        $structured = $this->aiCvAnalyserService->extractCvData($rawText);
        $fallback = $this->extractFallbackFields($rawText, $binaryUrls);

        $skills = collect($structured['skills'] ?? [])
            ->map(fn($skill) => trim((string) $skill))
            ->filter()
            ->values()
            ->all();

        $linkedin = $this->normalizeProfileUrl(
            $this->pickString($structured['linkedin'] ?? null, $fallback['linkedin']),
            'linkedin'
        );
        $github = $this->normalizeProfileUrl(
            $this->pickString($structured['github'] ?? null, $fallback['github']),
            'github'
        );
        $portfolio = $this->normalizeProfileUrl(
            $this->pickString($structured['portfolio'] ?? null, $fallback['portfolio']),
            'portfolio'
        );
        $structuredLocation = $this->cleanLocationCandidate((string) ($structured['location'] ?? ''));
        $fallbackLocation = $this->cleanLocationCandidate((string) ($fallback['location'] ?? ''));
        $location = $this->isLikelyLocation($structuredLocation)
            ? $structuredLocation
            : ($this->isLikelyLocation($fallbackLocation) ? $fallbackLocation : '');

        return [
            'raw_text' => $rawText,
            'name' => $this->pickString($structured['name'] ?? null, $fallback['name']),
            'email' => $this->pickString($structured['email'] ?? null, $fallback['email']),
            'phone' => $this->pickString($structured['phone'] ?? null, $fallback['phone']),
            'location' => $location,
            'linkedin' => $linkedin,
            'github' => $github,
            'portfolio' => $portfolio,
            'skills' => $skills,
            'experience' => is_array($structured['experience'] ?? null) ? $structured['experience'] : [],
            'education' => is_array($structured['education'] ?? null) ? $structured['education'] : [],
        ];
    }

    private function extractFallbackFields(string $text, array $binaryUrls = []): array
    {
        $email = '';
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches)) {
            $email = trim($matches[0]);
        }

        $phone = '';
        if (preg_match('/(\+?\d[\d\-\s()]{7,}\d)/', $text, $matches)) {
            $phone = trim($matches[0]);
        }

        $textUrls = [];
        if (preg_match_all('/https?:\/\/[^\s)]+/i', $text, $matches)) {
            $textUrls = $matches[0] ?? [];
        }

        $urls = collect(array_merge($textUrls, $binaryUrls))
            ->map(fn($url) => $this->normalizeDiscoveredUrl((string) $url))
            ->filter(fn($url) => $url !== '' && !$this->isIgnoredUtilityUrl($url))
            ->unique()
            ->values();

        $linkedin = (string) ($urls->first(fn($url) => str_contains(strtolower($url), 'linkedin.com')) ?? '');
        $github = (string) ($urls->first(fn($url) => str_contains(strtolower($url), 'github.com')) ?? '');
        $portfolio = (string) ($urls->first(function ($url) {
            $lower = strtolower($url);
            return !str_contains($lower, 'linkedin.com')
                && !str_contains($lower, 'github.com');
        }) ?? '');

        $lines = collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn($line) => trim((string) $line))
            ->filter()
            ->values();

        $name = (string) ($lines->first(function (string $line) {
            $lower = strtolower($line);
            return strlen($line) >= 3
                && strlen($line) <= 70
                && !str_contains($line, '@')
                && !str_contains($line, 'http')
                && !preg_match('/\d{3,}/', $line)
                && !str_contains($lower, 'curriculum vitae')
                && !str_contains($lower, 'resume');
        }) ?? '');

        $location = $this->extractBestLocation($text, $lines->all());

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'location' => $location,
            'linkedin' => (string) $linkedin,
            'github' => (string) $github,
            'portfolio' => (string) $portfolio,
        ];
    }

    private function pickString(?string $preferred, ?string $fallback = null): string
    {
        $preferred = trim((string) $preferred);
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) $fallback);
    }

    private function extractUrlsFromPdfBinary(string $absolutePath): array
    {
        $binary = @file_get_contents($absolutePath);
        if ($binary === false || $binary === '') {
            return [];
        }

        preg_match_all('/https?:\/\/[^\s<>()]+/i', $binary, $matches);

        return collect($matches[0] ?? [])
            ->map(fn($url) => $this->normalizeDiscoveredUrl((string) $url))
            ->filter(fn($url) => $url !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDiscoveredUrl(string $url): string
    {
        $url = trim(str_replace('\\/', '/', $url));
        $url = rtrim($url, " \t\n\r\0\x0B.,;:)'\"<>");
        return $url;
    }

    private function normalizeProfileUrl(?string $value, string $type): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/https?:\/\/[^\s)]+/i', $value, $match)) {
            $value = (string) $match[0];
        } else {
            if ($type === 'linkedin' && preg_match('/(?:www\.)?linkedin\.com\/[^\s|]+/i', $value, $match)) {
                $value = 'https://' . ltrim((string) $match[0], '/');
            } elseif ($type === 'github' && preg_match('/(?:www\.)?github\.com\/[^\s|]+/i', $value, $match)) {
                $value = 'https://' . ltrim((string) $match[0], '/');
            } elseif (preg_match('/^www\.[^\s]+$/i', $value)) {
                $value = 'https://' . $value;
            }
        }

        $value = $this->normalizeDiscoveredUrl($value);
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        if ($host === '' || $this->isIgnoredUtilityUrl($value)) {
            return '';
        }

        if ($type === 'linkedin' && !str_contains($host, 'linkedin.com')) {
            return '';
        }

        if ($type === 'github' && !str_contains($host, 'github.com')) {
            return '';
        }

        if ($type === 'portfolio' && (str_contains($host, 'linkedin.com') || str_contains($host, 'github.com'))) {
            return '';
        }

        return $value;
    }

    private function isIgnoredUtilityUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }

        foreach (['w3.org', 'adobe.com', 'purl.org'] as $ignoredDomain) {
            if (str_contains($host, $ignoredDomain)) {
                return true;
            }
        }

        return false;
    }

    private function extractBestLocation(string $text, array $lines): string
    {
        // Prefer explicit location/address labels.
        foreach ($lines as $line) {
            if (!is_string($line) || trim($line) === '') {
                continue;
            }

            if (preg_match('/\b(location|address)\b\s*[:\-]?\s*(.+)$/i', $line, $match)) {
                $candidate = $this->cleanLocationCandidate((string) ($match[2] ?? ''));
                if ($this->isLikelyLocation($candidate)) {
                    return $candidate;
                }
            }
        }

        // Parse contact rows with separators like "|".
        foreach ($lines as $line) {
            if (!is_string($line) || trim($line) === '') {
                continue;
            }

            $segments = preg_split('/\s*[|•]\s*/', $line) ?: [];
            foreach ($segments as $segment) {
                $candidate = $this->cleanLocationCandidate((string) $segment);
                if ($this->isLikelyLocation($candidate)) {
                    return $candidate;
                }
            }
        }

        // Final fallback: city, country pattern from full text.
        if (preg_match('/\b([A-Za-z][A-Za-z .\'-]{1,40},\s*[A-Za-z][A-Za-z .\'-]{1,40})\b/u', $text, $match)) {
            $candidate = $this->cleanLocationCandidate((string) ($match[1] ?? ''));
            if ($this->isLikelyLocation($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function cleanLocationCandidate(string $candidate): string
    {
        $candidate = trim($candidate);
        $candidate = trim(preg_replace('/\s+/', ' ', $candidate) ?? $candidate);
        $candidate = trim($candidate, ".,;:|");
        return $candidate;
    }

    private function isLikelyLocation(string $candidate): bool
    {
        return $this->scoreLocationCandidate($candidate) >= 3;
    }

    private function scoreLocationCandidate(string $candidate): int
    {
        $candidate = $this->cleanLocationCandidate($candidate);
        if ($candidate === '') {
            return -10;
        }

        $lower = strtolower($candidate);
        $score = 0;

        if (mb_strlen($candidate) >= 3 && mb_strlen($candidate) <= 80) {
            $score += 1;
        } else {
            $score -= 2;
        }

        if (str_contains($candidate, '@') || str_contains($lower, 'http')) {
            return -10;
        }

        if (str_contains($lower, 'linkedin') || str_contains($lower, 'github')) {
            return -8;
        }

        if (preg_match('/\+?\d[\d\-\s()]{6,}/', $candidate)) {
            return -8;
        }

        if (preg_match('/[.;!?]/', $candidate)) {
            $score -= 2;
        }

        $wordCount = count(preg_split('/\s+/', $candidate) ?: []);
        if ($wordCount <= 8) {
            $score += 1;
        } else {
            $score -= 3;
        }

        if (str_contains($candidate, ',')) {
            $score += 2;
            $segments = array_filter(array_map('trim', explode(',', $candidate)));
            if (!empty($segments)) {
                $compactSegments = collect($segments)->every(function (string $segment): bool {
                    $words = array_values(array_filter(preg_split('/\s+/', $segment) ?: []));
                    return count($words) >= 1 && count($words) <= 4;
                });

                if ($compactSegments) {
                    $score += 1;
                } else {
                    $score -= 2;
                }
            }
        } else {
            $score -= 1;
        }

        foreach (self::LOCATION_HINTS as $hint) {
            if (str_contains($lower, $hint)) {
                $score += 2;
                break;
            }
        }

        foreach (self::NON_LOCATION_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                $score -= 3;
            }
        }

        $ingWords = preg_match_all('/\b[a-z]{4,}ing\b/i', $candidate);
        if (is_int($ingWords) && $ingWords > 1) {
            $score -= 2;
        }

        return $score;
    }
}
