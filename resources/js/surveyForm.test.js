import { describe, it, expect, beforeEach, vi } from 'vitest';
import { initSurveyForm } from './surveyForm.js';

function choiceBlockHtml(qIndex, cIndex, body = '') {
  return `
    <div class="choice-block" data-testid="choice-block">
      <input type="text" name="questions[${qIndex}][choices][${cIndex}][body]" data-testid="choice-body-input" value="${body}">
      <button type="button" data-testid="choice-remove">削除</button>
    </div>
  `;
}

function questionBlockHtml({ index, type = '', required = false, choices = null }) {
  const choicesArray = choices ?? (type === 'single_choice' || type === 'multiple_choice' ? ['選択肢1', '選択肢2'] : []);
  const choicesHtml = choicesArray.map((body, cIndex) => choiceBlockHtml(index, cIndex, body)).join('');
  const showChoices = type === 'single_choice' || type === 'multiple_choice';
  const showScale = type === 'scale';

  return `
    <div class="question-block" data-testid="question-block">
      <button type="button" data-testid="question-move-up">up</button>
      <button type="button" data-testid="question-move-down">down</button>
      <button type="button" data-testid="question-remove">remove</button>
      <input type="text" name="questions[${index}][body]" data-testid="question-body-input" value="">
      <input type="radio" name="questions[${index}][question_type]" value="single_choice" data-testid="question-type-single_choice" ${type === 'single_choice' ? 'checked' : ''}>
      <input type="radio" name="questions[${index}][question_type]" value="multiple_choice" data-testid="question-type-multiple_choice" ${type === 'multiple_choice' ? 'checked' : ''}>
      <input type="radio" name="questions[${index}][question_type]" value="free_text" data-testid="question-type-free_text" ${type === 'free_text' ? 'checked' : ''}>
      <input type="radio" name="questions[${index}][question_type]" value="scale" data-testid="question-type-scale" ${type === 'scale' ? 'checked' : ''}>
      <input type="radio" name="questions[${index}][is_required]" value="1" data-testid="question-required-required" ${required ? 'checked' : ''}>
      <input type="radio" name="questions[${index}][is_required]" value="0" data-testid="question-required-optional" ${!required ? 'checked' : ''}>
      <div data-testid="choices-section" ${showChoices ? '' : 'hidden'}>
        <div data-testid="choices-container">${choicesHtml}</div>
        <button type="button" data-testid="choice-add">add</button>
      </div>
      <div data-testid="scale-section" ${showScale ? '' : 'hidden'}>
        <input type="text" name="questions[${index}][scale_min_label]" data-testid="scale-min-label-input" value="">
        <input type="text" name="questions[${index}][scale_max_label]" data-testid="scale-max-label-input" value="">
      </div>
    </div>
  `;
}

function questionTemplateHtml() {
  return `<template data-testid="question-template">${questionBlockHtml({ index: 'NEW', type: 'single_choice', choices: ['', ''] })}</template>`;
}

function choiceTemplateHtml() {
  return `<template data-testid="choice-template">${choiceBlockHtml('NEW', 'NEW')}</template>`;
}

