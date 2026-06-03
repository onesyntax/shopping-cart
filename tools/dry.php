<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DRY / knowledge-duplication reporter
|--------------------------------------------------------------------------
|
| A self-contained, dependency-free copy-paste detector (a mini phpcpd).
| It tokenises every PHP file under the given paths, normalises away the
| noise that hides duplication (whitespace, comments, and — so rename-only
| clones still match — variable names), then finds the longest blocks of
| identical token streams that appear in more than one place.
|
| Token-level clones are only a *proxy* for DRY: they flag where the same
| knowledge is written twice. A flagged block is a candidate for extraction
| (a method, a template, a value object); a clean run means no block of
| MIN_TOKENS+ tokens is repeated. Real knowledge duplication that isn't
| textually identical (a policy spread across an enum and four classes) still
| needs human eyes — this tool catches the mechanical half.
|
| Blocks at or above MIN_TOKENS are reported and make the command exit
| non-zero once more than ALLOWED clones remain, so `composer dry` can gate CI.
|
| Usage: php tools/dry.php [path ...] [--min=70] [--allowed=0]
|
*/

const MIN_TOKENS = 70;   // shortest duplicated block worth reporting
const ALLOWED = 0;       // clones tolerated before the command fails CI

$paths = [];
$minTokens = MIN_TOKENS;
$allowed = ALLOWED;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--min=(\d+)$/', $arg, $m)) {
        $minTokens = (int) $m[1];
    } elseif (preg_match('/^--allowed=(\d+)$/', $arg, $m)) {
        $allowed = (int) $m[1];
    } else {
        $paths[] = $arg;
    }
}

if ($paths === []) {
    $paths = [__DIR__.'/../app'];
}

/** Collect every .php file under the given paths. */
$files = [];
foreach ($paths as $path) {
    if (is_file($path)) {
        $files[] = $path;

        continue;
    }
    if (! is_dir($path)) {
        fwrite(STDERR, "Path not found: {$path}\n");
        exit(2);
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}
sort($files);

if ($files === []) {
    fwrite(STDERR, "No PHP files found.\n");
    exit(2);
}

$root = realpath(__DIR__.'/..').'/';
$short = static fn (string $path): string => str_replace($root, '', (string) realpath($path));

/*
| Build one global token stream across all files. Each entry is the
| normalised token text plus its origin (file + line). A unique boundary
| sentinel is inserted between files so no duplicated block straddles two
| files.
*/
$skip = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG];
$tokensText = [];   // normalised token text
$tokensFile = [];   // origin file index
$tokensLine = [];   // origin line
$boundary = [];     // true when this slot is a between-files sentinel

foreach ($files as $fileIndex => $file) {
    $code = file_get_contents($file);
    if ($code === false) {
        continue;
    }

    $line = 1;
    foreach (token_get_all($code) as $token) {
        if (is_array($token)) {
            [$id, $text, $startLine] = [$token[0], $token[1], $token[2]];
            $line = $startLine;
            if (in_array($id, $skip, true)) {
                $line += substr_count($text, "\n");

                continue;
            }
            // Normalise variable names so clones that differ only by naming match.
            $normal = $id === T_VARIABLE ? '$V' : $text;
        } else {
            $normal = $token;       // single-char punctuation: '{', ';', '(' ...
            $startLine = $line;
        }

        $tokensText[] = $normal;
        $tokensFile[] = $fileIndex;
        $tokensLine[] = $startLine;
        $boundary[] = false;

        $line = $startLine + substr_count(is_array($token) ? $token[1] : $token, "\n");
    }

    // Sentinel between files — unique value, never equal to a real token.
    $tokensText[] = "\0BOUNDARY\0{$fileIndex}";
    $tokensFile[] = $fileIndex;
    $tokensLine[] = $line;
    $boundary[] = true;
}

$n = count($tokensText);

/*
| Bucket every window of MIN_TOKENS tokens by hash. Windows containing a
| file boundary are skipped so clones never cross files.
*/
$buckets = [];
$validStart = array_fill(0, $n, true);
for ($i = 0; $i < $n; $i++) {
    if ($boundary[$i]) {
        for ($k = max(0, $i - $minTokens + 1); $k <= $i; $k++) {
            $validStart[$k] = false;
        }
    }
}

for ($i = 0; $i + $minTokens <= $n; $i++) {
    if (! $validStart[$i]) {
        continue;
    }
    $hash = md5(implode("\x1f", array_slice($tokensText, $i, $minTokens)));
    $buckets[$hash][] = $i;
}

/*
| For each bucket, pair up window starts that begin a *maximal* clone (their
| preceding token differs, so we're at the head of the block, not inside a
| longer one). Extend each match forward to its full length.
*/
$equal = static function (int $a, int $b) use ($tokensText, $boundary): bool {
    return ! $boundary[$a] && ! $boundary[$b] && $tokensText[$a] === $tokensText[$b];
};

$clones = [];
$seen = [];
foreach ($buckets as $starts) {
    if (count($starts) < 2) {
        continue;
    }
    foreach ($starts as $ai => $a) {
        foreach (array_slice($starts, $ai + 1) as $b) {
            if ($b - $a < $minTokens) {
                continue;   // overlapping window of the same run
            }
            // Skip if this isn't the head of the clone (predecessors match).
            if ($a > 0 && $b > 0 && $equal($a - 1, $b - 1)) {
                continue;
            }
            // Extend forward past the window to the full clone length.
            $len = $minTokens;
            while ($a + $len < $n && $b + $len < $n && $equal($a + $len, $b + $len)) {
                $len++;
            }
            $key = $a.':'.$b;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $clones[] = [
                'tokens' => $len,
                'aFile' => $short($files[$tokensFile[$a]]),
                'aFrom' => $tokensLine[$a],
                'aTo' => $tokensLine[$a + $len - 1],
                'bFile' => $short($files[$tokensFile[$b]]),
                'bFrom' => $tokensLine[$b],
                'bTo' => $tokensLine[$b + $len - 1],
            ];
        }
    }
}

usort($clones, static fn ($x, $y) => $y['tokens'] <=> $x['tokens']);

echo "\nDuplicated blocks (>= {$minTokens} tokens, variable names normalised)\n";
echo str_repeat('=', 92)."\n";
if ($clones === []) {
    echo "None. \xE2\x9C\x94 No block of {$minTokens}+ tokens is repeated across ".count($files)." files.\n\n";
    exit(0);
}

printf("%6s  %-40s  %-40s\n", 'TOKENS', 'BLOCK A', 'BLOCK B');
echo str_repeat('-', 92)."\n";
$duplicatedTokens = 0;
foreach ($clones as $c) {
    $duplicatedTokens += $c['tokens'];
    printf(
        "%6d  %-40s  %-40s\n",
        $c['tokens'],
        "{$c['aFile']}:{$c['aFrom']}-{$c['aTo']}",
        "{$c['bFile']}:{$c['bFrom']}-{$c['bTo']}",
    );
}

$over = count($clones);
echo "\n{$over} duplicated block(s) across ".count($files).' files; '
    ."{$duplicatedTokens} tokens repeated. Allowed: {$allowed}.\n";

exit($over > $allowed ? 1 : 0);
