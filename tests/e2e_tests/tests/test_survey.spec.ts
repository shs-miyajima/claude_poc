import { test, expect, Page } from '@playwright/test';
import { login, loginAsSuperUser, logout, registerAdmin, registerCompany, switchToCompany } from './helpers';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

async function setupCompanyWithAdmin(page: Page): Promise<{ companyCode: string; adminEmail: string }> {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const adminEmail = `admin-${suffix}@e2e.example.com`;

  await loginAsSuperUser(page);
  const companyCode = await registerCompany(page, companyName);
  await switchToCompany(page, companyName);
  await registerAdmin(page, '管理太郎', adminEmail, 'password1');

  return { companyCode, adminEmail };
}

async function fillSurveyBasics(
  page: Page,
  fields: { title: string; startDate: string; endDate: string; visibility: 'named' | 'anonymous' },
): Promise<void> {
  await page.getByTestId('survey-title-input').fill(fields.title);
  await page.getByTestId('survey-start-date-input').fill(fields.startDate);
  await page.getByTestId('survey-end-date-input').fill(fields.endDate);
  await page.getByTestId(`survey-visibility-${fields.visibility}`).check();
}

/** 一覧の「新規作成」から下書きアンケート(タイトルのみ)を作成し一覧に戻る */
async function createDraftSurvey(page: Page, title: string): Promise<void> {
  await page.getByTestId('nav-surveys').click();
  await page.getByTestId('survey-create-link').click();
  await fillSurveyBasics(page, { title, startDate: '2026-08-01', endDate: '2026-08-31', visibility: 'named' });
  await page.getByTestId('survey-submit').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);
}

// E2E-001-evt: アンケート新規作成ジャーニー(単一選択設問+選択肢3件)
test('企業ホーム画面からアンケート一覧を開き新規作成でタイトル・回答期間・記名/匿名と単一選択設問を入力して保存すると下書き状態で一覧・詳細に表示される', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  await page.getByTestId('home-surveys').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  await page.getByTestId('survey-create-link').click();
  await fillSurveyBasics(page, {
    title: '2026年度 従業員満足度調査',
    startDate: '2026-08-01',
    endDate: '2026-08-31',
    visibility: 'anonymous',
  });

  await page.getByTestId('question-add').click();
  const questionBlock = page.getByTestId('question-block').first();
  await questionBlock.getByTestId('question-type-single_choice').check();
  await questionBlock.getByTestId('question-body-input').fill('あなたの部署は?');
  await questionBlock.getByTestId('choice-add').click();
  const choiceInputs = questionBlock.getByTestId('choice-body-input');
  await choiceInputs.nth(0).fill('営業部');
  await choiceInputs.nth(1).fill('開発部');
  await choiceInputs.nth(2).fill('総務部');

  await page.getByTestId('survey-submit').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  const row = page.locator('tr', { hasText: '2026年度 従業員満足度調査' });
  await expect(row.getByText('下書き')).toBeVisible();

  await row.getByRole('link', { name: '2026年度 従業員満足度調査' }).click();
  await expect(page.getByTestId('survey-question-body')).toHaveText('あなたの部署は?');
  await expect(page.getByTestId('survey-choice')).toHaveCount(3);
});

// E2E-002-inp: アンケート作成フォームのスモーク(タイトル未入力)
test('アンケート作成画面でタイトルを空欄のまま登録ボタンを押すと必須エラーが表示され一覧へ遷移しない', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  await page.getByTestId('nav-surveys').click();
  await page.getByTestId('survey-create-link').click();
  await page.getByTestId('survey-start-date-input').fill('2026-08-01');
  await page.getByTestId('survey-end-date-input').fill('2026-08-31');
  await page.getByTestId('survey-visibility-named').check();
  await page.getByTestId('survey-submit').click();

  await expect(page.getByTestId('title-error')).toHaveText('タイトルを入力してください');
  await expect(page).not.toHaveURL(/\/company\/surveys$/);
});

