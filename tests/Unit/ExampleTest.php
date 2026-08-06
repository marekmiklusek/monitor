<?php

declare(strict_types=1);

it('boots the application in the testing environment', function (): void {
    expect(app()->environment())->toBe('testing');
});
