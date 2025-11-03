# Recipe-app

レシピの投稿・共有・検索ができるWebアプリケーションです。

## 概要

Recipe-appは「**Vanilla's Kitchen**」というサービス名で提供されるレシピ管理プラットフォームです。管理者がレシピを投稿・管理し、ユーザーがレシピを検索・閲覧できる仕組みを提供します。Firebase認証によるセキュアなユーザー管理と、直感的なUIが特徴です。

## 機能一覧

**認証機能**

- ユーザー認証（Firebase Authentication）
- 新規登録機能（ユーザーネーム、メールアドレス、パスワード）
- ログイン機能（メールアドレス、パスワード）
- ログアウト機能
- 管理者・一般ユーザー権限管理

**レシピ管理**

- レシピの投稿・編集・削除
- 画像アップロード
- カテゴリ・ジャンル別分類
- 論理削除対応

**検索機能**
- キーワード検索（ひらがな・カタカナ・漢字対応）
- ジャンル別絞り込み

**ソーシャル機能**
- レシピへの「いいね」
- コメント機能
- 閲覧数カウント

## 使用技術

**フロントエンド**
- Vue.js 3.5.17
- Nuxt.js 3.17.5
- TypeScript 5.8.3


**バックエンド**
- Laravel 8.83.8
- PHP 8.1.33
- MySQL 8.0.43

**Webサーバー**
- nginx 1.21.1

**認証**
- Firebase Authentication

**開発環境**
- Docker
- Docker Compose
- phpMyAdmin

## 環境構築

**Dockerビルド**
1. `git clone git@github.com:ichiki925/SHARE-app.git`
2. DockerDesktopアプリを立ち上げて、`docker-compose up -d --build` を実行

**Laravel環境構築**
1. `docker-compose exec php bash`
2. `composer install`
3. .env.exampleを.envにコピー
```bash
cp .env.example .env
```
4. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# Firebase設定
FIREBASE_PROJECT_ID=your-firebase-project-id
```

5. アプリケーションキーの作成
```bash
php artisan key:generate
```

6. マイグレーション・シーディング・ストレージリンクを実行してコンテナから出る
```bash
php artisan migrate:fresh --seed
php artisan storage:link
exit
```

**Nuxt.js環境構築**
1. `docker-compose exec nuxt-app sh`
2. コンテナ内で以下を実行
```bash
npm install
exit
```

**Firebase設定**
1. Firebaseプロジェクトの作成
- [Firebase Console](https://console.firebase.google.com/)にアクセス
- 「プロジェクトを追加」をクリックしてプロジェクトを作成
2. Firebase Authentication の有効化
- Firebase Console で作成したプロジェクトを開く
- 左メニューの「Authentication」をクリック
- 「始める」をクリック
- 「Sign-in method」タブで「メール/パスワード」を有効化
3. サービスアカウント認証情報の取得
- Firebase Console の「プロジェクトの設定」（歯車アイコン）をクリック
- 「サービス アカウント」タブを選択
- 「新しい秘密鍵の生成」をクリック
- ブラウザのダウンロードフォルダにファイルがダウンロードされる
4. 認証情報ファイルの配置
- ※ storage/app/firebase/ フォルダは事前に作成してください
- ダウンロードしたJSONファイルを探す
- そのファイルを storage/app/firebase/credentials.json として保存
5. 環境変数の設定
- Firebase Console の「プロジェクトの設定」（歯車アイコン）→「全般」タブからFirebase設定情報を取得
- Laravel側の`.env` ファイルに以下を追加：
```text
FIREBASE_PROJECT_ID=your-firebase-project-id
```
- Nuxt.js側の`.env`ファイル(`nuxt-app`ディレクトリ内)を作成し、以下の情報を追加：
```text
NUXT_FIREBASE_API_KEY=your_api_key_here
NUXT_FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
NUXT_FIREBASE_PROJECT_ID=your_project_id
NUXT_FIREBASE_STORAGE_BUCKET=your_project.appspot.com
NUXT_FIREBASE_MESSAGING_SENDER_ID=your_sender_id
NUXT_FIREBASE_APP_ID=your_app_id
```
6. 設定変更後、全コンテナを再起動
```bash
docker-compose restart
```










### Nuxt (.env)
```env
# Laravel API設定
API_BASE_URL=http://localhost/api
NUXT_PUBLIC_API_BASE=http://localhost

# Firebase設定（各自のFirebaseプロジェクトの値に置き換える）
FIREBASE_API_KEY=your_api_key
FIREBASE_AUTH_DOMAIN=your_project_id.firebaseapp.com
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_STORAGE_BUCKET=your_project_id.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=your_sender_id
FIREBASE_APP_ID=1:your_sender_id:web:your_app_id
```

## API仕様

### 認証
- すべてのAPIはFirebase JWTトークンによる認証が必要
- ヘッダーに `Authorization: Bearer <firebase_token>` を含める

### 主要エンドポイント
```
GET    /api/recipes              # レシピ一覧取得
POST   /api/recipes              # レシピ投稿
GET    /api/recipes/{id}         # レシピ詳細取得
PUT    /api/recipes/{id}         # レシピ更新
DELETE /api/recipes/{id}         # レシピ削除

POST   /api/recipes/{id}/like    # いいね追加
DELETE /api/recipes/{id}/like    # いいね削除

GET    /api/recipes/{id}/comments # コメント一覧取得
POST   /api/recipes/{id}/comments # コメント投稿
```

## データベース設計

### 主要テーブル
- **users** - ユーザー情報（Firebase認証連携）
- **recipes** - レシピ情報
- **recipe_likes** - レシピのいいね情報
- **recipe_comments** - レシピのコメント情報

詳細なER図は `docs/er-diagram.drawio` を参照してください。

## ディレクトリ構成

```
Recipe-app/
├── laravel/                 # Laravel バックエンド
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Support/         # カスタムヘルパークラス
│   ├── database/
│   │   └── migrations/
│   └── routes/
├── nuxt/                    # Nuxt.js フロントエンド
│   ├── components/
│   ├── pages/
│   ├── plugins/
│   └── composables/
├── docs/                    # ドキュメント
│   └── er-diagram.drawio
└── README.md
```

## トラブルシューティング

### よくある問題

**Q: `php artisan migrate` でエラーが発生する**
A: データベース接続設定を確認してください。`.env`のDB_*設定が正しいか確認。

**Q: 画像がアップロードできない**
A: `php artisan storage:link` を実行し、storageディレクトリの権限を確認してください。

**Q: Firebase認証でエラーが発生する**
A: Firebase設定ファイルとenvファイルの設定値を確認してください。

**Q: CORS エラーが発生する**
A: Laravel側のCORS設定を確認し、NuxtのベースURLが正しく設定されているか確認してください。

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。

## 作者

[Your Name] - [your.email@example.com]



---

**開発開始日**: 2025年
**最終更新**: 2025年9月12日