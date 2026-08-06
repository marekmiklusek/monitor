<?php

declare(strict_types=1);

it('redirects root to login', function (): void {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
