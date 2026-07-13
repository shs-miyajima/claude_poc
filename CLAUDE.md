# claude_poc — Claude Code 向け設定

@AGENTS.md

## 規約（常時適用）

作業内容に関わらず、以下の規約を常に適用する。

@.claude/rules/windows-file-editing-safety.md
@.claude/rules/sdd-workflow.md

## ドメイン別規約（Skill・必要時のみ読み込み）

Laravel実装・フロント実装・Playwright E2E・PHPUnit・Vitest単体テスト・テストピラミッドの規約は、
常時読み込みではなく該当する作業を行う時にSkillとして参照する
（詳細は `.claude/skills/sdd-feature/SKILL.md` の「参照」を参照）。

| Skill | 用途 |
|-------|------|
| `.claude/skills/laravel-conventions/SKILL.md` | Laravel 規約 |
| `.claude/skills/frontend-vite-tailwind/SKILL.md` | フロント規約 |
| `.claude/skills/testing-pyramid/SKILL.md` | テストピラミッド（レイヤ分担） |
| `.claude/skills/testing-playwright/SKILL.md` | Playwright E2E 規約 |
| `.claude/skills/testing-phpunit/SKILL.md` | PHPUnit 規約 |
| `.claude/skills/testing-vitest/SKILL.md` | Vitest 規約 |
