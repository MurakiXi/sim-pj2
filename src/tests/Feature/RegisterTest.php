<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Session;

class RegisterTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    private function validRegisterPayload(array $overrides = []): array
    {
        $token = 'test-token';

        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => $token,
        ], $overrides);
    }

    private function postRegister(array $overrides = [])
    {
        Session::start();

        $payload = $this->validRegisterPayload($overrides);
        $payload['_token'] = csrf_token();

        return $this
            ->from('/register')
            ->post('/register', $payload);
    }

    //ID1-1 名前が入力されていない場合、バリデーションメッセージが表示される
    public function test_register_name_required(): void
    {
        //2.名前を入力せずに他の必要項目を入力(準備)する
        $response = $this->postRegister(['name' => '']);
        //1.会員登録ページを開く
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'taro@example.com',
        ]);

        $this->assertGuest();
    }

    //ID1-2 メールアドレスが入力されていない場合、バリデーションメッセージが表示される
    public function test_register_email_required(): void
    {
        //2.メールアドレスを入力せずに他の必要項目を入力(準備)する
        $response = $this->postRegister(['email' => '']);

        //1.会員登録ページを開く
        //3.登録ボタンを押す
        $response->assertRedirect('/register');
        //期待挙動
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'taro@example.com']);

        $this->assertGuest();
    }

    //ID1-3　パスワードが7文字以下の場合、バリデーションメッセージが表示される
    public function test_register_password_min_8(): void
    {
        //2.7文字以下のパスワードと他の必要項目を入力(準備)する
        $response = $this->postRegister([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        //1.会員登録ページを開く
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'taro@example.com']);


        $this->assertGuest();
    }

    //ID1-4　パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
    public function test_register_password_confirmation_mismatch(): void
    {
        //2.確認用パスワードと異なるパスワードを入力し、他の必要項目も入力(準備)する
        $response = $this->postRegister([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        //1.会員登録ページを開く
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'taro@example.com']);

        $this->assertGuest();
    }
    //ID1-5 パスワードが入力されていない場合、バリデーションメッセージが表示される
    public function test_register_password_required(): void
    {
        //2.パスワードを入力せずに他の必要項目を入力(準備)する
        $response = $this->postRegister(['password' => '', 'password_confirmation' => '']);
        //1.会員登録ページを開く
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'taro@example.com']);

        $this->assertGuest();
    }

    // ID1-6 フォームに内容が入力されていた場合、データが正常に保存される
    public function test_register_success_saves_user_and_requires_verification(): void
    {
        //1.ユーザー情報を入力する
        $payload = $this->validRegisterPayload();

        //2.会員登録の処理を行う
        $response = $this->postRegister();

        $response->assertRedirect('/attendance');

        $this->assertAuthenticated();

        //期待挙動(DBに保存される)
        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'name'  => $payload['name'],
        ]);
        $this->assertDatabaseCount('users', 1);

        $this->get('/attendance')->assertRedirect('/email/verify');
    }
}
