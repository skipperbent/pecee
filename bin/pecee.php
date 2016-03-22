<?php

require_once 'config/bootstrap.php';

global $argv;

// TODO: use Cli tool
// TODO: check if bootstrap.php exists.
// TODO: check if phinx-config exists.

function getClassInfo($file) {
    $contents = file_get_contents($file);
    preg_match_all('/namespace ([^;\n]+)|class ([^\s{]+)/is', $contents, $matches);

    return [
        'class' => $matches[2][1],
        'namespace' => $matches[1][0],
        'full' => $matches[1][0] . '\\' . $matches[2][1]
    ];
}

function changeNamespace($newNamespace, $file) {
    $contents = file_get_contents($file);
    $info = getClassInfo($file);
    str_ireplace($info['namespace'], $newNamespace, $contents);
    file_put_contents($file, $contents);
}

switch(strtolower($argv[1])) {
    case 'migrations':
        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = 'config/phinx-config.php';

        array_shift($argv);
        array_shift($argv);

        // Run Phinx
        exec($phinx . ' ' . $cmd . ' '. join(' ', $argv) . ' -c ' . $config, $output);
        echo join(chr(10), $output);

        die();
        break;
    case 'change-namespace':
        die('yet not implemented');
        break;
}

die('No input specified');
return;