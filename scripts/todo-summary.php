#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Menghitung ringkasan progres dan daftar "siap dikerjakan" di TODO.md.
 *
 *   php scripts/todo-summary.php            tulis ulang blok otomatis di TODO.md
 *   php scripts/todo-summary.php --check    jangan menulis; exit 1 jika tidak sinkron atau ada pelanggaran
 *   php scripts/todo-summary.php path.md    pakai file lain
 *
 * Sumber data: baris tabel task (| T1.04 | ...) dan baris isu (| ISS-001 | ...).
 * Blok otomatis dibatasi penanda <!-- progress:start/end --> dan <!-- ready:start/end -->.
 */

$args = array_slice($argv, 1);
$check = in_array('--check', $args, true);
$paths = array_values(array_filter($args, fn ($a) => ! str_starts_with($a, '--')));
$file = $paths[0] ?? dirname(__DIR__).'/TODO.md';

if (! is_file($file)) {
    fwrite(STDERR, "File tidak ditemukan: $file\n");
    exit(2);
}

$text = file_get_contents($file);
// Bukan \R: tanpa flag /u, \R memotong di byte 0x85 yang ada di dalam emoji ✅.
$lines = preg_split('/\r\n|\n|\r/', $text);

const STATUSES = ['todo', 'doing', 'review', 'done', 'blocked'];
const MILESTONES = [
    'M0' => 'Discovery & Architecture',
    'M1' => 'Foundation & Master Data',
    'M2' => 'Inventory Ledger',
    'M3' => 'POS Integration',
    'M4' => 'Stock Count & Reconciliation',
    'M5' => 'Dashboard & Reports',
    'M6' => 'Hardening & Handover',
];

function cells(string $line): array
{
    $parts = explode('|', trim($line));
    array_shift($parts);
    array_pop($parts);

    return array_map('trim', $parts);
}

/** @return array<string,array<string,mixed>> */
function parseTasks(array $lines, array &$errors): array
{
    $tasks = [];
    foreach ($lines as $i => $line) {
        if (! preg_match('/^\|\s*(T\d\.\d{2})\s*\|/', $line)) {
            continue;
        }
        $c = cells($line);
        if (count($c) < 9) {
            $errors[] = 'Baris '.($i + 1).": kolom task kurang dari 9 ({$c[0]})";

            continue;
        }
        [$id, $title, $est, $actual, $crit, $dep, $status] = $c;
        if (! preg_match('/(todo|doing|review|done|blocked)/', $status, $m)) {
            $errors[] = "$id: status tidak dikenal '$status'";

            continue;
        }
        if (! preg_match('#^(\d+)\s*/\s*(\d+)$#', $crit, $cm)) {
            $errors[] = "$id: kolom Kriteria harus 'n/n', ditemukan '$crit'";

            continue;
        }
        $tasks[$id] = [
            'id' => $id,
            'ms' => 'M'.$id[1],
            'title' => $title,
            'est' => (int) $est,
            'actual' => is_numeric($actual) ? (float) $actual : 0.0,
            'met' => (int) $cm[1],
            'total' => (int) $cm[2],
            'dep' => $dep,
            'status' => $m[1],
            'line' => $i + 1,
        ];
    }

    return $tasks;
}

/** Dependensi task: id eksplisit, rentang "T2.05–T2.11", dan "M5 selesai". */
function dependencies(array $task, array $tasks): array
{
    $deps = [];
    $dep = $task['dep'];
    if (preg_match_all('/(T\d\.\d{2})\s*[–-]\s*(T\d\.\d{2})/u', $dep, $ranges, PREG_SET_ORDER)) {
        foreach ($ranges as $r) {
            foreach ($tasks as $t) {
                if (strcmp($t['id'], $r[1]) >= 0 && strcmp($t['id'], $r[2]) <= 0) {
                    $deps[] = $t['id'];
                }
            }
        }
        $dep = preg_replace('/(T\d\.\d{2})\s*[–-]\s*(T\d\.\d{2})/u', '', $dep);
    }
    if (preg_match_all('/\bT\d\.\d{2}\b/', $dep, $ids)) {
        array_push($deps, ...$ids[0]);
    }
    if (preg_match_all('/\b(M\d) selesai/', $dep, $ms)) {
        foreach ($ms[1] as $m) {
            foreach ($tasks as $t) {
                if ($t['ms'] === $m) {
                    $deps[] = $t['id'];
                }
            }
        }
    }

    return array_values(array_unique($deps));
}

function external(string $dep): string
{
    $x = preg_replace('/(T\d\.\d{2})(\s*[–-]\s*T\d\.\d{2})?/u', '', $dep);
    $x = preg_replace('/\bM\d selesai\b/', '', $x);
    $x = trim(preg_replace('/\s*,\s*/', ', ', $x), " ,");

    return $x === '' ? '' : $x;
}

function bar(float $ratio): string
{
    $filled = (int) round($ratio * 10);

    return str_repeat('▓', $filled).str_repeat('░', 10 - $filled).' '.(int) round($ratio * 100).'%';
}

