---
name: testing-vitest
description: >-
  VitestでJavaScript単体テストを実装/修正/実行する時、resources/js配下の純関数テストを書く時、
  SDDフェーズ3(テスト設計)で03-test-plan-vitest.csvを作成する時、SDDフェーズ4でVitestを書く時に使う規約。
---

# Vitest 規約

返答は日本語。指定された作業範囲以外のコードは修正しない。

## 対象

- `resources/js/` の **JavaScript**:
  - ユーティリティ関数
  - DOM 操作ロジック
  - axios ラッパー等
- **TypeScript は使用しない**（E2E の Playwright のみ TS）

## 配置

- テストファイル: 対象 JS と同階層に `*.test.js`、または `resources/js/__tests__/`
- 設定: プロジェクトルートの `vitest.config.js`

## 実行

```bash
npm run test          # 全テスト
npm run test:watch    # ウォッチモード（設定時）
npx vitest run <path> # 個別実行
```

## 方針

- レイヤ分担の正本: `.claude/skills/testing-pyramid/SKILL.md`
- **E2E は主テストではない**。重要ジャーニーの正常系とビジネスクリティカルな異常系に限定する
- バリデーション・境界値の **網羅** は PHPUnit / Vitest の責務。E2E の `inp` は **1 画面 1 スモーク**＋クリティカル異常のみ
- DOM 依存のテストは jsdom 等の Vitest 環境を使用（可能なら Vitest へ）
- Service 相当のサーバー側ロジックは PHPUnit でテスト
- 表示ロジックは特殊表示や異常系だけでなく、通常データが正しく描画される正常系も確認する
- API ラッパーは URL / payload の正常系と、エラーを呼び出し側へ伝播するケースを確認する
- 複数ステータス・複数表示条件は、期待するラベルや CSS クラスが異なる場合は個別ケースに分ける

## Test ID アノテーション規約（突合チェックに必須）

各 `test(...)` / `it(...)` の直前行に、対応する CSV（`03-test-plan-vitest.csv`）の Test ID を
以下の形式でコメントする。`npm run lint:sdd:testid -- <slug>` が計画と実装の突合に使用する。

```javascript
// VT-001-dyn: formatDate — ISO 文字列を YYYY/MM/DD に変換する
test('formatDate は ISO 文字列を YYYY/MM/DD に変換する', () => {
```

- コメント形式: `// <Test ID>: <説明>`（Test ID は `VT-{nnn}-{カテゴリ}` 形式。例: `VT-001-dyn`。
  カテゴリは CSV のカテゴリ列と一致させる。正本: `.claude/skills/testing-pyramid/SKILL.md`「Test ID 形式」）
- 1 つのテストに対して 1 行のアノテーション（複数の Test ID を 1 テストに紐付けない）

## SDD 連携

- フェーズ 3 で Vitest 対象がある場合、`03-test-plan.md` §6 に記載
- 実装後 `meta.yaml` の `vitest.test_files` に追記
