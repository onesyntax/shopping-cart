<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CRAP index reporter
|--------------------------------------------------------------------------
|
| Reads the crap4j report produced by `vendor/bin/pest --coverage-crap4j`
| and prints the CRAP index (Change Risk Anti-Patterns) per file and per
| method. CRAP = CC^2 * (1 - coverage)^3 + CC, so at full coverage it equals
| the cyclomatic complexity. Methods at or above THRESHOLD are flagged and
| make the command exit non-zero, so `composer crap` can gate CI.
|
| Usage: php tools/crap.php [path/to/crap4j.xml] [threshold]
|
*/

const THRESHOLD = 6.0;

$reportPath = $argv[1] ?? __DIR__.'/../storage/crap4j.xml';
$threshold = isset($argv[2]) ? (float) $argv[2] : THRESHOLD;

if (! is_file($reportPath)) {
    fwrite(STDERR, "CRAP report not found at {$reportPath}. Run `composer crap` to generate it.\n");
    exit(2);
}

$xml = simplexml_load_file($reportPath);
if ($xml === false) {
    fwrite(STDERR, "Could not parse CRAP report at {$reportPath}.\n");
    exit(2);
}

$methods = [];
$files = [];
foreach ($xml->methods->method as $m) {
    $class = (string) $m->className;
    $crap = (float) $m->crap;
    $methods[] = [
        'class' => $class,
        'method' => (string) $m->methodName,
        'cc' => (int) $m->complexity,
        'cov' => (float) $m->coverage,
        'crap' => $crap,
    ];

    // Aggregate to a per-file view; a file is as risky as its worst method.
    if (! isset($files[$class]) || $crap > $files[$class]['crap']) {
        $files[$class] = [
            'crap' => $crap,
            'cc' => (int) $m->complexity,
            'cov' => (float) $m->coverage,
            'method' => (string) $m->methodName,
        ];
    }
}

if ($methods === []) {
    fwrite(STDERR, "No methods found in CRAP report.\n");
    exit(2);
}

uasort($files, fn ($a, $b) => $b['crap'] <=> $a['crap']);
usort($methods, fn ($a, $b) => $b['crap'] <=> $a['crap']);

$short = fn (string $fqcn): string => str_replace('App\\', '', $fqcn);

echo "\nCRAP index by file (worst method per file)\n";
echo str_repeat('=', 88)."\n";
printf("%-58s %-18s %4s %6s %7s\n", 'FILE (class)', 'WORST METHOD', 'CC', 'COV%', 'CRAP');
echo str_repeat('-', 88)."\n";
foreach ($files as $class => $f) {
    $flag = $f['crap'] >= $threshold ? '  <-- over' : '';
    printf("%-58s %-18s %4d %5.1f %7.2f%s\n", $short($class), $f['method'], $f['cc'], $f['cov'], $f['crap'], $flag);
}

$over = array_values(array_filter($methods, fn ($m) => $m['crap'] >= $threshold));

echo "\nMethods at or above threshold (CRAP >= {$threshold})\n";
echo str_repeat('=', 88)."\n";
if ($over === []) {
    echo "None. \xE2\x9C\x94 Every method is below {$threshold}.\n";
} else {
    printf("%-62s %4s %7s %7s\n", 'METHOD', 'CC', 'COV%', 'CRAP');
    echo str_repeat('-', 88)."\n";
    foreach ($over as $m) {
        printf("%-62s %4d %6.1f %7.2f\n", $short($m['class']).'::'.$m['method'], $m['cc'], $m['cov'], $m['crap']);
    }
}

$maxCrap = max(array_column($methods, 'crap'));
echo "\n".count($methods).' methods across '.count($files).' files. '
    .'Max CRAP = '.rtrim(rtrim(number_format($maxCrap, 2), '0'), '.').'. '
    .count($over)." over threshold {$threshold}.\n";

exit($over === [] ? 0 : 1);