$errors = [];
$warnings = [];

// Log model (§12) memakai ID task juga, jadi dipisahkan dari tabel task.
$taskLines = $lines;
$modelLog = [];
$inLog = false;
foreach ($lines as $i => $line) {
    if (str_contains($line, '<!-- modellog:start -->')) {
        $inLog = true;

        continue;
    }
    if (str_contains($line, '<!-- modellog:end -->')) {
        $inLog = false;

        continue;
    }
    if ($inLog) {
        $taskLines[$i] = '';
        if (preg_match('/^\|\s*(T\d\.\d{2})\s*\|/', $line)) {
            $modelLog[] = ['cells' => cells($line), 'line' => $i + 1];
        }
    }
}
$tasks = parseTasks($taskLines, $errors);

if ($tasks === []) {
    fwrite(STDERR, "Tidak ada baris task ditemukan di $file\n");
    exit(2);
}

// Aturan
$doing = 0;
foreach ($tasks as $t) {
    if ($t['status'] === 'doing') {
        $doing++;
    }
    if ($t['met'] > $t['total']) {
        $errors[] = "{$t['id']}: Kriteria {$t['met']}/{$t['total']} melebihi total";
    }
    if ($t['status'] === 'done' && $t['met'] !== $t['total']) {
        $errors[] = "{$t['id']}: status done tetapi Kriteria {$t['met']}/{$t['total']}";
    }
    if (in_array($t['status'], ['doing', 'review', 'done'], true)) {
        foreach (dependencies($t, $tasks) as $d) {
            if (isset($tasks[$d]) && $tasks[$d]['status'] !== 'done') {
                $errors[] = "{$t['id']}: berstatus {$t['status']} padahal dependensi $d belum done ({$tasks[$d]['status']})";
            }
        }
    }
    if ($t['status'] === 'blocked' && ! preg_match('/ISS-\d+/', implode('|', cells($lines[$t['line'] - 1])))) {
        $errors[] = "{$t['id']}: berstatus blocked tanpa ID isu (ISS-nnn) di baris";
    }
}
if ($doing > 3) {
    $warnings[] = "Ada $doing task berstatus doing (batas yang disarankan: 3)";
}

// Isu
$issues = ['open' => 0, 'high' => 0, 'waiting' => 0];
foreach ($lines as $line) {
    if (! preg_match('/^\|\s*ISS-\d+\s*\|/', $line)) {
        continue;
    }
    $c = cells($line);
    if (count($c) < 10) {
        continue;
    }
    if ($c[4] !== 'closed') {
        $issues['open']++;
        if ($c[3] === 'high') {
            $issues['high']++;
        }
        if ($c[4] === 'waiting') {
            $issues['waiting']++;
        }
    }
}

// Ringkasan
$by = [];
foreach (MILESTONES as $m => $name) {
    $by[$m] = ['n' => 0, 'done' => 0, 'est' => 0, 'estDone' => 0, 'actual' => 0.0, 'started' => 0, 'crit' => 0, 'critMet' => 0];
}
$count = array_fill_keys(STATUSES, 0);
foreach ($tasks as $t) {
    $b = &$by[$t['ms']];
    $b['n']++;
    $b['est'] += $t['est'];
    $b['actual'] += $t['actual'];
    $b['crit'] += $t['total'];
    $b['critMet'] += $t['met'];
    if ($t['status'] === 'done') {
        $b['done']++;
        $b['estDone'] += $t['est'];
    }
    if ($t['status'] !== 'todo') {
        $b['started']++;
    }
    $count[$t['status']]++;
    unset($b);
}

$tot = ['n' => 0, 'done' => 0, 'est' => 0, 'estDone' => 0, 'actual' => 0.0, 'crit' => 0, 'critMet' => 0];
$rows = [];
foreach ($by as $m => $b) {
    foreach (['n', 'done', 'est', 'estDone', 'actual', 'crit', 'critMet'] as $k) {
        $tot[$k] += $b[$k];
    }
    $state = $b['done'] === $b['n'] ? '✅ task selesai' : ($b['started'] > 0 ? '🔄 berjalan' : '⬜ belum mulai');
    $rows[] = sprintf(
        '| %s — %s | %d/%d | %d/%d | %s | %s | %s |',
        $m, MILESTONES[$m], $b['done'], $b['n'], $b['estDone'], $b['est'],
        $b['actual'] > 0 ? rtrim(rtrim(number_format($b['actual'], 1, '.', ''), '0'), '.') : '—',
        bar($b['est'] > 0 ? $b['estDone'] / $b['est'] : 0), $state
    );
}
$rows[] = sprintf(
    '| **Total** | **%d/%d** | **%d/%d** | **%s** | **%s** | |',
    $tot['done'], $tot['n'], $tot['estDone'], $tot['est'],
    $tot['actual'] > 0 ? rtrim(rtrim(number_format($tot['actual'], 1, '.', ''), '0'), '.') : '—',
    bar($tot['est'] > 0 ? $tot['estDone'] / $tot['est'] : 0)
);

