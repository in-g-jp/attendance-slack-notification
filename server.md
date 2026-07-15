# サーバー運用メモ

本番環境の基本情報と、再デプロイ時の手順をまとめたドキュメントです。

---

## 🌐 サイト情報

| 項目 | 内容 |
| --- | --- |
| URL | [https://murata.intern.in-g.org](https://murata.intern.in-g.org) |
| サーバーIP | `非公開` |
| SSL | 設定済み |

## 📂 サーバー構成

| 項目 | 内容 |
| --- | --- |
| プロジェクトルート | `/home/project/murata.intern.in-g.org/htdocs` |
| Webサーバー | Apache (`httpd`) |

## 🗄️ データベース（MySQL）

| 項目 | 内容 |
| --- | --- |
| データベース名 | `murata_db` |
| ユーザー名 | `murata_user` |
| パスワード | サーバー内の `.env` を参照 |
| ホスト | `localhost` (`127.0.0.1`) |

## 🔑 接続情報（SSH）

接続には指定の秘密鍵・ポート番号・ユーザー名が必要です。  
詳細は社内管理ドキュメントを参照してください。

---

## 🚀 再デプロイ手順（更新の反映）

開発環境（Mac / vsc）で修正した内容を本番サーバーへ反映する手順です。

### 1) ローカル（Mac / Cursor）で作業

修正完了後、GitHub に変更を反映します。

```bash
# 変更をステージング
git add .

# コミット（修正内容をメモ）
git commit -m "修正内容のメモ"

# GitHubへ送信
git push origin main
```

### 2) サーバー（本番環境）で作業

SSH でログイン後、最新コードを取り込みます。

```bash
# プロジェクトディレクトリへ移動
cd /home/project/murata.intern.in-g.org/htdocs

# GitHubから最新の差分を取得
git pull origin main
```

### 3) 反映後のメンテナンス

変更内容に応じて、以下のコマンドを実行してください。

| 変更内容 | 実行コマンド |
| --- | --- |
| 設定（`.env`）を変更した場合 | `php artisan config:clear` |
| DB構成を変更した場合 | `php artisan migrate` |
| ライブラリを追加・更新した場合 | `composer install` |
| JS/CSSをビルドし直す場合 | `npm run build` |

### 4) データを変更する場合
envでカレンダーに登録する個人情報を管理している。

```bash
# envファイルの値を修正する。
vim .env(iを押して修正、escを押した後:wq+enterで終了)

# マイグレーションの実行
php artisan migrate:fresh --seed

# 変更の確認
php artisan tinker
\App\Models\Calendar::all();
```

