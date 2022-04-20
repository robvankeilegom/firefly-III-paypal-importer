<?php

it('can get version page', function () {
    $this->get('/version');

    $this->assertSame(
        $this->app->version(),
        $this->response->getContent()
    );
});
