<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/login', 'GET'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
