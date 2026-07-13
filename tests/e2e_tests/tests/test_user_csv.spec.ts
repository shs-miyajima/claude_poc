import { test, expect } from '@playwright/test';
import { loginAsSuperUser, registerAdmin, registerCompany, registerDepartment, switchToCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

async function setupCompanyAdminDepartment(page: import('@playwright/test').Page): Promise<{ departmentName: string }> {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;
  const departmentName = `人事部${suffix}`;

  await loginAsSuperUser(page);
  await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');
  await registerDepartment(page, departmentName);

  return { departmentName };
}

// E2E-013-evt: CSV一括登録(正常系)
test('全行正常なCSV(データ3行)をアップロードすると3件登録されユーザー一覧に表示される', async ({ page }) => {
  const { departmentName } = await setupCompanyAdminDepartment(page);
  const suffix = uniqueSuffix();

  const header = '氏名,メールアドレス,初期パスワード,生年月日,入社年月日,性別,部署名';
  const rows = [
    `CSV太郎${suffix},csv1-${suffix}@e2e.example.com,password1,1990-04-01,2015-04-01,男性,${departmentName}`,
    `CSV次郎${suffix},csv2-${suffix}@e2e.example.com,password1,1991-04-01,2016-04-01,女性,${departmentName}`,
    `CSV三郎${suffix},csv3-${suffix}@e2e.example.com,password1,1992-04-01,2017-04-01,その他,${departmentName}`,
  ];
  const csvContent = [header, ...rows].join('\n');

  await page.getByTestId('nav-users-csv').click();
  await page.getByTestId('csv-file-input').setInputFiles({
    name: 'users.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from(csvContent, 'utf-8'),
  });
  await page.getByTestId('csv-submit').click();

  await expect(page).toHaveURL(/\/company\/users$/);
  await expect(page.getByTestId('status-message')).toHaveText('3件登録しました');
  await expect(page.getByText(`CSV太郎${suffix}`)).toBeVisible();
  await expect(page.getByText(`CSV次郎${suffix}`)).toBeVisible();
  await expect(page.getByText(`CSV三郎${suffix}`)).toBeVisible();
});

// E2E-014-evt: CSV一括登録(異常系)
test('5行中4行目のメールが重複するCSVをアップロードすると1件も登録されずエラー行一覧が表示される', async ({ page }) => {
  const { departmentName } = await setupCompanyAdminDepartment(page);
  const suffix = uniqueSuffix();
  const dupEmail = `csv-dup-${suffix}@e2e.example.com`;

  const header = '氏名,メールアドレス,初期パスワード,生年月日,入社年月日,性別,部署名';
  // CSVの行番号はヘッダーを1行目として数える。データ1行目(dupEmailの初出)がCSV上2行目、
  // データ3行目(dupEmailの再出現)がCSV上4行目となり、そこでファイル内重複が検出される。
  const emails = [
    dupEmail,
    `csvuser2-${suffix}@e2e.example.com`,
    dupEmail,
    `csvuser4-${suffix}@e2e.example.com`,
    `csvuser5-${suffix}@e2e.example.com`,
  ];
  const rows = emails.map(
    (email, idx) => `CSV行${idx + 1}_${suffix},${email},password1,1990-04-01,2015-04-01,男性,${departmentName}`,
  );
  const csvContent = [header, ...rows].join('\n');

  await page.getByTestId('nav-users-csv').click();
  await page.getByTestId('csv-file-input').setInputFiles({
    name: 'users.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from(csvContent, 'utf-8'),
  });
  await page.getByTestId('csv-submit').click();

  await expect(page).not.toHaveURL(/\/company\/users$/);
  await expect(page.getByTestId('csv-error-list')).toContainText('4行目: メールアドレスがファイル内で重複しています。');

  await page.getByTestId('nav-users').click();
  await expect(page.getByText(dupEmail)).toHaveCount(0);
});
