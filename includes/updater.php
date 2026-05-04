<?php
/**
 * AKSOY GROUP — Güncelleme Motoru v2 (ERP pattern)
 * --------------------------------------------------
 * Özellikler:
 *  - GitHub Git tree SHA tabanlı dosya-bazlı diff
 *  - Smart Sync: sadece değişen dosyaları indir
 *  - Force Sync: tüm dosyaları zorla yenile
 *  - Rollback: yedekten geri dön (max 10 yedek tutar)
 *  - GitHub Releases: ZIP asset indir + uygula
 *  - Migration tracking: ag_migrations tablosu (idempotent)
 *  - Akıllı SQL splitter (string-içi `;` korur)
 *  - .gh_token dosyası + ag_settings fallback
 *  - Korumalı dosyalar: config.php, uploads/, logs/, .htaccess
 */

declare(strict_types=1);

class Updater
{
    public const TOKEN_FILE = AG_INCLUDES . '/.gh_token';
    public const BACKUP_DIR = AG_ROOT . '/storage/backups';
    public const VERSION_FILE = AG_ROOT . '/version.php';
    public const MAX_BACKUPS = 10;
    public const TEMP_DIR = AG_ROOT . '/storage/tmp';

    public const EXCLUDES = [
        'includes/config.php', 'includes/.installed.lock', 'includes/.gh_token',
        'storage/', 'uploads/', 'logs/', 'cache/',
        '.git/', '.github/', '.gitignore', 'node_modules/', 'vendor/',
        '.env', '.env.example',
    ];

    private string $owner;
    private string $repo;
    private string $branch;
    private string $current;

    public function __construct()
    {
        $this->owner   = AG_GITHUB_OWNER;
        $this->repo    = AG_GITHUB_REPO;
        $this->branch  = AG_GITHUB_BRANCH;
        $this->current = self::localVersion();

        if (!is_dir(self::BACKUP_DIR)) @mkdir(self::BACKUP_DIR, 0755, true);
        if (!is_dir(self::TEMP_DIR))   @mkdir(self::TEMP_DIR, 0755, true);
        $ht = self::BACKUP_DIR . '/.htaccess';
        if (!file_exists($ht)) @file_put_contents($ht, "Order deny,allow\nDeny from all\n");
    }

    // ═══════════════════════════════════════════════
    // VERSİYON
    // ═══════════════════════════════════════════════

