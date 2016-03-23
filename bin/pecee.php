<?php
global $argv, $appPath;

require_once $appPath . '/config/bootstrap.php';

// TODO: check if bootstrap.php exists.
// TODO: check if phinx-config exists.
// TODO: check framework migrations as fallback

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

switch(strtolower($argv[2])) {
    case 'migrations':
        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = $appPath . '/config/phinx-config.php';

        array_shift($argv);
        array_shift($argv);
        array_shift($argv);

        // Run Phinx
        exec($phinx . ' ' . join(' ', $argv) . ' -c ' . $config, $output);
        echo join(chr(10), $output);

        die();
        break;
    case 'change-namespace':
        die('yet not implemented');
        break;
    case 'key:generate':
        die('yet not implemented');
        break;
}

die('No input specified');
return;