<?php

declare(strict_types=1);

use App\Actions\ShortenSourcePath;

it('keeps everything after the vendor directory', function (): void {
    $file = '/data/7/7/77941b09-b3c1-4024-a9cb-91e72006b433/wellmall.webotvurcidev.cz/www/vendor/psy/psysh/src/ExecutionClosure.php';

    expect(resolve(ShortenSourcePath::class)->execute($file, 41))
        ->toBe('psy/psysh/src/ExecutionClosure.php:41');
});

it('keeps the last two directories of an application path', function (): void {
    $file = '/data/7/7/deadbeef/wellmall.cz/www/app/Http/Controllers/OrderController.php';

    expect(resolve(ShortenSourcePath::class)->execute($file, 42))
        ->toBe('Http/Controllers/OrderController.php:42');
});

it('handles windows separators', function (): void {
    $file = 'C:\\Users\\marek\\Herd\\playground\\vendor\\symfony\\console\\Application.php';

    expect(resolve(ShortenSourcePath::class)->execute($file, 785))
        ->toBe('symfony/console/Application.php:785');
});

it('keeps everything after the node modules directory', function (): void {
    $file = '/srv/app/node_modules/react-dom/index.js';

    expect(resolve(ShortenSourcePath::class)->execute($file, 7))
        ->toBe('react-dom/index.js:7');
});

it('omits the line when there is none', function (): void {
    expect(resolve(ShortenSourcePath::class)->execute('/srv/app/app/Jobs/SendInvoice.php'))
        ->toBe('app/Jobs/SendInvoice.php');
});

it('returns a bare filename unchanged', function (): void {
    expect(resolve(ShortenSourcePath::class)->execute('artisan', 16))->toBe('artisan:16');
});

it('keeps a short path as it is', function (): void {
    expect(resolve(ShortenSourcePath::class)->execute('app/artisan', 3))->toBe('app/artisan:3');
});
