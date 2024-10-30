jQuery(document).ready($ => {
    const id = defaultPaymentMethod.id;
    const input = $(`input[value="${id}"]`);
    if (input) {
        input.prop('checked', true);
    }
});