// Metrik log model
const TIER_ORDER = ['easy' => 1, 'medium' => 2, 'hard' => 3, 'hard+' => 4];
$logLine = null;
if ($modelLog !== []) {
    $n = 0;
    $passed = 0;
    $escalated = 0;
    $attempts = 0;
    $human = 0;
    foreach ($modelLog as $row) {
        $c = $row['cells'];
        if (count($c) < 8) {
            $errors[] = 'Log model baris '.$row['line'].': kolom kurang dari 8';

            continue;
        }
        [$tid, $risk, $from, $to, $tries, $ok, $man] = $c;
        if (! isset($tasks[$tid])) {
            $errors[] = "Log model: task $tid tidak ada di tabel task";

            continue;
        }
        if (! isset(TIER_ORDER[$from], TIER_ORDER[$to]) || ! preg_match('/^R[1-4]$/', $risk)
            || ! ctype_digit($tries) || ! in_array($ok, ['ya', 'tidak'], true) || ! in_array($man, ['ya', 'tidak'], true)) {
            $errors[] = "Log model $tid: nilai tidak valid (Risiko R1–R4; Awal/Akhir easy|medium|hard|hard+; Percobaan angka; Lulus/Manusia ya|tidak)";

            continue;
        }
        $n++;
        $passed += $ok === 'ya' ? 1 : 0;
        $escalated += $from !== $to ? 1 : 0;
        $attempts += (int) $tries;
        $human += $man === 'ya' ? 1 : 0;
    }
    if ($n > 0) {
        $logLine = sprintf(
            'Log model (%d task): verified success %d%% · eskalasi %d%% · rata-rata percobaan %s · intervensi manusia %d%%',
            $n, round($passed / $n * 100), round($escalated / $n * 100), number_format($attempts / $n, 1, '.', ''), round($human / $n * 100)
        );
    }
}

$progress = [
    '| Milestone | Task selesai | Jam selesai / estimasi | Jam aktual | Kemajuan (berbobot jam) | Status |',
    '| --------- | ------------ | ---------------------- | ---------- | ----------------------- | ------ |',
    ...$rows,
    '',
    sprintf(
        'Status task: ⬜ %d todo · 🔄 %d doing · 👀 %d review · ✅ %d done · ⛔ %d blocked  |  Kriteria terpenuhi: %d/%d  |  Isu terbuka: %d (high: %d, menunggu pihak lain: %d)',
        $count['todo'], $count['doing'], $count['review'], $count['done'], $count['blocked'],
        $tot['critMet'], $tot['crit'], $issues['open'], $issues['high'], $issues['waiting']
    ),
    ...($logLine !== null ? ['', $logLine] : []),
];

// Siap dikerjakan
$ready = [];
foreach ($tasks as $t) {
    if ($t['status'] !== 'todo') {
        continue;
    }
    $ok = true;
    foreach (dependencies($t, $tasks) as $d) {
        if (isset($tasks[$d]) && $tasks[$d]['status'] !== 'done') {
            $ok = false;
            break;
        }
    }
    if (! $ok) {
        continue;
    }
    $ext = external($t['dep']);
    $ready[] = sprintf('- **%s** %s (%d jam)%s', $t['id'], $t['title'], $t['est'], $ext !== '' ? " · prasyarat eksternal: $ext" : '');
}
$readyBlock = $ready === [] ? ['_Tidak ada task yang siap (semua selesai atau terhalang dependensi)._'] : $ready;

function replaceBlock(string $text, string $name, array $content): string
{
    $re = '/(<!-- '.$name.':start -->)(.*?)(<!-- '.$name.':end -->)/s';
    if (! preg_match($re, $text)) {
        fwrite(STDERR, "Penanda <!-- $name:start/end --> tidak ditemukan\n");
        exit(2);
    }

    return preg_replace_callback($re, fn ($m) => $m[1]."\n".implode("\n", $content)."\n".$m[3], $text, 1);
}

$new = replaceBlock($text, 'progress', $progress);
$new = replaceBlock($new, 'ready', $readyBlock);
$inSync = $new === $text;

foreach ($warnings as $w) {
    fwrite(STDERR, "PERINGATAN: $w\n");
}
foreach ($errors as $e) {
    fwrite(STDERR, "ERROR: $e\n");
}

if ($check) {
    if (! $inSync) {
        fwrite(STDERR, "TODO.md tidak sinkron: jalankan `php scripts/todo-summary.php`.\n");
    }
    exit(($inSync && $errors === []) ? 0 : 1);
}

if (! $inSync) {
    file_put_contents($file, $new);
}
printf(
    "%s: %d/%d task done, %d/%d jam, %d isu terbuka%s\n",
    basename($file), $tot['done'], $tot['n'], $tot['estDone'], $tot['est'], $issues['open'],
    $errors === [] ? '' : ' — '.count($errors).' pelanggaran (lihat di atas)'
);
exit($errors === [] ? 0 : 1);
