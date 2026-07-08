---
name: frontend-vite-tailwind
description: >-
  Blade テンプレート・Vite エントリ・JavaScript(素のJS)・Tailwind CSSを実装/修正する時、
  data-testid を付与する時、SDDフェーズ2(設計)で画面構成を検討する時、SDDフェーズ4(実装)で
  resources/views・resources/js・resources/css・vite.config.js を変更する時に使う規約。
---

# フロントエンド規約（Blade / Vite / JavaScript / Tailwind）

対象: `resources/views/**/*.blade.php`, `resources/js/**/*.js`, `resources/css/**/*`,
`vite.config.js` を扱う作業。返答は日本語。
指定された作業範囲以外のコードは修正しない。

> Tailwind は v4（`@tailwindcss/vite` プラグイン）のため、`tailwind.config.js` /
> `postcss.config.js` は**存在しない**。テーマ等の設定は `resources/css/app.css` 内の
> `@theme` / `@source` で行う。

## スタック

```
Blade テンプレート
  ↓
Vite（ビルド・バンドラ）
  ↓
axios + 素の JavaScript
  ↓
Tailwind CSS
```

- Laravel Vite Plugin で Blade と Vite を統合
- **フロント開発は純粋な JavaScript**（TypeScript は使用しない）
- TypeScript は Playwright E2E 専用

## ディレクトリ

| パス | 用途 |
|------|------|
| `resources/views/` | Blade テンプレート |
| `resources/js/` | Vite エントリ・axios・JS モジュール |
| `resources/css/` | Tailwind エントリ CSS |
| `vite.config.js` | Vite 設定 |

## コーディング

- Blade に `@vite(['resources/css/app.css', 'resources/js/app.js'])` でアセット読み込み
- axios で API 呼び出し（CSRF トークン設定を忘れない）
- DOM 操作は素の JavaScript（フレームワーク不使用）
- スタイルは Tailwind ユーティリティクラスを優先

## 通信中 UI・二重送信防止・エラー表示（プロジェクト標準 — TBD）

> 本節は**未確定（TBD）のプロジェクト標準**。この標準が必要になる最初の機能の設計フェーズで、
> open-questions としてユーザーに確認して方式を確定し、承認を得てから本節を更新する
> （手順: `.claude/rules/sdd-workflow.md`「プロジェクト規約への昇格」）。

- 二重送信防止の標準方式（送信中のボタン非活性 / ローディングオーバーレイ 等）: **TBD**
- 通信エラーの画面表示の標準方式（アラート領域 / トースト等の方式・文言方針）: **TBD**
- **確定するまで、機能ごとに独自方式を発明しない**。フォーム送信・axios 通信を含む画面を
  設計する際は、必ず標準を確定させてから設計・実装に進む
- サーバー側の競合対策（`02-design.md` §5.1）と対になる観点のため、設計時にセットで確認する

## data-testid（E2E 連携）

- E2E（Playwright）から参照される要素には **`data-testid` を付与する**。
  対象: 一覧行、ボタン、バッジ、アラート、エラー領域、フォーム等、テストが操作・検証する要素
- 命名はケバブケースで「対象-種別（-状態）」とする
  （例: `item-row`, `status-badge`, `status-button-approved`, `error-icon`）
- Tailwind クラスや表示文言は変わり得るため、E2E のセレクタとして使わせない
  （`data-testid` を付けておくことでテストの安定性を担保する）
- **プレフィックスは操作主体・権限単位で分ける**。`status-button-*` のような
  ワイルドカードセレクタ（例: `[data-testid^="status-button-"]`）が既存 E2E に
  存在する場合、新しいステータス値をそのまま `status-button-<status>` に追加すると、
  対象外の操作主体（例: 管理者専用ボタンの中に一般ユーザー専用ボタンが混入）まで
  拾ってしまう。操作主体が異なるボタンには **別プレフィックス**
  （例: 一般ユーザー専用なら `user-action-button` のような別名）を付与する
- 新しい `data-testid` を追加する際は、既存の E2E で使われているワイルドカード
  セレクタ（`^=`, `*=` 等）と衝突しないか `tests/e2e_tests/tests/` を確認する

## 開発

```bash
npm run dev    # Vite 開発サーバー
npm run build  # 本番ビルド
```

## SDD 連携

- 画面・JS 変更は `02-design.md` の §3 に記載してから実装
- 新規 JS ファイルは `meta.yaml` の `frontend.js_files` に追記
