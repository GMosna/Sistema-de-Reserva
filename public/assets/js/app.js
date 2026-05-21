var ToastManager = (function () {
    var icons = {
        success: 'check-circle-fill',
        error:   'exclamation-triangle-fill',
        warning: 'exclamation-circle-fill',
        info:    'info-circle-fill'
    };
    var colors = {
        success: 'text-success',
        error:   'text-danger',
        warning: 'text-warning',
        info:    'text-primary'
    };

    function show(type, message) {
        var container = document.getElementById('toastContainer');
        if (!container || typeof bootstrap === 'undefined') return;
        var id  = 'toast_' + Date.now() + '_' + Math.random().toString(36).slice(2);
        var icon  = icons[type]  || icons.info;
        var color = colors[type] || colors.info;
        var div = document.createElement('div');
        div.innerHTML =
            '<div id="' + id + '" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
            '<div class="toast-body d-flex align-items-center gap-2">' +
            '<i class="bi bi-' + icon + ' ' + color + '"></i>' +
            '<span>' + message + '</span>' +
            '</div>' +
            '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>' +
            '</div></div>';
        container.appendChild(div.firstElementChild);
        var el = document.getElementById(id);
        var toast = new bootstrap.Toast(el, {delay: 5000});
        toast.show();
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    }

    return {show: show};
})();

document.addEventListener('DOMContentLoaded', function () {
    // Convert hidden flash data elements to toasts
    document.querySelectorAll('[data-flash-type]').forEach(function (el) {
        ToastManager.show(el.dataset.flashType, el.dataset.flashMessage);
    });

    // Flatpickr date pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
        flatpickr('.date-range-picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }
});