    public static function localVersion(): string
    {
        // 1) version.php
        if (file_exists(self::VERSION_FILE)) {
            $c = @file_get_contents(self::VERSION_FILE);
            if (preg_match("/AG_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $c ?: '', $m)) return $m[1];
        }
        // 2) manifest.json
        $mf = AG_ROOT . '/manifest.json';
        if (file_exists($mf)) {
            $m = json_decode(@file_get_contents($mf) ?: '', true);
            if (!empty($m['version'])) return $m['version'];
        }
        // 3) DB ag_settings.current_version
        try {
            $v = DB::scalar("SELECT setting_value FROM ag_settings WHERE setting_key='current_version'");
            if ($v) return $v;
        } catch (Throwable $e) {}
        // 4) AG_VERSION
        return defined('AG_VERSION') ? AG_VERSION : '1.0.0';
    }

    public function remoteVersion(): string
    {
        $tok = $this->token();
        $body = $this->ghDownload('manifest.json', $tok);
        if ($body) {
            $m = json_decode($body, true);
            if (!empty($m['version'])) return $m['version'];
        }
        return '?';
    }

    public function fetchLatest(): array
    {
        $r = $this->ghAPI('/releases/latest');
        if (!$r || empty($r['tag_name'])) {
            throw new RuntimeException('GitHub release alınamadı.');
        }
        return [
            'version'   => ltrim((string)$r['tag_name'], 'v'),
            'name'      => (string)($r['name'] ?? $r['tag_name']),
            'body'      => (string)($r['body'] ?? ''),
            'published' => (string)($r['published_at'] ?? ''),
            'zip_url'   => $this->releaseZipUrl($r),
            'assets'    => $r['assets'] ?? [],
        ];
    }

    private function releaseZipUrl(array $rel): string
    {
        // Önce asset olarak yüklü ZIP'i tercih et (curl auth ile çalışır)
        foreach ($rel['assets'] ?? [] as $a) {
            $name = strtolower($a['name'] ?? '');
            if (str_ends_with($name, '.zip')) return (string)$a['url']; // api endpoint
        }
        // Fallback: zipball (tag bazlı)
        return (string)($rel['zipball_url'] ?? '');
    }

    // ═══════════════════════════════════════════════
    // GITHUB API
    // ═══════════════════════════════════════════════

    public function token(): string
    {
        if (file_exists(self::TOKEN_FILE)) {
            $t = trim((string)@file_get_contents(self::TOKEN_FILE));
            $t = preg_replace('/[^a-zA-Z0-9_\-]/', '', $t);
            if ($t) return $t;
        }
        try {
            $t = DB::scalar("SELECT setting_value FROM ag_settings WHERE setting_key='github_token'");
            if ($t) return (string)$t;
        } catch (Throwable $e) {}
        return '';
    }

    public function saveToken(string $tok): bool
    {
        $tok = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($tok));
        if (!$tok) return false;

        $savedFile = false;
        $savedDb = false;

        // 1) Dosyaya yazmayı dene (.gh_token)
        $tokenDir = dirname(self::TOKEN_FILE);
        if (is_writable($tokenDir) || is_writable(self::TOKEN_FILE)) {
            if (@file_put_contents(self::TOKEN_FILE, $tok) !== false) {
                @chmod(self::TOKEN_FILE, 0600);
                $savedFile = true;
            }
        }

        // 2) DB'ye fallback (her durumda yaz — okurken file öncelikli)
        try {
            DB::exec(
                "INSERT INTO ag_settings (setting_key, setting_value, setting_group, setting_type, label, sort_order, is_public)
                 VALUES ('github_token', ?, 'sistem', 'text', 'GitHub Token', 20, 0)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$tok]
            );
            $savedDb = true;
        } catch (Throwable $e) {
            error_log('[Updater::saveToken] DB hatası: ' . $e->getMessage());
        }

        return $savedFile || $savedDb;
    }

    public function ghAPI(string $path): ?array
    {
        $tok = $this->token();
        $hdrs = ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'];
        if ($tok) $hdrs[] = 'Authorization: token ' . $tok;
        $url = 'https://api.github.com/repos/' . $this->owner . '/' . $this->repo . $path;
        $r = $this->httpRequest($url, $hdrs, 30);
        if ($r['code'] !== 200) return null;
        return json_decode($r['body'], true);
    }

    public function ghDownload(string $path, string $tok = ''): ?string
    {
        $tok = $tok ?: $this->token();
        $hdrs = ['Accept: application/vnd.github+json'];
        if ($tok) $hdrs[] = 'Authorization: token ' . $tok;
        // 1) Contents API (base64)
        $url = 'https://api.github.com/repos/' . $this->owner . '/' . $this->repo
             . '/contents/' . str_replace('%2F', '/', rawurlencode($path)) . '?ref=' . $this->branch;
        $r = $this->httpRequest($url, $hdrs, 30);
        if ($r['code'] === 200) {
            $d = json_decode($r['body'], true);
            if (!empty($d['content'])) return base64_decode(str_replace(["\n","\r"], '', $d['content']));
        }
        // 2) Raw fallback
        $url = 'https://raw.githubusercontent.com/' . $this->owner . '/' . $this->repo . '/' . $this->branch . '/' . $path;
        $r = $this->httpRequest($url, $hdrs, 60);
        return $r['code'] === 200 ? $r['body'] : null;
    }

