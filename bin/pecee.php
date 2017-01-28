<?php
global $argv, $appPath;

require_once $appPath . '/bootstrap.php';

// TODO: check if bootstrap.php exists.
// TODO: check if phinx-config exists.

echo chr(10);

function getClassInfo($file)
{
    $contents = file_get_contents($file);
    preg_match_all('/namespace ([^;\n]+)|class ([^\s{]+)/is', $contents, $matches);

    return [
        'class'     => $matches[2][1],
        'namespace' => $matches[1][0],
        'full'      => $matches[1][0] . '\\' . $matches[2][1],
    ];
}

function changeNamespace($newNamespace, $file)
{
    $contents = file_get_contents($file);
    $info = getClassInfo($file);
    str_ireplace($info['namespace'], $newNamespace, $contents);
    file_put_contents($file, $contents);
}

switch (strtolower($argv[1])) {
    case 'copy-migrations':

        \Pecee\IO\Directory::copy(dirname(__DIR__) . '/database/migrations', $appPath . '/database/migrations');
        echo 'Copy complete!';
        exit(0);

        break;
    case 'migrations':
        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = $appPath . '/config/phinx-config.php';

        $argv = array_slice($argv, 2);

        $cmd = join(' ', $argv) . ' -c ' . $config;

        if (isset($argv[0]) && strtolower($argv[0]) === 'create') {
            $template = dirname(__DIR__) . '/database/stubs/migration.php';
            $cmd .= ' --template="' . $template . '"';
        }

        // Run Phinx
        exec($phinx . ' ' . $cmd, $output);
        echo join(chr(10), $output);

        exit(0);
        break;
    case 'seed:create':

        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = $appPath . '/config/phinx-config.php';

        $argv = array_slice($argv, 2);

        $cmd = 'seed:create ' . join(' ', $argv) . ' -c ' . $config;

        // Run Phinx
        exec($phinx . ' ' . $cmd, $output);
        echo join(chr(10), $output);

        exit(0);

        break;
    case 'seed:run':

        $phinx = dirname(dirname(dirname(__DIR__))) . '/bin/phinx';
        $config = $appPath . '/config/phinx-config.php';

        $argv = array_slice($argv, 2);

        $cmd = 'seed:run ' . join(' ', $argv) . ' -c ' . $config;

        // Run Phinx
        exec($phinx . ' ' . $cmd, $output);
        echo join(chr(10), $output);

        exit(0);

        break;
    case 'change-namespace':
        die('yet not implemented');
        break;
    case 'key:generate':
        echo 'New key: ' . \Pecee\Guid::generateSalt() . chr(10);
        exit(0);
        break;
    case 'password:create': {

        $argv = array_slice($argv, 2);

        if (!isset($argv[0])) {
            die('Error: missing required parameter [input password]');
        }

        echo sprintf('New password: %s', password_hash($argv[0], PASSWORD_DEFAULT)) . chr(10);

        echo chr(10);
        exit(0);
    }
        break;
    case 'password:reset': {

        $argv = array_slice($argv, 2);

        if (!isset($argv[0])) {
            die('Error: missing required parameter [user id]');
        }

        $user = \Pecee\Model\ModelUser::findOrfail($argv[0]);

        if ($user === null) {
            echo sprintf('User with id %s not found', $argv[0]) . chr(10);
        } else {
            $password = \Pecee\Guid::createRandomPassword(8);
            $user->setPassword($password);
            $user->save();

            echo sprintf('Password changed for %s to: %s', $user->username, $password) . chr(10);
        }

        echo chr(10);
        exit(0);

    }
        break;
}

echo 'No input specified';
exit(1);