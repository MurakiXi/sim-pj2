<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    //ID2-1　メールアドレスが未入力場合、バリデーションメッセージが表示される
    public function test_login_email_required(): void
    {
        $this->createUser();

        //2.メールアドレス以外のユーザー情報を入力する
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        //3.ログイン処理を行う
        $response->assertRedirect('/login');

        //期待挙動
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest();
    }

    //ID2-2
    public function test_login_password_required(): void
    {
        $this->createUser();

        //2.パスワード以外のユーザー情報を入力する
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => '',
            ]);

        $response->assertStatus(302);
        //3.ログインボタンを押す
        $response->assertRedirect('/login');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);


        $this->assertGuest();
    }

    //ID2-3　登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_invalid_password_fails(): void
    {
        $this->createUser();

        //2.登録内容と一致しない内容を入力する
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(302);
        //3.ログインボタンを押す
        $response->assertRedirect('/login');

        //期待挙動
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    //ID2　ログイン成功確認
    public function test_login_success(): void
    {
        //2.全ての必要項目を入力(準備)する
        $user = $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        //1.ログインページを開く
        $this->get('/login')->assertOk();
        //3.ログインボタンを押す
        $response = $this
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }
}
