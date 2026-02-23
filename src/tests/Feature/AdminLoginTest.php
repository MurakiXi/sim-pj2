<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Session;

class AdminLoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
    }

    private function postAdminLogin(array $overrides = [])
    {
        Session::start();

        $payload = array_merge([
            'email' => 'admin@example.com',
            'password' => 'adminpass',
        ], $overrides);

        $payload['_token'] = csrf_token();

        return $this->from('/admin/login')->post('/admin/login', $payload);
    }

    //ID3-1
    public function test_admin_login_email_required(): void
    {
        $response = $this->postAdminLogin(['email' => '']);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest('admin');
    }


    //ID3-2
    public function test_admin_login_password_required(): void
    {
        $response = $this->postAdminLogin(['password' => '']);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertGuest('admin');
    }


    //ID3-3　登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_admin_login_wrong_email_fails(): void
    {
        $response = $this->postAdminLogin([
            'email' => 'no-such-admin@example.com', // 形式は正しいが存在しない
            'password' => 'adminpass',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest('admin');
    }

    public function test_admin_can_login(): void
    {
        $admin = Admin::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'adminpass',
        ]);

        $response->assertRedirect('/admin/attendance/list');

        $this->assertTrue(auth('admin')->check());
    }
}