    /** GitHub Git tree → tüm repo dosyalarını SHA ile döndür. */
    public function repoTree(): array
    {
        $tree = $this->ghAPI('/git/trees/' . $this->branch . '?recursive=1');
        if (!$tree || empty($tree['tree'])) return [];
        $out = [];
        foreach ($tree['tree'] as $i) {
            if (($i['type'] ?? '') !== 'blob') continue;
            $p = (string)$i['path'];
            if (self::isExcluded($p)) continue;
            $out[] = ['path' => $p, 'sha' => $i['sha'], 'size' => $i['size'] ?? 0];
        }
        usort($out, fn($a, $b) => strcmp($a['path'], $b['path']));
        return $out;
    }

    public static function blobSha(string $content): string
    {
        return sha1('blob ' . strlen($content) . "\0" . $content);
    }

    public static function isExcluded(string $path): bool
    {
        foreach (self::EXCLUDES as $ex) {
            if ($path === $ex) return true;
            if (str_ends_with($ex, '/') && str_starts_with($path, $ex)) return true;
        }
        return false;
    }

    // ═══════════════════════════════════════════════
    // DOSYA-BAZLI DIFF
    // ═══════════════════════════════════════════════

    /** Repodaki her dosyayı lokalle karşılaştır. */
    public function compareFiles(): array
    {
        $remote = $this->repoTree();
        $diff = ['new' => [], 'changed' => [], 'unchanged' => [], 'total' => count($remote)];
        foreach ($remote as $f) {
            $local = AG_ROOT . '/' . $f['path'];
            if (!file_exists($local)) {
                $diff['new'][] = $f;
            } else {
                $localSha = self::blobSha((string)file_get_contents($local));
                if ($localSha !== $f['sha']) $diff['changed'][] = $f;
                else $diff['unchanged'][] = $f;
            }
        }
        return $diff;
    }

    /** Tek dosyayı GitHub'dan çek ve lokale yaz. */
    public function syncFile(string $path): array
    {
        if (self::isExcluded($path)) {
            return ['ok' => false, 'msg' => 'Korumalı dosya: ' . $path];
        }
        $body = $this->ghDownload($path);
        if ($body === null) return ['ok' => false, 'msg' => 'İndirilemedi: ' . $path];
        $local = AG_ROOT . '/' . $path;
        $dir = dirname($local);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $bytes = @file_put_contents($local, $body);
        return ['ok' => $bytes !== false, 'msg' => $bytes !== false ? "✓ $path" : "✗ Yazılamadı: $path", 'bytes' => $bytes];
    }

    /** Akıllı senkronizasyon: sadece değişen + yeni dosyalar. */
    public function smartSync(?array $onlyPaths = null): array
    {
        $log = [];
        $diff = $this->compareFiles();
        $targets = array_merge($diff['new'], $diff['changed']);
        if ($onlyPaths) {
            $set = array_flip($onlyPaths);
            $targets = array_filter($targets, fn($f) => isset($set[$f['path']]));
        }

        $log[] = '🔍 ' . count($targets) . ' dosya güncellenecek (' . count($diff['new']) . ' yeni, ' . count($diff['changed']) . ' değişmiş)';
        $this->createBackup('pre_smart_sync', $log);

        $ok = 0; $fail = 0;
        foreach ($targets as $f) {
            $r = $this->syncFile($f['path']);
            if ($r['ok']) { $ok++; }
            else         { $fail++; $log[] = $r['msg']; }
        }
        $log[] = "✓ $ok dosya güncellendi" . ($fail ? ", $fail başarısız" : '');

        $this->runMigrationsFromDir(AG_ROOT . '/migrations', $log);
        $this->writeVersionFile($this->remoteVersion(), $log);

        return ['success' => $fail === 0, 'message' => "Smart Sync: $ok başarılı, $fail başarısız.", 'log' => $log];
    }

