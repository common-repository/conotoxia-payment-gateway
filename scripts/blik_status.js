const waitingTime = 120000;
let processingStart;

jQuery(document).ready($ => {
    processingStart = Date.now();
    checkStatus($);
    clearEmptyPTag($);
});

function checkStatus($) {
    $.ajax({
        method: 'post',
        url: args.ajaxUrl,
        data: {
            action: 'cx_check_blik_status',
            orderId: args.orderId,
            orderKey: args.orderKey
        },
        dataType: 'json',
        success: (result) => {
            switch (result.status) {
                case 'SUCCESS':
                    changeStatus('success');
                    break;
                case 'WAITING':
                    if (Date.now() - processingStart < waitingTime) {
                        setTimeout(() => checkStatus($), 2000);
                    } else {
                        changeStatus('timeout');
                    }
                    break;
                case 'CODE_ERROR':
                    changeStatus('code-error');
                    break;
                case 'BANK_REJECTION':
                    changeStatus('bank-rejection');
                    break;
                case 'USER_REJECTION':
                    changeStatus('user-rejection');
                    break;
                case 'TIMEOUT':
                    changeStatus('timeout');
                    break;
                case 'ERROR':
                    changeStatus('error');
                    break;
                default:
                    changeStatus('problem');
            }
        },
        error: () => changeStatus('problem')
    });
}

function changeStatus(status) {
    document.querySelectorAll('.js-cx-blik-status-waiting-element')
        .forEach(element => hideElement(element));
    replaceStatusIcon(status);
    document.querySelectorAll(`.js-cx-blik-status-${status}-element`)
        .forEach(element => showElement(element));
    showContinueButton();
}

function showElement(element) {
    element.style.display = null;
}

function replaceStatusIcon(status) {
    let statusIcon;
    switch (status) {
        case 'success':
            statusIcon = document.getElementById('js-cx-blik-status-success-icon');
            break;
        case 'problem':
            break;
        default:
            statusIcon = document.getElementById('js-cx-blik-status-error-icon');
    }
    if (statusIcon) {
        const phoneIcon = document.getElementById('js-cx-blik-status-phone-icon');
        hideElement(phoneIcon);
        showElement(statusIcon);
    }
}

function showContinueButton() {
    const button = document.getElementById('js-cx-blik-status-continue-button');
    showElement(button);
}

function hideElement(element) {
    element.style.display = 'none';
}

function clearEmptyPTag($){
    $("#cx-blik-status-container p:empty").remove();
}