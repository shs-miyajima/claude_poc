import { test, expect } from '@playwright/test';
import { loginAsSuperUser, registerCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

// E2E-003-inp: 企業登録フォームのスモーク
test('企業登録画面で企業名を空欄のまま送信すると必須エラーが表示され企業一覧へ遷移しない', async ({ page }) => {
  await loginAsSuperUser(page);

  await page.getByTestId('company-create-link').click();
  await page.getByTestId('company-submit').click();

  await expect(page.getByTestId('name-error')).toHaveText('企業名は必須です。');
  await expect(page).not.toHaveURL(/\/super\/companies$/);
});

// E2E-004-evt: 企業登録ジャーニー
test('企業名を入力して登録すると企業一覧に企業名と採番された企業コードが表示される', async ({ page }) => {
  const companyName = `株式会社サンプル${uniqueSuffix()}`;

  await loginAsSuperUser(page);
  const code = await registerCompany(page, companyName);

  await expect(page.locator('tr', { hasText: companyName })).toBeVisible();
  expect(code).toMatch(/^C\d{4}$/);
});

// E2E-005-evt: 個別企業画面への切替
test('企業一覧から切替ボタンを押すと個別企業画面に遷移し全体画面へ戻れる', async ({ page }) => {
  const companyName = `切替企業${uniqueSuffix()}`;

  await loginAsSuperUser(page);
  await registerCompany(page, companyName);

  const row = page.locator('tr', { hasText: companyName });
  await row.getByRole('button', { name: '個別企業画面へ' }).click();

  await expect(page).toHaveURL(/\/company\/home$/);
  await expect(page.getByTestId('nav-admins')).toBeVisible();

  await page.getByTestId('exit-company').click();
  await expect(page).toHaveURL(/\/super\/companies$/);
});
