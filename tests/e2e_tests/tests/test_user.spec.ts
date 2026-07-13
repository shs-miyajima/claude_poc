import { test, expect } from '@playwright/test';
import { login, loginAsSuperUser, logout, registerAdmin, registerCompany, registerDepartment, registerUser, switchToCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

async function setupCompanyAdminDepartment(page: import('@playwright/test').Page): Promise<{
  companyCode: string;
  adminEmail: string;
  departmentName: string;
}> {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;
  const departmentName = `人事部${suffix}`;

  await loginAsSuperUser(page);
  const companyCode = await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');
  await logout(page);

  await login(page, companyCode, adminEmail, 'password1');
  await registerDepartment(page, departmentName);

  return { companyCode, adminEmail, departmentName };
}

// E2E-008-evt: ユーザー登録からログインまでの通しジャーニー
test('ユーザーを登録しログアウト後に当該ユーザーでログインするとユーザー向けトップに遷移する', async ({ page }) => {
  const { companyCode, departmentName } = await setupCompanyAdminDepartment(page);
  const suffix = uniqueSuffix();
  const userEmail = `user-${suffix}@e2e.example.com`;

  await registerUser(page, {
    name: '山田太郎',
    email: userEmail,
    password: 'password1',
    birthDate: '1990-04-01',
    hireDate: '2015-04-01',
    gender: '男性',
    departmentName,
  });

  await logout(page);

  await login(page, companyCode, userEmail, 'password1');

  await expect(page).toHaveURL(/\/home$/);
  await expect(page.getByTestId('user-home')).toBeVisible();
});

// E2E-009-inp: ユーザー登録フォームのスモーク
test('ユーザー登録画面で生年月日を空欄のまま送信すると必須エラーが表示されユーザー一覧へ遷移しない', async ({ page }) => {
  const { departmentName } = await setupCompanyAdminDepartment(page);

  await page.getByTestId('nav-users').click();
  await page.getByTestId('user-create-link').click();
  await page.getByTestId('name-input').fill('山田太郎');
  await page.getByTestId('email-input').fill(`user-${uniqueSuffix()}@e2e.example.com`);
  await page.getByTestId('password-input').fill('password1');
  await page.getByTestId('hire-date-input').fill('2015-04-01');
  await page.getByTestId('gender-input').selectOption({ label: '男性' });
  await page.getByTestId('department-input').selectOption({ label: departmentName });
  await page.getByTestId('user-submit').click();

  await expect(page.getByTestId('birth-date-error')).toHaveText('生年月日は必須です。');
  await expect(page).not.toHaveURL(/\/company\/users$/);
});

// E2E-012-evt: ユーザー無効化の確認ダイアログ
test('ユーザー一覧の無効化ボタンを押し確認ダイアログを承諾するとユーザーが無効化される', async ({ page }) => {
  const { departmentName } = await setupCompanyAdminDepartment(page);
  const suffix = uniqueSuffix();
  const userName = `無効化太郎${suffix}`;

  await registerUser(page, {
    name: userName,
    email: `user-${suffix}@e2e.example.com`,
    password: 'password1',
    birthDate: '1990-04-01',
    hireDate: '2015-04-01',
    gender: '男性',
    departmentName,
  });

  page.on('dialog', (dialog) => dialog.accept());

  const row = page.locator('tr', { hasText: userName });
  await row.getByRole('button', { name: '無効化' }).click();

  await expect(row.getByText('無効', { exact: true })).toBeVisible();
});

// E2E-017-dsp: ユーザー一覧ページング
test('21名のユーザーが存在する場合2ページ目のリンクを押すと残り1件が表示される', async ({ page }) => {
  const { departmentName } = await setupCompanyAdminDepartment(page);
  const suffix = uniqueSuffix();

  const header = '氏名,メールアドレス,初期パスワード,生年月日,入社年月日,性別,部署名';
  const rows: string[] = [];
  for (let i = 1; i <= 21; i++) {
    rows.push(`ページ利用者${i}_${suffix},page${i}-${suffix}@e2e.example.com,password1,1990-04-01,2015-04-01,男性,${departmentName}`);
  }
  const csvContent = [header, ...rows].join('\n');

  await page.getByTestId('nav-users-csv').click();
  await page.getByTestId('csv-file-input').setInputFiles({
    name: 'users.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from(csvContent, 'utf-8'),
  });
  await page.getByTestId('csv-submit').click();
  await expect(page).toHaveURL(/\/company\/users$/);

  await page.getByTestId('nav-users').click();
  await expect(page.getByTestId('user-list').locator('tbody tr')).toHaveCount(20);

  await page.locator('a', { hasText: '2' }).last().click();
  await expect(page).toHaveURL(/page=2/);
  await expect(page.getByTestId('user-list').locator('tbody tr')).toHaveCount(1);
});
