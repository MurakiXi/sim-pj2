# sim-pj2（coachtech勤怠管理）

Laravel 8 を用いた勤怠管理アプリ（Docker環境）。

---

## 構成（使用コンテナ / ポート）

- nginx: http://localhost（80）
- phpMyAdmin: http://localhost:8080
- Mailhog（メールUI）: http://localhost:8025
  - SMTP: 1025
- MySQL: mysql:3306（コンテナ内）

---

## 使用技術

- PHP 8.1（Docker / php:8.1-fpm）
- Laravel 8.x
- MySQL 8.0.26
- nginx 1.21.1
- Mailhog（開発用メール受信）

---

※ 一部UIのために JavaScript を使用（外部ライブラリ/フレームワーク不使用）

## 環境構築

### 1. Dockerビルド

```bash
git clone https://github.com/MurakiXi/sim-pj2.git
cd sim-pj2
docker compose up -d --build
```

### 2. Laravel 初期化（php コンテナ内）

```bash
docker compose exec php bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan optimize:clear
```

### 3. .env 設定（重要）

src/.env（コンテナ内では /var/www/.env）を以下に合わせてください。

```env
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 4. マイグレーション・シーディング(phpコンテナ内)

```bash
php artisan migrate --seed
```

※　Seederで一般ユーザー3名とそれぞれ直近3日分の勤怠(休憩1回)、管理者ユーザー1名を作成

### 5.動作確認用ダミーユーザー（Seeder）

一般ユーザー1

email: user1@example.com

password: user1pass


一般ユーザー2

email: user2@example.com

password: user2pass


一般ユーザー3

email: user3@example.com

password: user3pass

管理者

email: admin@example.com

password: adminpass

※ユーザー登録後はMailhogで認証メールを開き認証リンクをクリックする必要あり

※Seeder作成ユーザーはemail_verified_at済みなので不要

### 6.URL一覧

一般ユーザーログイン: http://localhost/login

管理者ログイン: http://localhost/admin/login

phpMyAdmin: http://localhost:8080 (ログイン：root/root)

Mailhog: http://localhost:8025

---

## テスト実行（重要：stamp_test DB）

テストは src/.env.testing（DB_DATABASE=stamp_test）を参照します。DBを作成してください。

```bash
docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS stamp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

テスト実行：

```bash
docker compose exec php vendor/bin/phpunit
```

または

```bash
docker compose exec php php artisan test
```

---



## テストにおけるエラーメッセージ文言の扱い

勤怠修正のバリデーションメッセージについて、要件シート(FN029)とテストケース一覧(ID11-1)で文言に一部齟齬あり。

実装は要件シート(FN029)の記載に沿って行い、PHPUnitテストでは文言差分による不必要な失敗を避けるため、該当箇所は正規表現で双方の文言を許容する形で検証。

(「出勤時間が不適切な値です」/「出勤時間もしくは退勤時間が不適切な値です」)




## テーブル仕様書・基本設計書

テーブル仕様書(含ER図)：docs/table_spec.xlsx

基本設計書：docs/basic_design.xlsx」
