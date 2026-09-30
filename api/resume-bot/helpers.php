<?php

/**
 * Fetch public GitHub profile + recent repos for resume bot context.
 * Uses curl extension or curl.exe fallback. Caches in session for 30 minutes.
 */
function resume_bot_http_get(string $url, int $timeout = 15): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/vnd.github+json',
                'User-Agent: hanova-resume-bot',
            ],
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
    }

    $tmpOut = tempnam(sys_get_temp_dir(), 'gh');
    $cmd = sprintf(
        'curl.exe -sS -H %s -H %s --max-time %d -o %s -w "%%{http_code}" %s',
        escapeshellarg('Accept: application/vnd.github+json'),
        escapeshellarg('User-Agent: hanova-resume-bot'),
        $timeout,
        escapeshellarg($tmpOut),
        escapeshellarg($url)
    );
    $codeRaw = shell_exec($cmd);
    $body = is_file($tmpOut) ? file_get_contents($tmpOut) : false;
    @unlink($tmpOut);
    $code = (int) trim((string) $codeRaw);
    return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
}

function resume_bot_github_context(): string
{
    $cacheKey = 'resume_bot_github_ctx';
    $cacheAtKey = 'resume_bot_github_at';
    $now = time();

    if (
        isset($_SESSION[$cacheKey], $_SESSION[$cacheAtKey])
        && ($now - (int) $_SESSION[$cacheAtKey]) < 1800
        && is_string($_SESSION[$cacheKey])
        && $_SESSION[$cacheKey] !== ''
    ) {
        return $_SESSION[$cacheKey];
    }

    $userJson = resume_bot_http_get('https://api.github.com/users/hanyehkhl');
    $reposJson = resume_bot_http_get('https://api.github.com/users/hanyehkhl/repos?sort=updated&per_page=12');

    $lines = [];
    $lines[] = 'GitHub profile: https://github.com/hanyehkhl';

    if ($userJson) {
        $user = json_decode($userJson, true);
        if (is_array($user)) {
            $lines[] = 'Name: ' . ($user['name'] ?? 'hanyeh');
            $lines[] = 'Public repos: ' . ($user['public_repos'] ?? '?');
            $lines[] = 'Followers: ' . ($user['followers'] ?? '?');
            if (!empty($user['bio'])) {
                $lines[] = 'Bio: ' . $user['bio'];
            }
        }
    }

    if ($reposJson) {
        $repos = json_decode($reposJson, true);
        if (is_array($repos)) {
            $lines[] = 'Recent / featured repositories:';
            foreach ($repos as $repo) {
                if (!is_array($repo)) {
                    continue;
                }
                $name = $repo['name'] ?? '';
                $desc = trim((string) ($repo['description'] ?? ''));
                $url = $repo['html_url'] ?? '';
                $lang = $repo['language'] ?? '';
                $updated = isset($repo['updated_at']) ? substr($repo['updated_at'], 0, 10) : '';
                $lines[] = "- {$name} ({$lang}) updated {$updated}: {$desc} — {$url}";
            }
        }
    }

    // Static fallback from known public profile if API fails
    if (count($lines) < 3) {
        $lines[] = 'Featured (known): galaxy-based-search-community, gbsa-community-detection, product-assistant, yolov3-car-counter';
        $lines[] = 'Focus: FastAPI, RAG, LLMs, deep learning, GbSA community detection, OpenCV/YOLOv3';
    }

    $ctx = implode("\n", $lines);
    $_SESSION[$cacheKey] = $ctx;
    $_SESSION[$cacheAtKey] = $now;
    return $ctx;
}

function resume_bot_csv_escape($value): string
{
    $value = str_replace('"', '""', (string) $value);
    return '"' . $value . '"';
}

function resume_bot_save_karfarma(array $row): bool
{
    $dir = __DIR__ . '/../../uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $file = $dir . '/karfarma.csv';
    $isNew = !is_file($file) || filesize($file) === 0;

    $line = implode(',', [
        resume_bot_csv_escape($row['datetime'] ?? date('Y-m-d H:i:s')),
        resume_bot_csv_escape($row['name'] ?? ''),
        resume_bot_csv_escape($row['phone'] ?? ''),
        resume_bot_csv_escape($row['request'] ?? ''),
        resume_bot_csv_escape($row['source'] ?? 'resume-chat'),
    ]) . "\n";

    $fp = @fopen($file, 'cb');
    if ($fp === false) {
        return false;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    fseek($fp, 0, SEEK_END);
    if ($isNew || ftell($fp) === 0) {
        fwrite($fp, "\xEF\xBB\xBF");
        fwrite($fp, "datetime,name,phone,request,source\n");
    }

    $ok = fwrite($fp, $line) !== false;
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}

/**
 * Parse hidden marker from model output:
 * [[KARFARMA|name=...|phone=...|request=...]]
 * Returns [cleanText, savedBool]
 */
function resume_bot_extract_karfarma(string $text): array
{
    $saved = false;
    $clean = $text;

    if (preg_match('/\[\[KARFARMA\|(.*?)\]\]/us', $text, $m)) {
        $payload = $m[1];
        $fields = ['name' => '', 'phone' => '', 'request' => ''];
        foreach (explode('|', $payload) as $part) {
            $part = trim($part);
            if ($part === '' || strpos($part, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            $k = strtolower(trim($k));
            if (isset($fields[$k])) {
                $fields[$k] = trim($v);
            }
        }

        if ($fields['phone'] !== '' && $fields['request'] !== '') {
            $saved = resume_bot_save_karfarma([
                'datetime' => date('Y-m-d H:i:s'),
                'name'     => $fields['name'],
                'phone'    => $fields['phone'],
                'request'  => $fields['request'],
                'source'   => 'resume-chat',
            ]);
        }

        $clean = trim(preg_replace('/\[\[KARFARMA\|.*?\]\]/us', '', $text));
        if ($saved && $clean === '') {
            $clean = 'درخواستت ثبت شد. به‌زودی با همان شماره‌ای که دادی پیگیری می‌شود.';
        } elseif ($saved) {
            $clean .= "\n\n✓ درخواست پروژه‌ات در فایل کارفرما ثبت شد.";
        }
    }

    return [$clean, $saved];
}
