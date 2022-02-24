<?php

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testExample()
    {
        $this->get('/');

        $this->assertSame(
            $this->app->version(),
            $this->response->getContent()
        );
    }
}