    /** Force sync: tüm repo dosyalarını sıfırdan indir. */
    public function forceSync(): array
    {
        $log = [];
        $remote = $this->repoTree();
        $log[] = '🔥 Force Sync — ' . count($remote) . ' dosya zorla yenilenecek';
        $this->createBackup('pre_force_sync', $log);

        $ok = 0; $fail = 0;
        foreach ($remote as $f) {
            $r = $this->syncFile($f['path']);
            if ($r['ok']) { $ok++; }
            else         { $fail++; }
        }
        $log[] = "✓ $ok dosya yenilendi" . ($fail ? ", $fail başarısız" : '');
        $this->runMigrationsFromDir(AG_ROOT . '/migrations', $log);
        $this->writeVersionFile($this->remoteVersion(), $log);
        return ['success' => $fail === 0, 'message' => "Force Sync: $ok başarılı.", 'log' => $log];
    }

    // ═══════════════════════════════════════════════
    // ZIP / RELEASE TABANLI GÜNCELLEME
    // ═══════════════════════════════════════════════

    /** GitHub release'in ZIP asset'ini indir + uygula. */
    public function install(string $version): array
    {
        $log = [];
        $log[] = "⏳ v{$version} kuruluyor…";
        $rel = $this->ghAPI('/releases/tags/v' . ltrim($version, 'v'));
        if (!$rel) throw new RuntimeException("v{$version} release bulunamadı.");

        $zipUrl = $this->releaseZipUrl($rel);
        if (!$zipUrl) throw new RuntimeException('Release ZIP URL\'i yok.');

        $this->createBackup("pre_v{$version}", $log);

        // ZIP indir
        $tmpZip = self::TEMP_DIR . '/release_' . time() . '.zip';
        $tok = $this->token();
        $hdrs = ['Accept: application/octet-stream'];
        if ($tok) $hdrs[] = 'Authorization: token ' . $tok;
        $r = $this->httpRequest($zipUrl, $hdrs, 180);
        if ($r['code'] !== 200 || !$r['body']) {
            throw new RuntimeException('ZIP indirilemedi: HTTP ' . $r['code']);
        }
        @file_put_contents($tmpZip, $r['body']);
        $log[] = '✓ ZIP indirildi (' . round(filesize($tmpZip)/1024, 1) . ' KB)';

        $result = $this->updateFromZip($tmpZip);
        @unlink($tmpZip);

        $result['log'] = array_merge($log, $result['log'] ?? []);
        if ($result['success']) {
            $this->writeVersionFile($version, $result['log']);
            $this->recordVersion($version, $rel['name'] ?? "v$version", true);
        }
        return $result;
    }

    /** Yerel ZIP dosyasından güncelleme uygula. */
    public function updateFromZip(string $zipFilePath): array
    {
        $log = [];
        if (!file_exists($zipFilePath)) return $this->fail('ZIP dosyası bulunamadı.', $log);
        if (filesize($zipFilePath) > 100 * 1024 * 1024) return $this->fail('ZIP çok büyük (max 100MB).', $log);
        if (!class_exists('ZipArchive')) return $this->fail('ZipArchive PHP uzantısı eksik.', $log);

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) return $this->fail('ZIP açılamadı.', $log);

