<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

header('Content-Type: text/html; charset=utf-8');

$laravelPath = __DIR__ . '/../laravel';

/**
 * The DirectAdmin package keeps Laravel outside public_html, so Laravel's
 * normal `storage:link` target (laravel/public/storage) is not the web root.
 */
function createPublicStorageLink(string $laravelPath, string $publicPath): string
{
    $target = $laravelPath . '/storage/app/public';
    $link = $publicPath . '/storage';

    if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
        throw new RuntimeException("Could not create storage directory: {$target}");
    }

    if (is_link($link)) {
        if (realpath($link) === realpath($target)) {
            return 'Public storage link already exists.';
        }

        if (!unlink($link)) {
            throw new RuntimeException("Could not replace existing storage link: {$link}");
        }
    } elseif (file_exists($link)) {
        throw new RuntimeException("{$link} already exists and is not a symbolic link. Remove or rename it in DirectAdmin File Manager, then try again.");
    }

    if (!symlink($target, $link)) {
        throw new RuntimeException('Could not create the public storage link. Confirm that symlinks are enabled by your hosting provider.');
    }

    return 'Public storage link created successfully.';
}

$environmentPath = $laravelPath . '/.env';
if (!is_file($environmentPath)) {
    $parentEnvironmentPath = __DIR__ . '/../.env';
    $exampleEnvironmentPath = $laravelPath . '/.env.example';
    if (is_file($parentEnvironmentPath)) {
        @copy($parentEnvironmentPath, $environmentPath);
    } elseif (is_file($exampleEnvironmentPath)) {
        @copy($exampleEnvironmentPath, $environmentPath);
    }
}

require __DIR__ . '/../laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$actions = [
    'key' => ['key:generate', ['--force' => true]],
    'migrate' => ['migrate', ['--force' => true]],
    'seed' => ['db:seed', ['--force' => true]],
    'storage' => null,
    'clear' => ['optimize:clear', []],
];

$action = (string) ($_POST['action'] ?? '');
$output = null;

if ($action !== '') {
    if (!array_key_exists($action, $actions)) {
        http_response_code(422);
        $output = 'Unknown deployment action.';
    } else {
        try {
            if ($action === 'storage') {
                $output = createPublicStorageLink($laravelPath, __DIR__);
            } else {
                [$command, $arguments] = $actions[$action];
                Artisan::call($command, $arguments);
                $output = Artisan::output() ?: "{$command} completed successfully.";
            }
        } catch (Throwable $exception) {
            http_response_code(500);
            $output = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Vegro HR deployment helper</title></head>
<body>
<h1>Vegro HR deployment helper</h1>
<p>Run only the required actions, then delete <code>deploy.php</code> in DirectAdmin File Manager.</p>
<?php if ($output !== null): ?>
<pre><?= htmlspecialchars($output, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>
<?php foreach (array_keys($actions) as $name): ?>
<form method="post" style="display:inline-block; margin:0 8px 8px 0">
    <input type="hidden" name="action" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit"><?= htmlspecialchars(ucfirst($name), ENT_QUOTES, 'UTF-8') ?></button>
</form>
<?php endforeach; ?>
</body>
</html>
