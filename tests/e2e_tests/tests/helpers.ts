import { Page, expect } from '@playwright/test';

export const SUPER_USER_EMAIL = process.env.SUPER_USER_EMAIL ?? 'super@example.com';
export const SUPER_USER_PASSWORD = process.env.SUPER_USER_PASSWORD ?? 'super1234';

export async function login(page: Page, companyCode: string, email: string, password: string): Promise<void> {
  await page.goto('/login');
  await page.getByTestId('company-code-input').fill(companyCode);
  await page.getByTestId('email-input').fill(email);
  await page.getByTestId('password-input').fill(password);
  await page.getByTestId('login-submit').click();
}

export async function loginAsSuperUser(page: Page): Promise<void> {
  await login(page, '', SUPER_USER_EMAIL, SUPER_USER_PASSWORD);
  await expect(page).toHaveURL(/\/super\/companies$/);
}

export async function logout(page: Page): Promise<void> {
  await page.getByTestId('logout-button').click();
  await expect(page).toHaveURL(/\/login$/);
}

/**
 * スーパーユーザーとしてログイン済みの状態で企業を登録し、企業一覧に戻る。
 * 一覧行から採番された企業コードを取得して返す。
 */
export async function registerCompany(page: Page, name: string): Promise<string> {
  await page.getByTestId('company-create-link').click();
  await page.getByTestId('company-name-input').fill(name);
  await page.getByTestId('company-submit').click();
  await expect(page).toHaveURL(/\/super\/companies$/);

  const row = page.locator('tr', { hasText: name });
  const code = (await row.locator('td').first().innerText()).trim();

  return code;
}

/** 企業一覧の指定行から個別企業画面へ切り替える */
export async function switchToCompany(page: Page, companyName: string): Promise<void> {
  const row = page.locator('tr', { hasText: companyName });
  await row.getByRole('button', { name: '個別企業画面へ' }).click();
  await expect(page).toHaveURL(/\/company\/home$/);
}

/** 個別企業画面(スーパーユーザー)で管理者を登録する */
export async function registerAdmin(page: Page, name: string, email: string, password: string): Promise<void> {
  await page.getByTestId('nav-admins').click();
  await page.getByTestId('admin-create-link').click();
  await page.getByTestId('name-input').fill(name);
  await page.getByTestId('email-input').fill(email);
  await page.getByTestId('password-input').fill(password);
  await page.getByTestId('admin-submit').click();
  await expect(page).toHaveURL(/\/company\/admins$/);
}

/** 部署を登録する(管理者 or スーパーユーザーの個別企業画面) */
export async function registerDepartment(page: Page, name: string): Promise<void> {
  await page.getByTestId('nav-departments').click();
  await page.getByTestId('department-create-link').click();
  await page.getByTestId('department-name-input').fill(name);
  await page.getByTestId('department-submit').click();
  await expect(page).toHaveURL(/\/company\/departments$/);
}

export type UserFields = {
  name: string;
  email: string;
  password: string;
  birthDate: string;
  hireDate: string;
  gender: '男性' | '女性' | 'その他';
  departmentName: string;
};

/** ユーザーを登録する(管理者 or スーパーユーザーの個別企業画面) */
export async function registerUser(page: Page, fields: UserFields): Promise<void> {
  await page.getByTestId('nav-users').click();
  await page.getByTestId('user-create-link').click();
  await page.getByTestId('name-input').fill(fields.name);
  await page.getByTestId('email-input').fill(fields.email);
  await page.getByTestId('password-input').fill(fields.password);
  await page.getByTestId('birth-date-input').fill(fields.birthDate);
  await page.getByTestId('hire-date-input').fill(fields.hireDate);
  await page.getByTestId('gender-input').selectOption({ label: fields.gender });
  await page.getByTestId('department-input').selectOption({ label: fields.departmentName });
  await page.getByTestId('user-submit').click();
  await expect(page).toHaveURL(/\/company\/users$/);
}
