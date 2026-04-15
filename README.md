# 勤怠管理アプリ (Attendance App)

coachtech 勤怠管理アプリ。出退勤の打刻、休憩管理、勤怠修正申請、および管理者による承認・スタッフ管理機能を提供。

## 環境構築

### Docker ビルド

```bash
# リポジトリのクローン
git clone [repository_url] .

# Docker コンテナのビルド・起動
docker compose up -d --build

# PHPコンテナ内へ
docker compose exec php bash

# 依存関係のインストール
composer install

# 環境設定ファイルの作成
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate

# マイグレーションとシーディング（ダミーデータ投入）を実行
php artisan migrate --seed

# ロゴ画像を表示可能にするためのシンボリックリンク作成
docker compose exec php php artisan storage:link

# storage 配下にロゴ画像が配置されているか確認（なければdocsからコピー）
# ※プロジェクトルート（WSL）で実行してください
mkdir -p src/storage/app/public/images
cp "docs/COACHTECHヘッダーロゴ.png" src/storage/app/public/images/logo.png
```

## 開発環境 URL

- **打刻画面（トップ）**: [http://localhost/](http://localhost/)
- **管理者ログイン**: [http://localhost/admin/login](http://localhost/admin/login)
- **メールプレビュー (Mailpit)**: [http://localhost:8025/](http://localhost:8025/)

## ログイン情報

採点および動作確認用のテストアカウントは以下の通りです。

### 管理者ユーザー
- **メールアドレス**: `admin@example.com`
- **パスワード**: `password`

### 一般ユーザー
- **メールアドレス**: `user@example.com`
- **パスワード**: `password`

## 使用技術
- PHP 8.2
- Laravel 10
- MySQL 8.0
- Fortify (認証基盤)
- CSS (Vanilla CSS / プレミアムデザイン)

## ER図
erDiagram
    users ||--o{ attendances : "1:多 (user_id)"
    users ||--o{ attendance_correction_requests : "1:多 (user_id)"
    users ||--o{ attendance_correction_requests : "1:多 承認者 (approved_by)"
    
    attendances ||--o{ breaks : "1:多 (attendance_id)"
    attendances ||--o{ attendance_correction_requests : "1:多 (attendance_id)"
    
    attendance_correction_requests ||--o{ correction_breaks : "1:多 (correction_id)"

    users {
        bigint unsigned id PK
        string name
        string email
        string password
        string role "user, admin"
        timestamp email_verified_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }erDiagram
    users ||--o{ attendances : "1:多 (user_id)"
    users ||--o{ attendance_correction_requests : "1:多 (user_id)"
    users ||--o{ attendance_correction_requests : "1:多 承認者 (approved_by)"
    
    attendances ||--o{ breaks : "1:多 (attendance_id)"
    attendances ||--o{ attendance_correction_requests : "1:多 (attendance_id)"
    
    attendance_correction_requests ||--o{ correction_breaks : "1:多 (correction_id)"

    users {
        unsigned_bigint id PK
        string name
        string email
        string password
        string role "user, admin"
        timestamp email_verified_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    attendances {
        unsigned_bigint id PK
        unsigned_bigint user_id FK "users(id)"
        date date
        time clock_in
        time clock_out
        string status
        text note
        timestamp created_at
        timestamp updated_at
    }

    breaks {
        unsigned_bigint id PK
        unsigned_bigint attendance_id FK "attendances(id)"
        time break_start
        time break_end
        timestamp created_at
        timestamp updated_at
    }

    attendance_correction_requests {
        unsigned_bigint id PK
        unsigned_bigint attendance_id FK "attendances(id)"
        unsigned_bigint user_id FK "users(id)"
        string status "pending, approved, rejected等"
        time clock_in
        time clock_out
        text note
        unsigned_bigint approved_by FK "users(id)"
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }

    correction_breaks {
        unsigned_bigint id PK
        unsigned_bigint correction_id FK "attendance_correction_requests(id)"
        time break_start
        time break_end
        timestamp created_at
        timestamp updated_at
    }

## 各種テストの実行方法

```bash
# 全テスト（Feature/Unit）の実行
php artisan test
```
## 特記事項
・テストケースと機能要件で矛盾している場合は、適宜判断

    ・出勤押下後のステータス表示（※勤務中か、出勤中か表記揺れ）
  　    FN020	出勤機能3. 「出勤」を押下した時に，画面が「出勤中」ステータスのものに変更になること
        テスト期待挙動：画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
    　→　機能要件と画面デザインを正とする。

    ・ID11：出退勤エラーメッセージ （※エラーメッセージに齟齬）　
        FN029-1要件：「出勤時間もしくは退勤時間が不適切な値です」
        テスト期待挙動：「出勤時間が不適切な値です」
        →　機能要件を正とする。

・誤字は適宜読み替え
    ・テストケース
        9	勤怠一覧情報取得機能（一般ユーザー）
        14　ユーザー情報取得機能（管理者）
　      「翌月」を押下した時に表示月の前月の情報が表示される
        →　前月は翌月の誤字と判断。


## 追加テスト
・機能要件とテストケースを突合して、不足していると感じたテストケースを追加。
    (AdditionalRequirementTest.phpの11項目として実装)

# 完全に抜け落ちているテストケース
    機能要件には存在するが、テストケースに記載がない項目。

    ・FN004 / FN010：認証画面間の遷移
        会員登録画面からログイン画面への遷移リンクが機能するか。
        ログイン画面から会員登録画面への遷移リンクが機能するか。

    ・FN011：メールを用いた認証機能
    ・未認証でのログインブロック
        新規会員登録後、メール認証を完了せずにログインを試みた場合、メール認証誘導画面へ遷移するか。（FN011-2）

    ・認証メール再送機能
        メール認証誘導画面で「認証メール再送」ボタン（または該当ボタン）を押下した際、メールが再送信されるか。（FN012）

    ・FN013 / FN017：ログアウト機能
        一般ユーザーがヘッダーから正常にログアウトできるか。
        管理者ユーザーがヘッダーから正常にログアウトできるか。

    ・FN022-3：退勤時のフラッシュメッセージ
        退勤ボタンを押下した際、「お疲れ様でした。」というメッセージが画面に表示されるか。

    ・FN026-4：詳細画面の休憩入力フィールド（一般ユーザー）
        「休憩」について、休憩回数分のレコードと追加で１つ分の入力フィールドが表示されているか。

    ・FN027 / FN038：承認待ち状態の編集ロック（一般・管理者共通）
        承認待ちの申請詳細画面を開いた際、フィールドが編集不可になっており、「承認待ちのため修正はできません。」というメッセージが表示されているか。

    ・FN040：管理者による直接修正の成功（管理者）
        管理者が勤怠詳細を修正し、バリデーションエラーなく保存処理を行った場合、データが正常に更新され、一般ユーザーの画面にも反映されるか。
        （※現状、管理者の修正機能はエラー時のテストしかない）

    ・FN045：CSV出力機能（管理者）
        スタッフの月次勤怠画面で「CSV出力」ボタンを押下した際、選択した月の勤怠情報がCSVファイルとして正常にダウンロードされるか。


# 要件に対してテストの網羅性が不足している項目
    機能要件（FN）で定義されている条件に対して、テストケース側の検証パターンが足りていない項目。

    ・FN029-1 / FN039-1：出退勤の時間の矛盾
        要件：「出勤時間が退勤時間より後」または「退勤時間が出勤時間より前」

        現在のテスト（ID11/ID13）：出勤時間を退勤時間より後にする場合のみテストしている。
        追加テスト： 退勤時間を出勤時間より前に設定した場合もエラーになるか。

    FN029-2 / FN039-2：休憩開始時間の矛盾
        要件：「休憩開始時間が出勤時間より前」または「退勤時間より後」

        現在のテスト（ID11/ID13）：退勤時間より後に設定した場合のみテストしている。
        追加テスト： 休憩開始時間を出勤時間より前に設定した場合もエラーになるか。