        // ZIP içindeki manifest.json'u bul (kök veya alt klasör)
        $manifestJson = $zip->getFromName('manifest.json');
        $zipPrefix = '';
        if (!$manifestJson) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (basename($entry) === 'manifest.json') {
                    $manifestJson = $zip->getFromIndex($i);
                    $dir = dirname($entry);
                    $zipPrefix = ($dir !== '.') ? rtrim($dir, '/') . '/' : '';
                    break;
                }
            }
        }
        if (!$manifestJson) {
            $zip->close();
            return $this->fail('Geçersiz paket: manifest.json yok.', $log);
        }
        $manifest = json_decode($manifestJson, true);
        if (empty($manifest['version'])) {
            $zip->close();
            return $this->fail('manifest.json hatalı.', $log);
        }
        $newVersion = $manifest['version'];
        $isCritical = !empty($manifest['critical']);

        $log[] = "📦 Paket: v{$newVersion}" . ($isCritical ? ' (kritik)' : '');
        $log[] = "🔧 Mevcut: v{$this->current}";

        if (!$isCritical && !version_compare($newVersion, $this->current, '>')) {
            $zip->close();
            return $this->fail("v{$newVersion} zaten yüklü ya da daha eski.", $log);
        }

        // Geçici klasöre çıkar
        $extractPath = self::TEMP_DIR . '/upd_' . time();
        @mkdir($extractPath, 0755, true);
        $zip->extractTo($extractPath);
        $zip->close();
        $log[] = "✓ ZIP açıldı";

        // Gerçek kaynak klasörü
        $srcDir = $extractPath;
        if ($zipPrefix) {
            $cand = $extractPath . '/' . rtrim($zipPrefix, '/');
            if (is_dir($cand)) $srcDir = $cand;
        }
        if ($srcDir === $extractPath) {
            $subs = glob($extractPath . '/*', GLOB_ONLYDIR) ?: [];
            if (count($subs) === 1 && is_file($subs[0] . '/manifest.json')) $srcDir = $subs[0];
        }
        $log[] = "📂 Kaynak: " . str_replace($extractPath, '[tmp]', $srcDir);

        // Dosyaları kopyala (korumalıları atla)
        $copied = 0; $failed = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $file) {
            $real = $file->getRealPath();
            if ($real === false) continue;
            $rel = ltrim(str_replace($srcDir, '', $real), '/\\');
            $rel = str_replace('\\', '/', $rel);
            if (!$rel || $rel === 'manifest.json') continue;
            if (self::isExcluded($rel)) continue;

            $dst = AG_ROOT . '/' . $rel;
            if ($file->isDir()) {
                if (!is_dir($dst)) @mkdir($dst, 0755, true);
            } else {
                $dstDir = dirname($dst);
                if (!is_dir($dstDir)) @mkdir($dstDir, 0755, true);
                if (@copy($real, $dst)) $copied++;
                else { $failed++; $log[] = "⚠ Kopyalanamadı: $rel"; }
            }
        }
        $log[] = "✓ $copied dosya kopyalandı" . ($failed ? ", $failed hatalı" : '');

        // Migration'ları çalıştır
        $migDir = $srcDir . '/migrations';
        if (is_dir($migDir)) $this->runMigrationsFromDir($migDir, $log);

        // Geçici dosyaları sil
        $this->rmdirRecursive($extractPath);
        $log[] = "✓ Geçici dosyalar temizlendi";

        $this->current = $newVersion;
        return ['success' => $failed === 0, 'message' => "v{$newVersion} kuruldu.", 'log' => $log, 'new_version' => $newVersion];
    }

    // ═══════════════════════════════════════════════
    // YEDEK / ROLLBACK
    // ═══════════════════════════════════════════════

    public function createBackup(string $label, ?array &$log = null): ?string
    {
        $stamp = date('Y-m-d_His');
        $name = "{$stamp}_{$label}.zip";
        $bkPath = self::BACKUP_DIR . '/' . $name;
        if (!class_exists('ZipArchive')) {
            if ($log !== null) $log[] = '⚠ Yedek alınamadı (ZipArchive yok)';
            return null;
        }
        $zip = new ZipArchive();
        if ($zip->open($bkPath, ZipArchive::CREATE) !== true) {
            if ($log !== null) $log[] = '⚠ Yedek ZIP açılamadı';
            return null;
        }

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(AG_ROOT, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $cnt = 0;
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            $real = $file->getRealPath();
            $rel = ltrim(str_replace(AG_ROOT, '', $real), '/\\');
            $rel = str_replace('\\', '/', $rel);
            // storage/, uploads/, logs/ atla (büyük & runtime)
            if (str_starts_with($rel, 'storage/')) continue;
            if (str_starts_with($rel, 'uploads/')) continue;
            if (str_starts_with($rel, 'logs/')) continue;
            $zip->addFile($real, $rel);
            $cnt++;
        }
        $zip->close();
        if ($log !== null) $log[] = "✓ Yedek alındı: {$name} ({$cnt} dosya)";
        $this->rotateBackups();
        return $name;
    }

    public function rotateBackups(): void
    {
        $files = glob(self::BACKUP_DIR . '/*.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach (array_slice($files, self::MAX_BACKUPS) as $f) @unlink($f);
    }

    public function listBackups(): array
    {
        $files = glob(self::BACKUP_DIR . '/*.zip') ?: [];
        $out = [];
        foreach ($files as $f) {
            $out[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => filemtime($f),
            ];
        }
        usort($out, fn($a, $b) => $b['time'] - $a['time']);
        return $out;
    }

    public function rollback(string $backupName): array
    {
        $log = [];
        $bk = self::BACKUP_DIR . '/' . basename($backupName);
        if (!file_exists($bk)) return $this->fail('Yedek bulunamadı.', $log);

        // Önce mevcut hali yedekle
        $this->createBackup('pre_rollback', $log);

        $zip = new ZipArchive();
        if ($zip->open($bk) !== true) return $this->fail('Yedek ZIP açılamadı.', $log);

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name || str_ends_with($name, '/')) continue;
            if (self::isExcluded($name)) continue;
            $content = $zip->getFromIndex($i);
            if ($content === false) continue;
            $dst = AG_ROOT . '/' . $name;
            $dir = dirname($dst);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            if (@file_put_contents($dst, $content) !== false) $count++;
        }
        $zip->close();
        $log[] = "✓ Rollback: {$count} dosya geri yüklendi";
        return ['success' => true, 'message' => "Yedekten geri dönüldü: {$backupName}", 'log' => $log];
    }

    // ═══════════════════════════════════════════════
    // MIGRATION RUNNER
    // ═══════════════════════════════════════════════

    public function runMigrationsFromDir(string $dir, ?array &$log = null): void
    {
        $log ??= [];
        if (!is_dir($dir)) return;
        $files = glob($dir . '/v*.sql') ?: [];
        sort($files);
        foreach ($files as $f) $this->runMigration($f, $log);
    }

    public function runMigration(string $sqlFile, array &$log): void
    {
        $filename = basename($sqlFile);
        $pdo = DB::pdo();

        // ag_migrations tablosunu garanti et
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `ag_migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `filename` VARCHAR(255) NOT NULL,
                `checksum` VARCHAR(64) DEFAULT NULL,
                `status` ENUM('ok','error','skipped') DEFAULT 'ok',
                `notes` TEXT DEFAULT NULL,
                `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_filename` (`filename`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {}

        // Daha önce uygulanmış mı?
        try {
            $st = $pdo->prepare("SELECT status FROM ag_migrations WHERE filename = ? LIMIT 1");
            $st->execute([$filename]);
            if ($st->fetchColumn() === 'ok') {
                $log[] = "↷ Atlandı (zaten uygulanmış): $filename";
                return;
            }
        } catch (Throwable $e) {}

        $sql = (string)file_get_contents($sqlFile);
        $checksum = md5($sql);
        $stmts = self::splitSql($sql);
        $ok = 0; $errors = [];
        foreach ($stmts as $s) {
            if ($s === '') continue;
            try { $pdo->exec($s); $ok++; }
            catch (Throwable $e) { $errors[] = substr($e->getMessage(), 0, 200); }
        }
        $status = $errors ? 'error' : 'ok';
        try {
            $pdo->prepare("INSERT INTO ag_migrations (filename, checksum, status, notes)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), status=VALUES(status),
                    notes=VALUES(notes), applied_at=NOW()")
                ->execute([$filename, $checksum, $status, $errors ? implode(' | ', array_slice($errors, 0, 3)) : null]);
        } catch (Throwable $e) {}

        if ($status === 'ok') {
            $log[] = "✓ Migration: $filename ({$ok} sorgu)";
        } else {
            $log[] = "⚠ HATA: $filename — " . count($errors) . ' hata, ' . $ok . ' sorgu çalıştı';
            foreach (array_slice($errors, 0, 3) as $e) $log[] = "   ↳ $e";
        }
    }

    /** Akıllı SQL splitter — string-içi `;` karakterlerini, `''` escape'leri ve yorumları korur. */
    public static function splitSql(string $sql): array
    {
        $stmts = [];
        $cur = '';
        $inStr = false;
        $strCh = '';
        $len = strlen($sql);
        $i = 0;
        while ($i < $len) {
            $ch = $sql[$i];
            if ($inStr) {
                if ($ch === '\\' && $i + 1 < $len) { $cur .= $ch . $sql[$i+1]; $i += 2; continue; }
                if ($ch === $strCh) {
                    if ($i + 1 < $len && $sql[$i+1] === $strCh) { $cur .= $ch . $sql[$i+1]; $i += 2; continue; }
                    $inStr = false;
                }
                $cur .= $ch; $i++; continue;
            }
            if ($ch === '-' && $i + 1 < $len && $sql[$i+1] === '-') { while ($i < $len && $sql[$i] !== "\n") $i++; continue; }
            if ($ch === '/' && $i + 1 < $len && $sql[$i+1] === '*') { $i += 2; while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i+1] === '/')) $i++; $i += 2; continue; }
            if ($ch === "'" || $ch === '"' || $ch === '`') { $inStr = true; $strCh = $ch; $cur .= $ch; $i++; continue; }
            if ($ch === ';') { $s = trim($cur); if ($s !== '') $stmts[] = $s; $cur = ''; $i++; continue; }
            $cur .= $ch; $i++;
        }
        $s = trim($cur);
        if ($s !== '') $stmts[] = $s;
        return $stmts;
    }

    // ═══════════════════════════════════════════════
    // VERSİYON YAZIM
    // ═══════════════════════════════════════════════

    public function writeVersionFile(string $version, ?array &$log = null): void
    {
        $log ??= [];
        $content = "<?php\n// Otomatik üretildi — Updater\ndefine('AG_VERSION_INSTALLED', '{$version}');\n";
        if (@file_put_contents(self::VERSION_FILE, $content) !== false) {
            $log[] = "✓ version.php yazıldı: v{$version}";
        }
        try {
            DB::exec("UPDATE ag_settings SET setting_value = ? WHERE setting_key = 'current_version'", [$version]);
        } catch (Throwable $e) {}
    }

    public function recordVersion(string $version, string $name, bool $success = true): void
    {
        try {
            DB::exec("INSERT IGNORE INTO ag_versions (version, release_name, status, migration_executed)
                      VALUES (?, ?, ?, 1)", [$version, $name, $success ? 'success' : 'failed']);
        } catch (Throwable $e) {}
    }

    // ═══════════════════════════════════════════════
    // YARDIMCI
    // ═══════════════════════════════════════════════

    private function httpRequest(string $url, array $headers = [], int $timeout = 30): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => array_merge(['User-Agent: AksoyGroup-Updater/' . $this->current], $headers),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['code' => $code, 'body' => $body ?: ''];
        }
        $ctx = stream_context_create(['http' => [
            'header'  => implode("\r\n", array_merge(['User-Agent: AksoyGroup-Updater'], $headers)),
            'timeout' => $timeout,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        return ['code' => $body !== false ? 200 : 0, 'body' => $body ?: ''];
    }

    private function fail(string $msg, array $log): array
    {
        return ['success' => false, 'message' => $msg, 'log' => $log];
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = "$dir/$f";
            is_dir($p) ? $this->rmdirRecursive($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    public function currentVersion(): string { return $this->current; }
}