// E2E-003-evt: アンケート編集ジャーニー(設問の↑並び替えを含む)
test('編集画面で問2を「↑」ボタンで問1より上に移動して保存すると詳細画面で問2が1番目・問1が2番目の順で表示される', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  await page.getByTestId('nav-surveys').click();
  await page.getByTestId('survey-create-link').click();
  await fillSurveyBasics(page, {
    title: '編集ジャーニー用アンケート',
    startDate: '2026-08-01',
    endDate: '2026-08-31',
    visibility: 'named',
  });

  await page.getByTestId('question-add').click();
  await page.getByTestId('question-block').nth(0).getByTestId('question-type-free_text').check();
  await page.getByTestId('question-block').nth(0).getByTestId('question-body-input').fill('問1');

  await page.getByTestId('question-add').click();
  await page.getByTestId('question-block').nth(1).getByTestId('question-type-free_text').check();
  await page.getByTestId('question-block').nth(1).getByTestId('question-body-input').fill('問2');

  await page.getByTestId('survey-submit').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  const row = page.locator('tr', { hasText: '編集ジャーニー用アンケート' });
  await row.getByRole('link', { name: '編集ジャーニー用アンケート' }).click();
  await page.getByTestId('survey-edit-link').click();

  await page.getByTestId('question-block').nth(1).getByTestId('question-move-up').click();
  await page.getByTestId('survey-submit').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  await row.getByRole('link', { name: '編集ジャーニー用アンケート' }).click();
  const bodies = page.getByTestId('survey-question-body');
  await expect(bodies).toHaveCount(2);
  await expect(bodies.nth(0)).toHaveText('問2');
  await expect(bodies.nth(1)).toHaveText('問1');
});

// E2E-004-evt: アンケート削除ジャーニー(confirmダイアログ)
test('詳細画面の削除ボタンを押しconfirmダイアログを承諾するとアンケート一覧からAが消える', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  const title = `削除ジャーニー用アンケート${uniqueSuffix()}`;
  await createDraftSurvey(page, title);

  const row = page.locator('tr', { hasText: title });
  await row.getByRole('link', { name: title }).click();

  page.once('dialog', (dialog) => {
    void dialog.accept();
  });
  await page.getByTestId('survey-delete-button').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  await expect(page.getByText(title)).toHaveCount(0);
});

// E2E-005-evt: アンケート公開ジャーニー
test('詳細画面の公開ボタンを押すと状態バッジが「公開」に変わり編集・削除・公開ボタンが表示されなくなる', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  const title = `公開ジャーニー用アンケート${uniqueSuffix()}`;
  await createDraftSurvey(page, title);

  const row = page.locator('tr', { hasText: title });
  await row.getByRole('link', { name: title }).click();

  await expect(page.getByTestId('survey-status')).toHaveText('下書き');
  await page.getByTestId('survey-publish-button').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  await row.getByRole('link', { name: title }).click();
  await expect(page.getByTestId('survey-status')).toHaveText('公開');
  await expect(page.getByTestId('survey-edit-link')).toHaveCount(0);
  await expect(page.getByTestId('survey-delete-button')).toHaveCount(0);
  await expect(page.getByTestId('survey-publish-button')).toHaveCount(0);
});

// E2E-006-evt: 代理作成ジャーニー(作成者非表示の確認を含む)
test('個別企業画面へ切替済みのスーパーユーザーがアンケートを新規作成すると切替中企業の一覧に表示され詳細に作成者情報が表示されない', async ({ page }) => {
  const suffix = uniqueSuffix();
  const companyName = `E2E企業${suffix}`;
  const title = `代理作成アンケート${suffix}`;

  await loginAsSuperUser(page);
  await registerCompany(page, companyName);
  await switchToCompany(page, companyName);

  await page.getByTestId('nav-surveys').click();
  await page.getByTestId('survey-create-link').click();
  await fillSurveyBasics(page, { title, startDate: '2026-08-01', endDate: '2026-08-31', visibility: 'named' });
  await page.getByTestId('survey-submit').click();
  await expect(page).toHaveURL(/\/company\/surveys$/);

  await expect(page.getByText(title)).toBeVisible();
  await expect(page.getByText('スーパーユーザー')).toHaveCount(0);

  const row = page.locator('tr', { hasText: title });
  await row.getByRole('link', { name: title }).click();
  await expect(page.getByText('スーパーユーザー')).toHaveCount(0);
});

// E2E-007-dsp: アンケート一覧ページング
test('企業に21件のアンケートが存在する状態で一覧の2ページ目リンクを押すと残り1件が表示される', async ({ page }) => {
  const { companyCode, adminEmail } = await setupCompanyWithAdmin(page);
  await page.getByTestId('logout-button').click();
  await login(page, companyCode, adminEmail, 'password1');

  const suffix = uniqueSuffix();
  for (let i = 1; i <= 21; i += 1) {
    await createDraftSurvey(page, `ページング検証アンケート${i}_${suffix}`);
  }

  await page.getByTestId('nav-surveys').click();
  await expect(page.getByTestId('survey-list').locator('tbody tr')).toHaveCount(20);

  await page.getByTestId('survey-pagination').getByRole('link', { name: '2' }).click();
  await expect(page.getByTestId('survey-list').locator('tbody tr')).toHaveCount(1);
});
