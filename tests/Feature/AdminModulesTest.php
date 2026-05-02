<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_whatsapp_pages_render(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/wa/blast', 'GET'));
        $this->assertSame(200, $response->getStatusCode());

        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/wa/history', 'GET'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_admin_system_config_pages_render(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/settings/branding', 'GET'));
        $this->assertSame(200, $response->getStatusCode());

        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/settings/backup', 'GET'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_admin_academic_year_page_renders(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $response = $this->app
            ->make(Kernel::class)
            ->handle(Request::create('/admin/school/years', 'GET'));
        $this->assertSame(200, $response->getStatusCode());
    }
}
