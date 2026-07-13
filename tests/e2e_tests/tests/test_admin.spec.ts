import { test, expect } from '@playwright/test';
import { login, loginAsSuperUser, logout, registerCompany, switchToCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

// E2E-006-evt: 管理者登録からログインまでの通しジャーニー
test('管理者を登録しログアウト後に当該管理者でログインすると管理者向けトップに遷移する', async ({ page }) => {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;

  await loginAsSuperUser(page);
  const companyCode = await registerCompany(page, companyName);
  await switchToCompany(page, companyName);

  await page.getByTestId('nav-admins').click();
  await page.getByTestId('admin-create-link').click();
  await page.getByTestId('name-input').fill('管理太郎');
  await page.getByTestId('email-input').fill(adminEmail);
  await page.getByTestId('password-input').fill('password1');
  await page.getByTestId('admin-submit').click();
  await expect(page).toHaveURL(/\/company\/admins$/);

  await logout(page);

  await login(page, companyCode, adminEmail, 'password1');

  await expect(page).toHaveURL(/\/company\/home$/);
  await expect(page.getByTestId('company-home')).toBeVisible();
});

// E2E-007-inp: 管理者登録フォームのスモーク
test('管理者登録画面でメールアドレスを空欄のまま送信すると必須エラーが表示され管理者一覧へ遷移しない', async ({ page }) => {
  const companyName = `E2E企業${uniqueSuffix()}`;

  await loginAsSuperUser(page);
  await registerCompany(page, companyName);
  await switchToCompany(page, companyName);

  await page.getByTestId('nav-admins').click();
  await page.getByTestId('admin-create-link').click();
  await page.getByTestId('name-input').fill('管理太郎');
  await page.getByTestId('password-input').fill('password1');
  await page.getByTestId('admin-submit').click();

  await expect(page.getByTestId('email-error')).toHaveText('メールアドレスは必須です。');
  await expect(page).not.toHaveURL(/\/company\/admins$/);
});
