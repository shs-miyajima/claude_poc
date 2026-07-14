const QUESTION_BLOCK_SELECTOR = '[data-testid="question-block"]';
const CHOICE_BLOCK_SELECTOR = '[data-testid="choice-block"]';
const MIN_CHOICES = 2;

function renumber(formRoot) {
  const questionBlocks = formRoot.querySelectorAll(QUESTION_BLOCK_SELECTOR);

  // Renaming a radio input's `name` to a value that momentarily matches another
  // still-checked radio's name makes the browser uncheck one of them immediately
  // (native same-name radio-group exclusivity). Renumbering to a collision-free
  // temporary prefix first, then to the final index, avoids that transient collision.
  questionBlocks.forEach((block, qIndex) => {
    block.querySelectorAll('[name]').forEach((el) => {
      el.name = el.name.replace(/^questions\[[^\]]*\]/, `questions[tmp${qIndex}]`);
    });

    const choiceBlocks = block.querySelectorAll(CHOICE_BLOCK_SELECTOR);
    choiceBlocks.forEach((choiceBlock, cIndex) => {
      choiceBlock.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replace(/\[choices\]\[[^\]]*\]/, `[choices][tmp${cIndex}]`);
      });
    });
  });

  questionBlocks.forEach((block, qIndex) => {
    block.querySelectorAll('[name]').forEach((el) => {
      el.name = el.name.replace(/^questions\[tmp[^\]]*\]/, `questions[${qIndex}]`);
    });

    const choiceBlocks = block.querySelectorAll(CHOICE_BLOCK_SELECTOR);
    choiceBlocks.forEach((choiceBlock, cIndex) => {
      choiceBlock.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replace(/\[choices\]\[tmp[^\]]*\]/, `[choices][${cIndex}]`);
      });
    });
  });
}

function updateChoiceRemoveDisabled(questionBlock) {
  const choiceBlocks = questionBlock.querySelectorAll(CHOICE_BLOCK_SELECTOR);
  const disable = choiceBlocks.length <= MIN_CHOICES;

  choiceBlocks.forEach((choiceBlock) => {
    const removeButton = choiceBlock.querySelector('[data-testid="choice-remove"]');
    if (removeButton) {
      removeButton.disabled = disable;
    }
  });
}

function updateQuestionTypeVisibility(questionBlock) {
  const checkedType = questionBlock.querySelector('input[name$="[question_type]"]:checked');
  const value = checkedType ? checkedType.value : '';
  const choicesSection = questionBlock.querySelector('[data-testid="choices-section"]');
  const scaleSection = questionBlock.querySelector('[data-testid="scale-section"]');

  if (choicesSection) {
    choicesSection.hidden = value !== 'single_choice' && value !== 'multiple_choice';
  }
  if (scaleSection) {
    scaleSection.hidden = value !== 'scale';
  }
}

function addQuestionBlock(formRoot) {
  const template = formRoot.querySelector('[data-testid="question-template"]');
  const container = formRoot.querySelector('[data-testid="questions-container"]');
  if (!template || !container) {
    return;
  }

  container.appendChild(template.content.cloneNode(true));
  renumber(formRoot);
}

function addChoiceBlock(formRoot, questionBlock) {
  const template = formRoot.querySelector('[data-testid="choice-template"]');
  const container = questionBlock.querySelector('[data-testid="choices-container"]');
  if (!template || !container) {
    return;
  }

  container.appendChild(template.content.cloneNode(true));
  renumber(formRoot);
  updateChoiceRemoveDisabled(questionBlock);
}

function removeQuestionBlock(formRoot, questionBlock) {
  if (!window.confirm('設問を削除しますか？')) {
    return;
  }

  questionBlock.remove();
  renumber(formRoot);
}

function removeChoiceBlock(formRoot, questionBlock, choiceBlock) {
  const choiceBlocks = questionBlock.querySelectorAll(CHOICE_BLOCK_SELECTOR);
  if (choiceBlocks.length <= MIN_CHOICES) {
    return;
  }

  choiceBlock.remove();
  renumber(formRoot);
  updateChoiceRemoveDisabled(questionBlock);
}

function moveQuestionBlock(formRoot, questionBlock, direction) {
  if (direction === 'up') {
    const prev = questionBlock.previousElementSibling;
    if (prev) {
      questionBlock.parentElement.insertBefore(questionBlock, prev);
    }
  } else {
    const next = questionBlock.nextElementSibling;
    if (next) {
      questionBlock.parentElement.insertBefore(next, questionBlock);
    }
  }

  renumber(formRoot);
}

function handleClick(formRoot, event) {
  const target = event.target;
  if (!(target instanceof Element)) {
    return;
  }

  if (target.closest('[data-testid="question-add"]')) {
    addQuestionBlock(formRoot);
    return;
  }

  const questionBlock = target.closest(QUESTION_BLOCK_SELECTOR);
  if (!questionBlock) {
    return;
  }

  if (target.closest('[data-testid="question-remove"]')) {
    removeQuestionBlock(formRoot, questionBlock);
    return;
  }

  if (target.closest('[data-testid="question-move-up"]')) {
    moveQuestionBlock(formRoot, questionBlock, 'up');
    return;
  }

  if (target.closest('[data-testid="question-move-down"]')) {
    moveQuestionBlock(formRoot, questionBlock, 'down');
    return;
  }

  if (target.closest('[data-testid="choice-add"]')) {
    addChoiceBlock(formRoot, questionBlock);
    return;
  }

  const choiceRemoveButton = target.closest('[data-testid="choice-remove"]');
  if (choiceRemoveButton) {
    if (choiceRemoveButton.disabled) {
      return;
    }

    const choiceBlock = target.closest(CHOICE_BLOCK_SELECTOR);
    if (choiceBlock) {
      removeChoiceBlock(formRoot, questionBlock, choiceBlock);
    }
  }
}

function handleChange(event) {
  const target = event.target;
  if (!(target instanceof Element) || !target.matches('input[name$="[question_type]"]')) {
    return;
  }

  const questionBlock = target.closest(QUESTION_BLOCK_SELECTOR);
  if (questionBlock) {
    updateQuestionTypeVisibility(questionBlock);
  }
}

export function initSurveyForm(root = document) {
  const formRoot = root.querySelector('[data-survey-form]');
  if (!formRoot) {
    return;
  }

  formRoot.querySelectorAll(QUESTION_BLOCK_SELECTOR).forEach((block) => {
    updateQuestionTypeVisibility(block);
    updateChoiceRemoveDisabled(block);
  });

  formRoot.addEventListener('click', (event) => handleClick(formRoot, event));
  formRoot.addEventListener('change', handleChange);
}
