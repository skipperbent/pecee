<?php
global $argv, $appPath;

require_once $appPath . '/bootstrap.php';

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
    case 'copy-migrations':

        \Pecee\IO\Directory::copy(dirname(__DIR__) . '/database/migrations', $appPath . '/database/migrations');
        echo 'Copy complete!';
        exit(0);

        break;
    case 'migrations':
        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = $appPath . '/config/phinx-config.php';

        array_shift($argv);
        array_shift($argv);

        $cmd = join(' ', $argv) . ' -c ' . $config;

        if(isset($argv[0]) && strtolower($argv[0]) === 'create') {
            $template = dirname(__DIR__) . '/database/stubs/migration.php';
            $cmd .= ' --template="'. $template . '"';
        }

        // Run Phinx
        exec($phinx . ' ' . $cmd, $output);
        echo join(chr(10), $output);

        exit(0);
        break;
    case 'change-namespace':
        die('yet not implemented');
        break;
    case 'key:generate':
        $token = new \Pecee\CsrfToken();
        echo 'New key: ' . md5($token->generateToken()) . chr(10);
        exit(0);
        break;
}

echo 'No input specified';
exit(1);