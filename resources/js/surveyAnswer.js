const STEP_SELECTOR = '[data-testid="answer-step"]';

function showToast(message) {
  const toast = document.createElement('div');
  toast.dataset.testid = 'answer-toast';
  toast.textContent = message;
  toast.className =
    'fixed bottom-6 left-1/2 -translate-x-1/2 bg-[#14161c] text-white text-[13px] px-4 py-2 rounded-[9px] shadow-lg z-50';
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2200);
}

function renderStep(wizard, index) {
  const steps = wizard.querySelectorAll(STEP_SELECTOR);
  const questionStepCount = steps.length - 1;
  const isConfirmStep = index === steps.length - 1;

  steps.forEach((step, i) => {
    step.hidden = i !== index;
  });

  const progressBar = wizard.querySelector('[data-testid="answer-progress-bar"]');
  const progressLabel = wizard.querySelector('[data-testid="answer-progress-label"]');
  const percent = questionStepCount > 0 ? Math.round(((isConfirmStep ? questionStepCount : index + 1) / questionStepCount) * 100) : 100;

  if (progressBar) {
    progressBar.style.width = `${Math.min(percent, 100)}%`;
  }
  if (progressLabel) {
    progressLabel.textContent = isConfirmStep ? '確認画面' : `${index + 1} / ${questionStepCount}`;
  }

  const backButton = wizard.querySelector('[data-testid="answer-back"]');
  if (backButton) {
    backButton.disabled = index === 0;
  }

  const nextButton = wizard.querySelector('[data-testid="answer-next"]');
  const submitButton = wizard.querySelector('[data-testid="answer-submit"]');
  if (nextButton) {
    nextButton.hidden = isConfirmStep;
  }
  if (submitButton) {
    submitButton.hidden = !isConfirmStep;
  }
}

export function initSurveyAnswer(root = document) {
  const wizard = root.querySelector('[data-survey-answer]');
  if (!wizard) {
    return;
  }

  let currentIndex = 0;
  const stepCount = wizard.querySelectorAll(STEP_SELECTOR).length;
  renderStep(wizard, currentIndex);

  wizard.querySelector('[data-testid="answer-next"]')?.addEventListener('click', () => {
    if (currentIndex < stepCount - 1) {
      currentIndex += 1;
      renderStep(wizard, currentIndex);
    }
  });

  wizard.querySelector('[data-testid="answer-back"]')?.addEventListener('click', () => {
    if (currentIndex > 0) {
      currentIndex -= 1;
      renderStep(wizard, currentIndex);
    }
  });

  wizard.querySelector('[data-testid="answer-save-draft"]')?.addEventListener('click', () => {
    showToast('下書きを保存しました（モック）');
    setTimeout(() => {
      window.location.href = wizard.dataset.homeUrl;
    }, 900);
  });

  wizard.querySelector('[data-testid="answer-submit"]')?.addEventListener('click', () => {
    showToast('回答を送信しました（モック）');
    setTimeout(() => {
      window.location.href = wizard.dataset.homeUrl;
    }, 900);
  });
}
