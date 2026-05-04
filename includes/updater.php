<?php
/**
 * AKSOY GROUP — Güncelleme Motoru
 * GitHub Releases'tan ZIP indir, migration çalıştır, dosyaları güncelle.
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class Updater
{
    public const GH_API = 'https://api.github.com';

    private string $owner;
    private string $repo;
    private string $token;
    private string $currentVersion;

    public function __construct()
    {
        $this->owner = AG_GITHUB_OWNER;
        $this->repo  = AG_GITHUB_REPO;
        $this->token = (string)setting('github_token', '');
        $this->currentVersion = (string)setting('current_version', AG_VERSION);
    }

    /** Son release bilgisini al. */
    public function latestRelease(): ?array
    {
        $url = self::GH_API . "/repos/{$this->owner}/{$this->repo}/releases/latest";
        $resp = $this->ghCurl($url);
        if (!$resp || empty($resp['tag_name'])) {
            return null;
        }
        return [
            'version'      => ltrim($resp['tag_name'], 'v'),
            'name'         => $resp['name'] ?? $resp['tag_name'],
            'body'         => $resp['body'] ?? '',
            'published_at' => $resp['published_at'] ?? null,
            'zip_url'      => $resp['zipball_url'] ?? null,
            'assets'       => $resp['assets'] ?? [],
        ];
    }

    /** Tüm release listesi. */
    public function allReleases(int $perPage = 30): array
    {
        $url = self::GH_API . "/repos/{$this->owner}/{$this->repo}/releases?per_page={$perPage}";
        $resp = $this->ghCurl($url);
        return is_array($resp) ? $resp : [];
    }

    /** Güncelleme var mı? */
    public function hasUpdate(): array
    {
        $latest = $this->latestRelease();
        if (!$latest) {
            return ['available' => false, 'reason' => 'GitHub release okunamadı.'];
        }
        $cmp = version_compare($latest['version'], $this->currentVersion);
        return [
            'available' => $cmp > 0,
            'current'   => $this->currentVersion,
            'latest'    => $latest['version'],
            'name'      => $latest['name'],
            'notes'     => $latest['body'],
            'date'      => $latest['published_at'],
            'zip_url'   => $latest['zip_url'],
            'assets'    => $latest['assets'],
        ];
    }

    /** Belirli versiyonu indir, aç, yerleştir, migration çalıştır. */
    public function install(string $version): array
    {
        $log = [];
        $log[] = "Güncelleme başladı: v{$version}";

        // 1. Release info
        $url = self::GH_API . "/repos/{$this->owner}/{$this->repo}/releases/tags/v{$version}";
        $rel = $this->ghCurl($url);
        if (!$rel || empty($rel['tag_name'])) {
            return ['ok' => false, 'log' => $log, 'error' => 'Release bulunamadı.'];
        }

        // 2. ZIP URL'i: önce assets, yoksa zipball
        $zipUrl = null;
        if (!empty($rel['assets']) && is_array($rel['assets'])) {
            foreach ($rel['assets'] as $a) {
                if (str_ends_with($a['name'] ?? '', '.zip')) {
                    $zipUrl = $a['url']; // /repos/.../releases/assets/{id}
                    break;
                }
            }
        }
        if (!$zipUrl) {
            $zipUrl = $rel['zipball_url'] ?? null;
        }
        if (!$zipUrl) {
            return ['ok' => false, 'log' => $log, 'error' => 'ZIP URL bulunamadı.'];
        }
        $log[] = "ZIP URL alındı.";

        // 3. ZIP indir
        $zipPath = AG_LOGS . '/updater/v' . $version . '.zip';
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }
        $ok = $this->downloadZip($zipUrl, $zipPath);
        if (!$ok) {
            return ['ok' => false, 'log' => $log, 'error' => 'ZIP indirilemedi.'];
        }
        $log[] = "ZIP indirildi: " . formatBytes(filesize($zipPath));

        // 4. ZIP'i aç
        $extractDir = AG_LOGS . '/updater/v' . $version;
        if (is_dir($extractDir)) {
            $this->rrmdir($extractDir);
        }
        mkdir($extractDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'log' => $log, 'error' => 'ZIP açılamadı.'];
        }
        $zip->extractTo($extractDir);
        $zip->close();
        $log[] = "ZIP açıldı.";

        // 5. Açılan klasörün kökü (zipball'da owner-repo-sha/ olur)
        $root = $extractDir;
        $items = array_diff(scandir($extractDir), ['.', '..']);
        if (count($items) === 1) {
            $first = $extractDir . '/' . reset($items);
            if (is_dir($first)) {
                $root = $first;
            }
        }

        // 6. Migration çalıştır (varsa)
        $migrationFile = $root . '/migrations/v' . $version . '.sql';
        if (file_exists($migrationFile)) {
            $log[] = "Migration bulundu: v{$version}.sql";
            try {
                $sql = file_get_contents($migrationFile);
                $this->runMigration($sql);
                $log[] = "Migration başarıyla uygulandı.";
            } catch (Throwable $e) {
                return ['ok' => false, 'log' => $log, 'error' => 'Migration hatası: ' . $e->getMessage()];
            }
        } else {
            $log[] = "Migration dosyası yok, atlandı.";
        }

        // 7. Dosyaları kopyala (config.php ve uploads/ hariç)
        $copied = $this->copyTree($root, AG_ROOT, [
            'config.php',
            'uploads',
            'logs',
            '.git',
            '.gitignore',
            '.gitattributes',
            '.github',
            'README.md',
            'install.php',
            'setup.php',
        ]);
        $log[] = "{$copied} dosya güncellendi.";

        // 8. Versiyon kaydı
        DB::insert('ag_versions', [
            'version'             => $version,
            'release_name'        => $rel['name'] ?? null,
            'release_notes'       => $rel['body'] ?? null,
            'migration_executed'  => file_exists($migrationFile) ? 1 : 0,
            'migration_log'       => implode("\n", $log),
            'installed_by'        => Auth::userId() ?: null,
        ]);
        DB::exec("UPDATE ag_settings SET setting_value = ? WHERE setting_key = 'current_version'", [$version]);

        // 9. Temizlik
        @unlink($zipPath);
        $this->rrmdir($extractDir);
        $log[] = "Güncelleme tamamlandı.";

        Audit::log('system_update', 'version', null, ['from' => $this->currentVersion], ['to' => $version], 'warning');

        return ['ok' => true, 'log' => $log, 'version' => $version];
    }

    /** Yüklü versiyonların geçmişi. */
    public function history(): array
    {
        return DB::all("SELECT v.*, u.full_name as installer
                        FROM ag_versions v
                        LEFT JOIN ag_users u ON u.id = v.installed_by
                        ORDER BY v.installed_at DESC LIMIT 50");
    }

    // ── PRIVATE ─────────────────────────────────────────

    private function ghCurl(string $url): mixed
    {
        $ch = curl_init($url);
        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: AksoyGroup-Updater/1.0',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) return null;
        return json_decode($body, true);
    }

    private function downloadZip(string $url, string $dest): bool
    {
        $ch = curl_init($url);
        $fp = fopen($dest, 'wb');
        if (!$fp) {
            curl_close($ch);
            return false;
        }
        $headers = [
            'Accept: application/octet-stream',
            'User-Agent: AksoyGroup-Updater/1.0',
        ];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $code !== 200) {
            @unlink($dest);
            return false;
        }
        return true;
    }

    /** SQL'i akıllıca parçala (string içindeki ; karakterlerini görmezden gelir). */
    private function runMigration(string $sql): void
    {
        $statements = self::splitSql($sql);
        foreach ($statements as $stmt) {
            if ($stmt === '') continue;
            // DDL statement'ler implicit commit yapar, transaction sarmalı her ifadede etkisiz olur.
            DB::pdo()->exec($stmt);
        }
    }

    /** String/yorum sınırlarını anlayan SQL splitter. */
    public static function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);
        $i = 0;
        while ($i < $len) {
            $ch = $sql[$i];
            if ($inString) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $current .= $ch . $sql[$i + 1];
                    $i += 2; continue;
                }
                if ($ch === $stringChar) {
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $current .= $ch . $sql[$i + 1];
                        $i += 2; continue;
                    }
                    $inString = false;
                }
                $current .= $ch; $i++; continue;
            }
            if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                continue;
            }
            if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                $i += 2;
                while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
                $i += 2; continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inString = true; $stringChar = $ch;
                $current .= $ch; $i++; continue;
            }
            if ($ch === ';') {
                $stmt = trim($current);
                if ($stmt !== '') $statements[] = $stmt;
                $current = ''; $i++; continue;
            }
            $current .= $ch; $i++;
        }
        $stmt = trim($current);
        if ($stmt !== '') $statements[] = $stmt;
        return $statements;
    }

    private function copyTree(string $src, string $dst, array $exclude = []): int
    {
        $count = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $item) {
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $relTop = explode('/', str_replace('\\', '/', $rel))[0];
            if (in_array($relTop, $exclude, true)) continue;
            if (in_array(basename($rel), $exclude, true)) continue;

            $target = $dst . '/' . $rel;
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $td = dirname($target);
                if (!is_dir($td)) mkdir($td, 0755, true);
                if (copy($item->getPathname(), $target)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
