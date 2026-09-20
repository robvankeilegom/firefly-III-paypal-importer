<?php

it('can get version page', function () {
    $response = $this->get('/version');

    $this->assertSame(
        $this->app->version(),
        $response->getContent()
    );
});
