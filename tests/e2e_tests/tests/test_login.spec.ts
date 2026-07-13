import { test, expect } from '@playwright/test';
import {
  SUPER_USER_EMAIL,
  SUPER_USER_PASSWORD,
  login,
  loginAsSuperUser,
  logout,
  registerAdmin,
  registerCompany,
  switchToCompany,
} from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

// E2E-001-trn: スーパーユーザーログイン→全体画面
test('企業コードを空欄にしてログインすると全体(全企業)画面に遷移する', async ({ page }) => {
  await login(page, '', SUPER_USER_EMAIL, SUPER_USER_PASSWORD);

  await expect(page).toHaveURL(/\/super\/companies$/);
  await expect(page.getByTestId('company-list')).toBeVisible();
});

// E2E-002-auth: ログイン失敗表示
test('正しいメールに誤ったパスワードを入力するとエラーメッセージが表示されログイン画面に留まる', async ({ page }) => {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;

  await loginAsSuperUser(page);
  await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');
  await logout(page);

  await login(page, '', adminEmail, 'wrong-password');

  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByTestId('login-error')).toHaveText(
    '企業コード、メールアドレス、またはパスワードが正しくありません。',
  );
});

// E2E-015-auth: ログアウト後のリダイレクト
test('ログアウト後にブラウザバックで管理者向けトップへアクセスするとログイン画面へリダイレクトされる', async ({ page }) => {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;

  await loginAsSuperUser(page);
  const companyCode = await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');
  await logout(page);

  await login(page, companyCode, adminEmail, 'password1');
  await expect(page).toHaveURL(/\/company\/home$/);

  await logout(page);

  await page.goto('/company/home');
  await expect(page).toHaveURL(/\/login$/);
});

// E2E-016-auth: 未ログインアクセス時のリダイレクト
test('未ログイン状態で認証必須URLへ直接アクセスするとログイン画面へリダイレクトされる', async ({ page }) => {
  await page.goto('/company/home');

  await expect(page).toHaveURL(/\/login$/);
});
