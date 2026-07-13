---
name: frontend-vite-tailwind
description: >-
  Blade / Vite / JavaScript / Tailwind の画面・フロント実装を行う時、resources/views や
  resources/js を変更する時、SDDフェーズ2(設計)で画面構成を検討する時、SDDフェーズ4(実装)で
  フロントを書く時に使う規約。
---

# フロントエンド規約

返答は日本語。指定された作業範囲以外のコードは修正しない。

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

## 開発

```bash
npm run dev    # Vite 開発サーバー
npm run build  # 本番ビルド
```

## SDD 連携

- 画面・JS 変更は `02-design.md` の §3 に記載してから実装
- 新規 JS ファイルは `meta.yaml` の `frontend.js_files` に追記
