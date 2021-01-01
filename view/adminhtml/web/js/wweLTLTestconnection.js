require(["jquery", "domReady!"], function ($) {
    /* Test Connection Validation */
    addWweLtTestConnTitle($);
    $('#WweLtTestConnBtn').click(function (event) {
        event.preventDefault();
        if ($('#config-edit-form').valid()) {
            let ajaxURL = $(this).attr('connAjaxUrl');
            WweLtTestConnectionAjaxCall($, ajaxURL);
        }
        return false;
    });
});

/**
 * Assign Title to inputs
 */
function addWweLtTestConnTitle($) {
    $('#WweLtConnSettings_first_WweLtAccountNumber').attr('title', 'Account Number');
    $('#WweLtConnSettings_first_WweLtUsername').attr('title', 'Username');
    $('#WweLtConnSettings_first_WweLtPassword').attr('title', 'Password');
    $('#WweLtConnSettings_first_WweLtAuthenticationKey').attr('title', 'Authentication Key');
    $('#WweLtConnSettings_first_WweLtLicenseKey').attr('title', 'Plugin License Key');
}

/**
 * Test connection ajax call
 * @param {type} ajaxURL
 * @returns {Success or Error}
 */
function WweLtTestConnectionAjaxCall($, ajaxURL) {
    var credentials = {
        accountNumber: $('#WweLtConnSettings_first_WweLtAccountNumber').val(),
        username: $('#WweLtConnSettings_first_WweLtUsername').val(),
        password: $('#WweLtConnSettings_first_WweLtPassword').val(),
        authenticationKey: $('#WweLtConnSettings_first_WweLtAuthenticationKey').val(),
        pluginLicenceKey: $('#WweLtConnSettings_first_WweLtLicenseKey').val()
    };
    WweLtAjaxRequest(credentials, ajaxURL, WweLtConnectSuccessFunction);
}

/**
 *
 * @param {type} data
 * @returns {undefined}
 */
function WweLtConnectSuccessFunction(data) {
    let styleClass = data.error ? 'error': 'success';
    WweLtResponseMessage('WweLt-con-msg',styleClass, data.msg);
}
