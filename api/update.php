<?php
/**
 * AKSOY GROUP — Update Center AJAX Endpoint
 * ERP-style — JSON yanıtlar, AJAX akış, sayfa yenilenmiyor.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
@set_time_limit(0);
@ini_set('memory_limit', '512M');
@ignore_user_abort(true);

Auth::requireRole('superadmin');
header('Content-Type: application/json; charset=utf-8');

// CSRF — sadece POST endpoint'leri için
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    try { CSRF::require(); }
    catch (Throwable $e) { jsonResponse(['ok' => false, 'error' => 'CSRF doğrulaması başarısız.'], 403); }
}

$updater = new Updater();

try {
    switch ($action) {
        // ───────────────────────────────────────────
        // STATUS — kontrol + diff + dosya listesi
        // ───────────────────────────────────────────
        case 'status': {
            if (!$updater->token()) {
                jsonResponse(['ok' => false, 'error' => 'GitHub token tanımlı değil. Ayarlar sekmesine gidin.']);
            }
            $diff = $updater->compareFiles();
            $remoteVer = $updater->remoteVersion();
            $localVer = $updater->currentVersion();

            // Frontend için dosya map'i
            $files = [];
            foreach ($diff['unchanged'] as $f) $files[$f['path']] = ['status' => 'ok',      'sha' => $f['sha'], 'size' => $f['size']];
            foreach ($diff['changed']   as $f) $files[$f['path']] = ['status' => 'diff',    'sha' => $f['sha'], 'size' => $f['size']];
            foreach ($diff['new']       as $f) $files[$f['path']] = ['status' => 'missing', 'sha' => $f['sha'], 'size' => $f['size']];
            ksort($files);

            $stats = [
                'ok'      => count($diff['unchanged']),
                'diff'    => count($diff['changed']),
                'missing' => count($diff['new']),
            ];

            jsonResponse([
                'ok'           => true,
                'local_ver'    => $localVer,
                'remote_ver'   => $remoteVer,
                'stats'        => $stats,
                'total'        => $diff['total'],
                'needs_update' => $stats['diff'] + $stats['missing'] > 0,
                'files'        => $files,
            ]);
        }

        // ───────────────────────────────────────────
        // SYNC — Smart Update (sadece değişen)
        // ───────────────────────────────────────────
        case 'sync': {
            $start = microtime(true);
            $r = $updater->smartSync();
            $duration = round((microtime(true) - $start) * 1000);
            Audit::log('update_smart_sync', 'system', null, null, ['ms' => $duration], 'warning');
            jsonResponse([
                'ok'      => $r['success'],
                'error'   => $r['success'] ? null : $r['message'],
                'log'     => $r['log'] ?? [],
                'updated' => count(array_filter($r['log'] ?? [], fn($l) => str_starts_with($l, '✓ '))),
                'version' => $updater->remoteVersion(),
                'ms'      => $duration,
            ]);
        }

        // ───────────────────────────────────────────
        // FORCE — Force Update (tüm dosyalar)
        // ───────────────────────────────────────────
        case 'force': {
            $start = microtime(true);
            $r = $updater->forceSync();
            $duration = round((microtime(true) - $start) * 1000);
            Audit::log('update_force_sync', 'system', null, null, ['ms' => $duration], 'critical');
            jsonResponse([
                'ok'      => $r['success'],
                'error'   => $r['success'] ? null : $r['message'],
                'log'     => $r['log'] ?? [],
                'updated' => count(array_filter($r['log'] ?? [], fn($l) => str_starts_with($l, '✓ '))),
                'version' => $updater->remoteVersion(),
                'ms'      => $duration,
            ]);
        }

        // ───────────────────────────────────────────
        // UPDATE_FILE — tek dosya
        // ───────────────────────────────────────────
        case 'update_file': {
            $file = trim($_POST['file'] ?? $_GET['file'] ?? '');
            if (!$file) jsonResponse(['ok' => false, 'error' => 'Dosya parametresi yok.']);
            $r = $updater->syncFile($file);
            jsonResponse([
                'ok'    => $r['ok'],
                'error' => $r['ok'] ? null : $r['msg'],
                'msg'   => $r['msg'],
                'bytes' => $r['bytes'] ?? 0,
            ]);
        }

        // ───────────────────────────────────────────
        // MIGRATE — DB migration çalıştır
        // ───────────────────────────────────────────
        case 'migrate': {
            $log = [];
            $updater->runMigrationsFromDir(AG_ROOT . '/migrations', $log);
            $migrations = DB::all("SELECT * FROM ag_migrations ORDER BY id DESC LIMIT 50");
            jsonResponse(['ok' => true, 'log' => $log, 'migrations' => $migrations]);
        }

        // ───────────────────────────────────────────
        // BACKUP — yedek al
        // ───────────────────────────────────────────
        case 'backup': {
            $name = $updater->createBackup('manual');
            if (!$name) jsonResponse(['ok' => false, 'error' => 'Yedek alınamadı.']);
            Audit::log('backup_create', 'system', null, null, ['file' => $name]);
            jsonResponse(['ok' => true, 'name' => $name, 'backups' => $updater->listBackups()]);
        }

        // ───────────────────────────────────────────
        // BACKUPS — listele
        // ───────────────────────────────────────────
        case 'backups': {
            jsonResponse(['ok' => true, 'backups' => $updater->listBackups(), 'max' => Updater::MAX_BACKUPS]);
        }

        // ───────────────────────────────────────────
        // ROLLBACK — yedekten geri dön
        // ───────────────────────────────────────────
        case 'rollback': {
            $bk = trim($_POST['backup'] ?? '');
            if (!$bk) jsonResponse(['ok' => false, 'error' => 'Yedek dosya adı boş.']);
            $r = $updater->rollback($bk);
            Audit::log('rollback', 'system', null, null, ['backup' => $bk], 'critical');
            jsonResponse(['ok' => $r['success'], 'error' => $r['success'] ? null : $r['message'], 'log' => $r['log'] ?? []]);
        }

        // ───────────────────────────────────────────
        // TOKEN — kaydet
        // ───────────────────────────────────────────
        case 'save_token': {
            $tok = trim($_POST['token'] ?? '');
            if (!$tok) jsonResponse(['ok' => false, 'error' => 'Token boş olamaz.']);
            // Esnek regex: ghp_/gho_/ghs_/ghr_/github_pat_ + alfanum/altçizgi/tire, en az 20 char
            if (!preg_match('/^(ghp_|gho_|ghs_|ghr_|github_pat_)[A-Za-z0-9_\-]{20,}$/', $tok)) {
                jsonResponse([
                    'ok' => false,
                    'error' => 'Token formatı geçersiz. ghp_… / gho_… / github_pat_… ile başlamalı, en az 24 karakter olmalı. Girdiğiniz: ' . strlen($tok) . ' karakter, ön ek: ' . substr($tok, 0, 4)
                ]);
            }
            $r = $updater->saveToken($tok);
            if (!$r['ok']) {
                jsonResponse([
                    'ok'    => false,
                    'error' => 'Kaydedilemedi: ' . ($r['error'] ?? 'bilinmeyen hata'),
                    'where' => $r['where'],
                    'file'  => $r['file'],
                    'db'    => $r['db'],
                ]);
            }
            Audit::log('update_token_set', 'system', null, null, ['where' => $r['where'], 'len' => $r['len']]);
            $stored = $updater->token();
            jsonResponse([
                'ok'         => true,
                'where'      => $r['where'],   // 'file', 'db', veya 'file+db'
                'file'       => $r['file'],
                'db'         => $r['db'],
                'token_tail' => substr($stored, -4),
                'len'        => $r['len'],
            ]);
        }

        // ───────────────────────────────────────────
        // DIAGNOSE — sistem teşhisi
        // ───────────────────────────────────────────
        case 'diagnose': {
            $tokenFile = Updater::TOKEN_FILE;
            $tokenDir = dirname($tokenFile);
            $checks = [
                'php_version'        => PHP_VERSION,
                'php_ok'             => version_compare(PHP_VERSION, '8.3', '>='),
                'curl'               => function_exists('curl_init'),
                'zip'                => class_exists('ZipArchive'),
                'mb_string'          => function_exists('mb_substr'),
                'allow_url_fopen'    => (bool)ini_get('allow_url_fopen'),
                'token_file_path'    => $tokenFile,
                'token_dir_exists'   => is_dir($tokenDir),
                'token_dir_writable' => is_writable($tokenDir),
                'token_file_exists'  => file_exists($tokenFile),
                'token_file_writable'=> file_exists($tokenFile) ? is_writable($tokenFile) : null,
                'token_in_db'        => false,
                'token_in_file'      => false,
                'storage_writable'   => is_writable(AG_ROOT . '/storage'),
                'db_connect'         => false,
                'github_owner'       => AG_GITHUB_OWNER,
                'github_repo'        => AG_GITHUB_REPO,
                'github_branch'      => AG_GITHUB_BRANCH,
                'local_version'      => Updater::localVersion(),
            ];
            try {
                $checks['db_connect'] = (bool)DB::scalar('SELECT 1');
                $tokInDb = DB::scalar("SELECT setting_value FROM ag_settings WHERE setting_key='github_token'");
                $checks['token_in_db'] = $tokInDb ? '••••' . substr((string)$tokInDb, -4) . ' (' . strlen((string)$tokInDb) . ' kar)' : false;
            } catch (Throwable $e) {
                $checks['db_error'] = $e->getMessage();
            }
            if (file_exists($tokenFile)) {
                $tokInFile = trim((string)@file_get_contents($tokenFile));
                $checks['token_in_file'] = $tokInFile ? '••••' . substr($tokInFile, -4) . ' (' . strlen($tokInFile) . ' kar)' : false;
            }
            jsonResponse(['ok' => true, 'checks' => $checks]);
        }

        // ───────────────────────────────────────────
        // COMMITS — son 20 commit
        // ───────────────────────────────────────────
        case 'commits': {
            $commits = $updater->ghAPI('/commits?per_page=20&sha=' . AG_GITHUB_BRANCH);
            if (!$commits) jsonResponse(['ok' => false, 'error' => 'Commits alınamadı.']);
            $simplified = array_map(fn($c) => [
                'sha'     => substr((string)($c['sha'] ?? ''), 0, 7),
                'message' => (string)($c['commit']['message'] ?? ''),
                'author'  => (string)($c['commit']['author']['name'] ?? '?'),
                'date'    => (string)($c['commit']['author']['date'] ?? ''),
                'url'     => (string)($c['html_url'] ?? ''),
            ], array_slice((array)$commits, 0, 20));
            jsonResponse(['ok' => true, 'commits' => $simplified]);
        }

        // ───────────────────────────────────────────
        // RELEASES — son 10 release
        // ───────────────────────────────────────────
        case 'releases': {
            $rel = $updater->ghAPI('/releases?per_page=10');
            if (!$rel) jsonResponse(['ok' => false, 'error' => 'Releases alınamadı.']);
            jsonResponse(['ok' => true, 'releases' => array_map(fn($r) => [
                'tag'        => $r['tag_name'] ?? '',
                'name'       => $r['name'] ?? '',
                'body'       => $r['body'] ?? '',
                'date'       => $r['published_at'] ?? '',
                'asset_size' => array_sum(array_column($r['assets'] ?? [], 'size')),
                'has_asset'  => !empty($r['assets']),
            ], (array)$rel)]);
        }

        // ───────────────────────────────────────────
        // INSTALL — release ZIP indir + uygula
        // ───────────────────────────────────────────
        case 'install': {
            $version = trim($_POST['version'] ?? '');
            if (!$version) jsonResponse(['ok' => false, 'error' => 'Sürüm parametresi yok.']);
            $start = microtime(true);
            $r = $updater->install($version);
            Audit::log('update_install', 'system', null, null, ['version' => $version], 'critical');
            jsonResponse([
                'ok'      => $r['success'],
                'error'   => $r['success'] ? null : $r['message'],
                'log'     => $r['log'] ?? [],
                'version' => $r['new_version'] ?? $version,
                'ms'      => round((microtime(true) - $start) * 1000),
            ]);
        }

        // ───────────────────────────────────────────
        // UPLOAD_ZIP — manuel ZIP yükleme
        // ───────────────────────────────────────────
        case 'upload_zip': {
            if (empty($_FILES['zip']['tmp_name'])) jsonResponse(['ok' => false, 'error' => 'ZIP dosyası seçilmedi.']);
            $r = $updater->updateFromZip($_FILES['zip']['tmp_name']);
            Audit::log('update_zip_upload', 'system', null, null, ['file' => $_FILES['zip']['name']], 'critical');
            jsonResponse([
                'ok'      => $r['success'],
                'error'   => $r['success'] ? null : $r['message'],
                'log'     => $r['log'] ?? [],
                'version' => $r['new_version'] ?? null,
            ]);
        }

        // ───────────────────────────────────────────
        // VERSION — kısa versiyon bilgisi
        // ───────────────────────────────────────────
        case 'version': {
            jsonResponse([
                'ok'         => true,
                'local'      => $updater->currentVersion(),
                'remote'     => $updater->remoteVersion(),
                'token_set'  => !empty($updater->token()),
                'php'        => PHP_VERSION,
                'mariadb'    => (string)DB::scalar('SELECT VERSION()'),
                'curl'       => function_exists('curl_init'),
                'zip'        => class_exists('ZipArchive'),
            ]);
        }

        default:
            jsonResponse(['ok' => false, 'error' => 'Bilinmeyen aksiyon: ' . h($action)], 400);
    }
} catch (Throwable $e) {
    error_log('[update.php] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    jsonResponse(['ok' => false, 'error' => 'Sunucu hatası: ' . $e->getMessage()], 500);
}
