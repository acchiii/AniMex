(function () {
    const spinnerClass = 'animated-loading-spinner';

    function makeSpinner(inline) {
        const spinner = document.createElement('span');
        spinner.className = spinnerClass + (inline
            ? ' ml-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent align-[-0.125em]'
            : ' absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-purple-500 border-r-transparent');
        spinner.setAttribute('aria-hidden', 'true');
        return spinner;
    }

    window.setPendingForm = function (form) {
        if (!form || form.dataset.pending === '1') return false;

        form.dataset.pending = '1';
        form.setAttribute('aria-busy', 'true');

        const button = form.querySelector('button[type="submit"]');
        if (button && !button.disabled) {
            button.dataset.loadingDisabled = '1';
            button.disabled = true;
        }

        (button || form).appendChild(makeSpinner(Boolean(button)));
        return true;
    };

    window.clearPendingForm = function (form) {
        if (!form || form.dataset.pending !== '1') return;

        form.querySelectorAll('.' + spinnerClass).forEach(function (spinner) {
            spinner.remove();
        });

        const button = form.querySelector('button[type="submit"]');
        if (button && button.dataset.loadingDisabled === '1') {
            button.disabled = false;
            delete button.dataset.loadingDisabled;
        }

        delete form.dataset.pending;
        form.removeAttribute('aria-busy');
    };
})();
