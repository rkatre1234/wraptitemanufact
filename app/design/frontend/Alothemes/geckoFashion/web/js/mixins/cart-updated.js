/*
* @Author: nguyen
* @Date:   2019-06-03 14:51:40
* @Last Modified by:   nguyen
* @Last Modified time: 2019-06-03 14:51:44
*/

define(['jquery'], function ($) {
    'use strict';

    var minicart = $('[data-block="minicart"]');

    var openTO = 0;

    var updateCount = 0;

    minicart.on('contentLoading', function () {
        updateCount++;
    });

    var mixin = {

        update: function (updatedCart) {

            this._super(updatedCart);

            if (updateCount >= 1) {

                if (minicart && minicart.find('[data-role="dropdownDialog"]').length) {

                    minicart.find('[data-role="dropdownDialog"]').dropdownDialog("open");
                    clearTimeout(openTO);
                    openTO = setTimeout(function() {
                        minicart.find('[data-role="dropdownDialog"]').dropdownDialog("close");
                    }, 10000);

                }
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});