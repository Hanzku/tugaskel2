'use strict';

document.addEventListener('submit', (event) => {
  const button = event.submitter;
  if (button instanceof HTMLButtonElement && button.dataset.confirm) {
    if (!window.confirm(button.dataset.confirm)) {
      event.preventDefault();
    }
  }
});