function buildForm(questionBlocks) {
  document.body.innerHTML = `
    <form data-survey-form>
      <div data-testid="questions-container">${questionBlocks.map(questionBlockHtml).join('')}</div>
      <button type="button" data-testid="question-add">add-question</button>
      ${questionTemplateHtml()}
      ${choiceTemplateHtml()}
    </form>
  `;
  initSurveyForm(document);
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('surveyForm', () => {
  // VT-001-dyn: 設問ブロックの追加 — 「設問を追加」ボタンで設問ブロックが1つ増える
  it('設問を追加ボタンを押すと設問ブロックが1つ増える', () => {
    buildForm([{ index: 0, type: 'free_text' }]);

    document.querySelector('[data-testid="question-add"]').click();

    expect(document.querySelectorAll('[data-testid="question-block"]')).toHaveLength(2);
  });

  // VT-002-dyn: 設問ブロックの削除 — confirm()をtrueにモックし削除ボタンで対象ブロックが除去される
  it('confirmを承諾して削除ボタンを押すと対象の設問ブロックが除去される', () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    buildForm([{ index: 0, type: 'free_text' }, { index: 1, type: 'free_text' }]);

    document.querySelectorAll('[data-testid="question-remove"]')[0].click();

    expect(document.querySelectorAll('[data-testid="question-block"]')).toHaveLength(1);
  });

  // VT-003-dyn: 設問ブロックの上移動 — 2つ目の「↑」ボタンでDOM順序が問2・問1・問3になる
  it('2つ目の設問の上移動ボタンを押すと順序が入れ替わる', () => {
    buildForm([
      { index: 0, type: 'free_text', choices: [] },
      { index: 1, type: 'free_text', choices: [] },
      { index: 2, type: 'free_text', choices: [] },
    ]);
    const blocks = document.querySelectorAll('[data-testid="question-block"]');
    blocks[0].querySelector('[data-testid="question-body-input"]').value = '問1';
    blocks[1].querySelector('[data-testid="question-body-input"]').value = '問2';
    blocks[2].querySelector('[data-testid="question-body-input"]').value = '問3';

    blocks[1].querySelector('[data-testid="question-move-up"]').click();

    const bodies = [...document.querySelectorAll('[data-testid="question-body-input"]')].map((el) => el.value);
    expect(bodies).toEqual(['問2', '問1', '問3']);

    const freeTextChecked = [...document.querySelectorAll('[data-testid="question-type-free_text"]')].map((el) => el.checked);
    expect(freeTextChecked).toEqual([true, true, true]);
  });

  // VT-004-dyn: 設問ブロックの下移動 — 2つ目の「↓」ボタンでDOM順序が問1・問3・問2になる
  it('2つ目の設問の下移動ボタンを押すと順序が入れ替わる', () => {
    buildForm([
      { index: 0, type: 'free_text', choices: [] },
      { index: 1, type: 'free_text', choices: [] },
      { index: 2, type: 'free_text', choices: [] },
    ]);
    const blocks = document.querySelectorAll('[data-testid="question-block"]');
    blocks[0].querySelector('[data-testid="question-body-input"]').value = '問1';
    blocks[1].querySelector('[data-testid="question-body-input"]').value = '問2';
    blocks[2].querySelector('[data-testid="question-body-input"]').value = '問3';

    blocks[1].querySelector('[data-testid="question-move-down"]').click();

    const bodies = [...document.querySelectorAll('[data-testid="question-body-input"]')].map((el) => el.value);
    expect(bodies).toEqual(['問1', '問3', '問2']);

    const freeTextChecked = [...document.querySelectorAll('[data-testid="question-type-free_text"]')].map((el) => el.checked);
    expect(freeTextChecked).toEqual([true, true, true]);
  });

  // VT-005-dyn: 設問name属性の再採番 — 3件中1件目を削除すると残りがquestions[0]・questions[1]になる
  it('1つ目の設問を削除すると残りのname属性がquestions[0]・questions[1]に振り直される', () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    buildForm([
      { index: 0, type: 'free_text', choices: [] },
      { index: 1, type: 'free_text', choices: [] },
      { index: 2, type: 'free_text', choices: [] },
    ]);

    document.querySelectorAll('[data-testid="question-remove"]')[0].click();

    const names = [...document.querySelectorAll('[data-testid="question-body-input"]')].map((el) => el.name);
    expect(names).toEqual(['questions[0][body]', 'questions[1][body]']);
  });

  // VT-006-dyn: 選択肢入力欄の追加 — 選択肢が2件の状態で追加すると3件になる
  it('選択肢を追加ボタンを押すと選択肢入力欄が1つ増える', () => {
    buildForm([{ index: 0, type: 'single_choice' }]);

    document.querySelector('[data-testid="choice-add"]').click();

    expect(document.querySelectorAll('[data-testid="choice-body-input"]')).toHaveLength(3);
  });

  // VT-007-dyn: 選択肢入力欄の削除 — 選択肢が3件の状態で1件目を削除すると2件になる
  it('選択肢が3件のとき1つ目の削除ボタンを押すと対象の選択肢が除去される', () => {
    buildForm([{ index: 0, type: 'single_choice', choices: ['選択肢1', '選択肢2', '選択肢3'] }]);

    document.querySelectorAll('[data-testid="choice-remove"]')[0].click();

    expect(document.querySelectorAll('[data-testid="choice-body-input"]')).toHaveLength(2);
  });

  // VT-008-dyn: 選択肢name属性の再採番 — 3件中1件目を削除すると残りがchoices[0]・choices[1]になる
  it('選択肢を削除すると残りのname属性がchoices[0]・choices[1]に振り直される', () => {
    buildForm([{ index: 0, type: 'single_choice', choices: ['選択肢1', '選択肢2', '選択肢3'] }]);

    document.querySelectorAll('[data-testid="choice-remove"]')[0].click();

    const names = [...document.querySelectorAll('[data-testid="choice-body-input"]')].map((el) => el.name);
    expect(names).toEqual(['questions[0][choices][0][body]', 'questions[0][choices][1][body]']);
  });

  // VT-009-dyn: 設問形式変更時の選択肢欄表示(単一選択) — ラジオボタン変更で選択肢入力欄が表示状態になる
  it('設問形式で単一選択を選択すると選択肢入力欄が表示される', () => {
    buildForm([{ index: 0, type: '', choices: [] }]);
    const radio = document.querySelector('[data-testid="question-type-single_choice"]');

    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));

    expect(document.querySelector('[data-testid="choices-section"]').hidden).toBe(false);
  });

  // VT-010-dyn: 設問形式変更時の入力欄非表示(自由記述) — ラジオボタン変更で選択肢・段階評価欄がいずれも非表示になる
  it('設問形式で自由記述を選択すると選択肢欄・段階評価ラベル欄が非表示になる', () => {
    buildForm([{ index: 0, type: 'single_choice' }]);
    const radio = document.querySelector('[data-testid="question-type-free_text"]');

    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));

    expect(document.querySelector('[data-testid="choices-section"]').hidden).toBe(true);
    expect(document.querySelector('[data-testid="scale-section"]').hidden).toBe(true);
  });

  // VT-011-dyn: 設問形式変更時のラベル欄表示(段階評価) — ラジオボタン変更で段階評価ラベル入力欄が表示状態になる
  it('設問形式で段階評価を選択すると段階評価の両端ラベル入力欄が表示される', () => {
    buildForm([{ index: 0, type: '', choices: [] }]);
    const radio = document.querySelector('[data-testid="question-type-scale"]');

    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));

    expect(document.querySelector('[data-testid="scale-section"]').hidden).toBe(false);
  });

  // VT-012-dyn: 選択肢削除ボタンの無効化(下限2件) — 選択肢が2件のとき両方の削除ボタンにdisabledが付与される
  it('選択肢が2件のとき両方の削除ボタンが無効化される', () => {
    buildForm([{ index: 0, type: 'single_choice', choices: ['選択肢1', '選択肢2'] }]);

    const removeButtons = document.querySelectorAll('[data-testid="choice-remove"]');
    expect(removeButtons[0].disabled).toBe(true);
    expect(removeButtons[1].disabled).toBe(true);
  });

  // VT-013-dyn: 選択肢削除ボタン無効化時のクリック無視 — disabled状態で削除ボタンを押しても選択肢は2件のまま
  it('選択肢が2件で削除ボタンがdisabledのとき押しても選択肢は変化しない', () => {
    buildForm([{ index: 0, type: 'single_choice', choices: ['選択肢1', '選択肢2'] }]);

    document.querySelectorAll('[data-testid="choice-remove"]')[0].click();

    expect(document.querySelectorAll('[data-testid="choice-body-input"]')).toHaveLength(2);
  });

  // VT-014-dyn: 選択肢削除ボタンの再有効化 — 選択肢を追加し3件になると全ての削除ボタンのdisabledが解除される
  it('選択肢を追加して3件になると全ての削除ボタンが再有効化される', () => {
    buildForm([{ index: 0, type: 'single_choice', choices: ['選択肢1', '選択肢2'] }]);

    document.querySelector('[data-testid="choice-add"]').click();

    const removeButtons = document.querySelectorAll('[data-testid="choice-remove"]');
    expect(removeButtons).toHaveLength(3);
    removeButtons.forEach((button) => {
      expect(button.disabled).toBe(false);
    });
  });

  // VT-015-dyn: 設問追加時の初期値(単一選択) — 「設問を追加」で追加した設問ブロックは単一選択があらかじめ選択され選択肢入力欄が表示される
  it('設問を追加ボタンで追加した設問ブロックは単一選択があらかじめ選択された状態になる', () => {
    buildForm([]);

    document.querySelector('[data-testid="question-add"]').click();

    const block = document.querySelector('[data-testid="question-block"]');
    expect(block.querySelector('[data-testid="question-type-single_choice"]').checked).toBe(true);
    expect(block.querySelector('[data-testid="choices-section"]').hidden).toBe(false);
  });
});
