# Recipe-app-demo

⚠️ **ポートフォリオ用デモ環境** ⚠️

レシピの投稿・共有・検索ができるWebアプリケーション「**Vanilla's Kitchen**」のデモ環境です。

**🔗 本番環境リポジトリ**: [ichiki925/recipe-app](https://github.com/ichiki925/recipe-app)

---

## ⚠️ 重要な注意事項

このリポジトリは**ポートフォリオ展示用のデモ環境**です。本番環境とは完全に分離されています。

**本番環境との主な違い:**
- ダミーデータが投入されています
- Firebase プロジェクトが異なります（`recipe-app-demo-2cfdc`）
- データベース名が異なります（`vanilla_kitchen_demo`）
- セキュリティ設定が異なります

---

## 🎯 デモ用管理者アカウント

デモ環境では、以下のアカウントでログインして全機能をお試しいただけます：

- **メールアドレス**: `demo@example.com`
- **パスワード**: `password`

---

## 📋 概要

Recipe-appは「**Vanilla's Kitchen**」というサービス名で提供されるレシピ管理プラットフォームです。管理者がレシピを投稿・管理し、ユーザーがレシピを検索・閲覧できる仕組みを提供します。Firebase認証によるセキュアなユーザー管理と、直感的なUIが特徴です。

---

## ✨ 主要機能

### 認証機能
- ユーザー認証（Firebase Authentication）
- 新規登録機能（ユーザーネーム、メールアドレス、パスワード）
- ログイン機能（メールアドレス、パスワード）
- ログアウト機能
- 管理者・一般ユーザー権限管理

### レシピ管理
- レシピの投稿・編集・削除
- 画像アップロード（Firebase Storage）
- カテゴリ・ジャンル別分類
- 論理削除対応

### 検索機能
- キーワード検索（ひらがな・カタカナ・漢字対応）
- ジャンル別絞り込み

### ソーシャル機能
- レシピへの「いいね」
- コメント機能
- 閲覧数カウント

---

## 🛠️ 使用技術

### フロントエンド
- **Vue.js** 3.5.17
- **Nuxt.js** 3.17.5
- **TypeScript** 5.8.3

### バックエンド
- **Laravel** 11.x
- **PHP** 8.2
- **MySQL** 8.0

### Webサーバー
- **nginx** 1.21

### 認証・ストレージ
- **Firebase Authentication**
- **Firebase Storage**

### 開発環境
- **Docker**
- **Docker Compose**

---

## 📊 データベース設計

### 主要テーブル
- **users** - ユーザー情報（Firebase認証連携）
- **recipes** - レシピ情報
- **recipe_likes** - レシピのいいね情報
- **recipe_comments** - レシピのコメント情報

---

## 🔗 API仕様

### 認証
- すべてのAPIはFirebase JWTトークンによる認証が必要
- ヘッダーに `Authorization: Bearer <firebase_token>` を含める

### 主要エンドポイント
```
GET    /api/recipes              # レシピ一覧取得
POST   /api/recipes              # レシピ投稿（管理者のみ）
GET    /api/recipes/{id}         # レシピ詳細取得
PUT    /api/recipes/{id}         # レシピ更新（管理者のみ）
DELETE /api/recipes/{id}         # レシピ削除（管理者のみ）

POST   /api/recipes/{id}/like    # いいね追加
DELETE /api/recipes/{id}/like    # いいね削除

GET    /api/recipes/{id}/comments # コメント一覧取得
POST   /api/recipes/{id}/comments # コメント投稿
```

---

## 📁 ディレクトリ構成

```
recipe-app-demo/
├── src/                     # Laravel バックエンド
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Support/         # カスタムヘルパークラス
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/         # ダミーデータ生成
│   └── routes/
├── nuxt-app/                # Nuxt.js フロントエンド
│   ├── components/
│   ├── pages/
│   ├── plugins/
│   └── composables/
├── docker/                  # Docker設定
└── README.md
```

---

## 🚀 デモ環境の起動方法

このデモ環境をローカルで動かす場合：

### 前提条件
- Docker Desktop がインストールされていること
- Git がインストールされていること

### 起動手順

1. **リポジトリをクローン**
```bash
git clone https://github.com/ichiki925/recipe-app-demo.git
cd recipe-app-demo
```

2. **Dockerコンテナを起動**
```bash
docker-compose up -d
```

3. **Laravel環境構築**
```bash
docker-compose exec php bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
exit
```

4. **Nuxt.js環境構築**
```bash
docker-compose exec nuxt-app sh
npm install
exit
```

5. **ブラウザでアクセス**
- フロントエンド: http://localhost:3000
- バックエンド: http://localhost

---

## 🔐 Firebase設定（参考情報）

デモ環境で使用しているFirebase設定：

**Project ID**: `recipe-app-demo-2cfdc`
**Storage Bucket**: `recipe-app-demo-2cfdc.firebasestorage.app`

---

## 📝 ライセンス

このプロジェクトはMITライセンスの下で公開されています。

---

## 👤 作者

**Kuniko Ichiki**
- GitHub: [@ichiki925](https://github.com/ichiki925)

---

**開発開始日**: 2025年7月
**最終更新**: 2025年11月3日