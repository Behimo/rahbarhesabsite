document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-password-toggle]');
  const input = document.querySelector('#password');

  if (!toggle || !input) {
    return;
  }

  const swap = () => {
    const show = input.getAttribute('type') === 'password';
    input.setAttribute('type', show ? 'text' : 'password');
    toggle.querySelector('[data-eye-off]')?.classList.toggle('d-none', show);
    toggle.querySelector('[data-eye]')?.classList.toggle('d-none', !show);
    toggle.setAttribute('aria-label', show ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور');
  };

  toggle.addEventListener('click', swap);
  toggle.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      swap();
    }
  });
});
