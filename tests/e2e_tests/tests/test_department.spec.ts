import { test, expect } from '@playwright/test';
import { login, loginAsSuperUser, registerAdmin, registerCompany, switchToCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

async function setupCompanyWithAdmin(page: import('@playwright/test').Page): Promise<{ companyCode: string; adminEmail: string }> {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;

  await loginAsSuperUser(page);
  const companyCode = await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');

  return { companyCode, adminEmail };
}

// E2E-010-evt: 部署登録と選択肢反映
test('部署を登録した後にユーザー登録画面を開くと部署選択肢に表示される', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  const departmentName = `人事部${uniqueSuffix()}`;

  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  await page.getByTestId('nav-departments').click();
  await page.getByTestId('department-create-link').click();
  await page.getByTestId('department-name-input').fill(departmentName);
  await page.getByTestId('department-submit').click();
  await expect(page).toHaveURL(/\/company\/departments$/);

  await page.getByTestId('nav-users').click();
  await page.getByTestId('user-create-link').click();

  await expect(page.getByTestId('department-input').locator('option', { hasText: departmentName })).toHaveCount(1);
});

// E2E-011-inp: 部署登録フォームのスモーク
test('部署登録画面で部署名を空欄のまま送信すると必須エラーが表示され部署一覧へ遷移しない', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);

  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  await page.getByTestId('nav-departments').click();
  await page.getByTestId('department-create-link').click();
  await page.getByTestId('department-submit').click();

  await expect(page.getByTestId('name-error')).toHaveText('部署名は必須です。');
  await expect(page).not.toHaveURL(/\/company\/departments$/);
});
