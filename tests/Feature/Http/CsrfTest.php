<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PU-121-other: CSRFトークンを付与せず送信すると419が返り認証処理が実行されない
     */
    public function test_login_without_csrf_token_returns_419(): void
    {
        $this->app['env'] = 'production';

        try {
            $response = $this->post('/login', [
                'company_code' => '',
                'email' => 'nobody@example.com',
                'password' => 'password',
            ]);

            $response->assertStatus(419);
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertGuest();
    }
}
