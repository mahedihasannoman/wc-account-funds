/**
 * WC Accounts Funds
 * https://www.braintum.com
 *
 * Copyright (c) 2018 braintum
 * Licensed under the GPLv2+ license.
 */
(function ($) {
    'use strict';
    $.wc_accounts_funds_admin = function () {
        var plugin = this;
        var placeholder = placeholder || 'Select..';
        plugin.init = function () {
            $('.wc-af-select2').select2({});
        };
        plugin.init();
    };

    //$.fn
    $.fn.wc_accounts_funds_admin = function () {

    };

    $.wc_accounts_funds_admin();
})(jQuery);