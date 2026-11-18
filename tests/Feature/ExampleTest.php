<?php

test('the application root shows the public status page', function () {
    $response = $this->get('/');

    $response->assertOk()->assertSee('ITK website status');
});
