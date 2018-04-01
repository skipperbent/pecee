<?php
global $argv, $appPath;

try {
	require_once $appPath . '/bootstrap.php';
} catch (\PDOException $e) {
	// Ignore database errors for now
}

// TODO: check if bootstrap.php exists.
// TODO: check if phinx-config exists.
// TODO: move stuff to separate classes.

echo chr(10);

function loopFolder($path, \Closure $callback, array $filterExtensions = [])
{

	$handle = opendir($path);
	while ($item = readdir($handle)) {

		if ($item === '.' || $item === '..') {
			continue;
		}

		$newPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($item, DIRECTORY_SEPARATOR);
		if (is_dir($newPath) === true) {
			loopFolder($newPath, $callback);
		} else {

			if (count($filterExtensions) && !in_array(\Pecee\IO\File::getExtension($newPath), $filterExtensions, false)) {
				continue;
			}

			$callback($newPath);
		}
	}
	closedir($handle);

}

function setEnvironmentValue($key, $value = '', $autoCreate = true, $setExample = true)
{

	global $appPath;

	$setValue = function ($file) use ($key, $value, $autoCreate) {
		if (is_file($file)) {
			$lines = explode(chr(10), file_get_contents($file));

			$found = false;

			foreach ($lines as $i => $line) {
				if (stripos($line, $key . '=') !== false) {
					$found = true;
					$lines[$i] = $key . '=' . $value;
					break;
				}
			}

			if ($autoCreate === true && $found === false) {
				$lines[] = strtoupper($key) . '=' . trim($value);
			}

			file_put_contents($file, join(chr(10), $lines));
		}
	};

	$setValue($appPath . '/.env');

	if ($setExample === true) {
		$setValue($appPath . '/.env.example');
	}
}

switch (strtolower($argv[1])) {
	case 'copy-migrations':

		\Pecee\IO\Directory::copy(dirname(__DIR__) . '/database/migrations', $appPath . '/database/migrations');
		echo 'Copy complete!';
		exit(0);

		break;
	case 'phinx':
		$phinx = $appPath . '/vendor/bin/phinx';
		$config = $appPath . '/config/phinx-config.php';

		$argv = array_slice($argv, 1);

		$args = $argv;
		$args[] = '--configuration=' . $config;

		if (isset($argv[1]) && strtolower($argv[1]) === 'create') {
			$template = dirname(__DIR__) . '/database/stubs/migration.php';
			$args[] = '--template=' . $template;
		}

		// Run Phinx
		try {
			$app = new \Phinx\Console\PhinxApplication();
			$app->run(new \Symfony\Component\Console\Input\ArgvInput($args));
		}catch(Exception $e) {
			echo $e->getMessage() . chr(10);
		}

		exit(0);
		break;
	case 'change-namespace':

		$argv = array_slice($argv, 2);

		if (!isset($argv[0])) {
			echo 'Error: missing required parameter namespace';
			exit(1);
		}

		if (preg_match_all('/^[a-zA-Z\_]+$/i', $argv[0]) === 0) {
			echo 'Error: invalid namespace (example: Demo)';
			exit(1);
		}

		$newNamespace = trim($argv[0]);
		$oldNamespace = null;

		function getClassInfo($file)
		{
			$contents = file_get_contents($file);
			preg_match_all('/namespace ([^;\n]+)|class ([^\s{]+)/i', $contents, $matches);

			return [
				'has_match' => isset($matches[1]) && count($matches[1]) > 0 || isset($matches[2]) && count($matches[2]) > 0,
				'contents'  => $contents,
				'class'     => isset($matches[2][1]) ? $matches[2][1] : null,
				'namespace' => isset($matches[1][0]) ? $matches[1][0] : null,
				'full'      => isset($matches[1][0]) ? $matches[1][0] . '\\' . $matches[2][1] : null,
				'matches'   => $matches,
			];
		}

		$oldNamespace = 'Demo';

		function replaceFile($file, array $map = [])
		{
			global $oldNamespace;
			global $newNamespace;

			$map = array_merge([
				$oldNamespace . '::'          => $newNamespace . '::',
				$oldNamespace . '\\'          => $newNamespace . '\\',
				'use ' . $oldNamespace . '\\' => 'use ' . $newNamespace . '\\',
			], $map);

			if (is_file($file)) {
				$contents = file_get_contents($file);
				$contents = str_ireplace(array_keys($map), array_values($map), $contents);
				file_put_contents($file, $contents);
				$contents = null;
			}
		}

		// --- Fix classes ---

		loopFolder($appPath . '/app', function ($file) {

			global $oldNamespace;
			global $newNamespace;

			$info = getClassInfo($file);

			if ($info['has_match'] === true) {
				echo '- Class: ' . $file . '...';

				$tmp = explode('\\', $info['namespace']);

				if ($oldNamespace === null) {
					$oldNamespace = $tmp[0];
				}

				$tmp[0] = $newNamespace;

				replaceFile($file, [
					$info['namespace'] => join('\\', $tmp),
				]);

				echo ' OK!' . chr(10);
			}
		}, ['php']);

		// --- Fix views ---

		loopFolder($appPath . '/views', function ($file) use ($newNamespace, $oldNamespace) {
			echo '- View: ' . $file . '...';

			replaceFile($file);

			echo ' OK!' . chr(10);
		}, ['php']);

		echo chr(10) . '.... project files OK!' . chr(10) . chr(10);

		// --- Fixing routes file ---

		loopFolder($appPath . '/routes', function ($file) use ($newNamespace, $oldNamespace) {
			echo '- View: ' . $file . '...';

			replaceFile($file);

			echo ' OK!' . chr(10);
		}, ['php']);

		echo chr(10) . '.... router files OK!' . chr(10) . chr(10);

		// --- Fixing .env file ---

		echo '- Setting new APP_NAME in env...';

		setEnvironmentValue('APP_NAME', $newNamespace);

		echo 'OK!' . chr(10);

		// --- COMPLETED ---

		echo '- Completed!' . chr(10) . chr(10);

		exit(0);

		break;
	case 'key:generate':
		echo 'New key: ' . \Pecee\Guid::generateSalt() . chr(10);
		exit(0);
		break;
	case 'env:key-generate':
		$key = \Pecee\Guid::generateSalt();
		setEnvironmentValue('APP_SECRET', $key);

		echo 'App-secret successfully set' . chr(10);
		exit(0);

		break;
	case 'password:create': {

		$argv = array_slice($argv, 2);

		if (!isset($argv[0])) {
			echo 'Error: missing required parameter [input password]';
			exit(1);
		}

		echo sprintf('New password: %s', password_hash($argv[0], PASSWORD_DEFAULT)) . chr(10);

		echo chr(10);
		exit(0);
	}
		break;
	case 'password:reset': {

		$argv = array_slice($argv, 2);

		if (!isset($argv[0])) {
			echo 'Error: missing required parameter [user id]';
			exit(1);
		}

		$user = \Pecee\Model\ModelUser::instance()->findOrfail($argv[0]);

		if ($user === null) {
			echo sprintf('User with id %s not found', $argv[0]) . chr(10);
		} else {
			$password = \Pecee\Guid::generateHash(8);
			$user->setPassword($password);
			$user->save();

			echo sprintf('Password changed for %s to: %s', $user->username, $password) . chr(10);
		}

		echo chr(10);
		exit(0);

	}
		break;
}

echo 'Error: please enter valid argument';
exit(1);