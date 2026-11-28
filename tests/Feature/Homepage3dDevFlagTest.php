<?php

it('does not expose the dev-only 3D override outside the local environment', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-dev-3d', false);
});

it('exposes the dev-only 3D override when the app runs locally', function () {
    $this->app['env'] = 'local';

    $this->get('/')
        ->assertOk()
        ->assertSee('data-dev-3d', false);
});
