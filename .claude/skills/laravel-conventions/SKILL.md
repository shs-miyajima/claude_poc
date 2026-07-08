---
name: laravel-conventions
description: >-
  Laravel 12 / PHP 8.4 のController・Service・Model・Migration・Form Request・Routeを
  実装/修正する時、PHPUnitテストを書く/実行する時、artisanコマンドを使う時、SDDフェーズ2(設計)で
  Laravelのクラス構成を検討する時、SDDフェーズ4(実装)でapp/・routes/・database/・config/・
  bootstrap/・tests/*.php 配下を変更する時に使う規約。
---

# Laravel 規約

対象: `app/**/*.php`, `routes/**/*.php`, `database/**/*.php`, `config/**/*.php`,
`tests/**/*.php`, `bootstrap/**/*.php` を扱う作業。返答は日本語。
指定された作業範囲以外のコードは修正しない。

## 環境

- Laravel 12 / PHP 8.4（Docker イメージ `laravel_app:1.0`）
- リポジトリ直下に Laravel アプリ（`app/`, `routes/` 等）
- **Docker で実行**（`run_debug.bat` で起動、http://localhost:8000）。`php artisan serve` は使用しない
- DB: PostgreSQL 18（Docker、localhost:5433）
- 認証: Laravel 標準（session / Breeze 等）

## アーキテクチャ

- Controller は薄く保ち、ビジネスロジックは Service に置く
- Form Request でバリデーション
- Eloquent Model + Migration で DB 操作
- 命名: PSR-4、`StudlyCase` クラス、`camelCase` メソッド

## ディレクトリ

| パス | 用途 |
|------|------|
| `app/Http/Controllers/` | コントローラ |
| `app/Services/` | ビジネスロジック |
| `app/Models/` | Eloquent モデル |
| `app/Http/Requests/` | Form Request |
| `routes/web.php` | Web ルート |
| `database/migrations/` | マイグレーション |
| `tests/Unit/` | 単体テスト |
| `tests/Feature/` | 機能テスト |

## インデックス設計（SDD フェーズ 2・Migration 作成時）

「重くなってから張る」のではなく、**要件に根拠があるものを初期 Migration で張り切る**。
逆に、根拠を specs の ID（UC-xx / VAL-xx / §5.2 画面項目 / FK）で言えないインデックスは
張らない（推測での先行最適化をしない。フェーズ 1 の「仮定の禁止」と同じ判断基準）。

| 種類 | 判断 | 根拠の書き方 |
|------|------|-------------|
| FK カラム | **必ず張る**（PostgreSQL は FK に自動でインデックスを張らない） | FK |
| 業務上の一意制約 | **必ず unique index で張る**（アプリ側バリデーションだけに頼らない） | VAL-xx |
| 画面の検索・絞り込み・並び順カラム | 張る | UC-xx / §5.2 の項目名 |
| 単独の低カーディナリティ列（status・フラグ等） | 原則張らない。複合インデックスの一部か部分インデックス（`WHERE status = '...'`）を検討 | 設計判断の理由を明記 |
| 「将来使うかも」 | 張らない | 根拠 ID が書けない = 推測 |

- 複合インデックスの列順は「**等値条件（=）で絞る列が先、範囲条件（<, BETWEEN）・ORDER BY の列が後**」。
  カーディナリティは同格の候補が並んだときのタイブレークに使う二次基準
  （カーディナリティ順を機械的に適用しない）
- 設計時は `02-design.md` §2.4 のインデックス表に「テーブル・対象列（列順どおり）・種類・根拠 ID」を記載する

## クエリ設計（一覧・参照系）

- 一覧・参照系はリレーション参照を設計時に洗い出し、Eager Loading（`with()`）の対象を
  `02-design.md` §2.2 のメソッド概要に明記する（N+1 クエリの予防。「重くなってから直す」にしない）
- 一覧画面は原則 `paginate()` を使う。全件表示にする場合は上限件数と根拠を設計に書く

## 削除設計

- テーブルごとに**論理削除か物理削除か**を設計時に決め、`02-design.md` §2.4 の表に記載する
- FK は削除時挙動（`cascadeOnDelete` / `restrictOnDelete` / `nullOnDelete`）を明示する
  （デフォルト任せ・暗黙の restrict にしない）
- 論理削除を選ぶ場合、unique 制約との干渉（削除済みレコードと同値の再登録可否）を設計で確認する

## テスト

- PHPUnit は **Service 等の単体テストに限定**
- E2E は Playwright が主。Controller の E2E 代替テストは原則書かない

### カバレッジ基準（実装完了の条件）

- **Service・Enum 等の PHPUnit 担当範囲は原則 100%**（到達できない行は理由を
  `03-test-plan.md` または PR に明記して許容する）
- **プロジェクト全体では 80% 以上**（`--min=80` で強制。未達はテスト失敗になる）
- Controller・Blade 等の未カバーは E2E（Playwright）の担当のため、PHPUnit で
  重複してテストしない

```bash
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage --min=80
```

## コマンド

artisan / PHPUnit は **Docker コンテナ内で実行** する。

```bash
run_debug.bat                 # 起動（起動確認込み）
run_debug.bat verify          # 起動確認のみ
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app vendor/bin/phpunit --filter <TestClass>
```

## Test ID アノテーション規約（突合チェックに必須）

各テストメソッドの直前の docコメントに、対応する CSV の Test ID を記載する。
この規約により `node scripts/sdd-lint-testid.mjs <slug>` でテスト実装の漏れを機械検出できる。

```php
/**
 * PHPUnit-inp-001: 任意項目 空入力で登録成功
 */
public function test_任意項目が空でも登録できる(): void
```

- docコメント形式: `* <Test ID>: <説明>`（Test ID は `PHPUnit-{category}-{nnn}` 形式）
- 1 つのテストメソッドに対して 1 行のアノテーション

## SDD 連携

- 実装判断は `docs/specs/<slug>/` を正とする
- 設計書（`02-design.md`）に記載されたクラス・メソッドから実装する
