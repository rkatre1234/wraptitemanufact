
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data',
    'mage/storage',
    'mage/translate',
    'mage/mage',
    'jquery-ui-modules/widget'
], function ($, modal, customerData, storage, $t) {
    'use strict';

    $.widget('ajaxwishlist.customerAuthenticationPopup', {
        options: {
            login: '#customer-popup-login',
            prevLogin: 'a.towishlist'
        },
         _create: function () {
            this._ajaxSubmit();
        },

        _ajaxSubmit: function() {
            var self = this,
                form = this.element.find('form'),
                inputElement = form.find('input');

            inputElement.on("keyup", function(e) {
                self.element.find('.messages').html('');
            });

            form.submit(function (e) {
                if (form.validation('isValid')) {
                    if (form.hasClass('form-create-account')) {
                        $.ajax({
                            url: $(e.target).attr('action'),
                            data: $(e.target).serialize(),
                            showLoader: true,
                            type: 'POST',
                            dataType: 'json',
                            success: function (response) {
                                self._showResponse(response, form.find('input[name="redirect_url"]').val());
                            },
                            error: function() {
                                self._showFailingMessage();
                            }
                        });
                    } else {
                        var submitData = {},
                            formDataArray = $(e.target).serializeArray();
                        formDataArray.forEach(function (entry) {
                            submitData[entry.name] = entry.value;
                        });
                        $('body').loader().loader('show');
                        storage.post(
                            $(e.target).attr('action'),
                            JSON.stringify(submitData)
                        ).done(function (response) {
                            $('body').loader().loader('hide');
                            self._showResponse(response, form.find('input[name="redirect_url"]').val());
                        }).fail(function () {
                            $('body').loader().loader('hide');
                            self._showFailingMessage();
                        });
                    }
                }
                return false;
            });
        },

        /**
         * Display messages on the screen
         * @private
         */
        _displayMessages: function(className, message) {
            $('<div class="message '+className+'"><div>'+message+'</div></div>').appendTo(this.element.find('.messages'));
        },

        /**
         * Showing response results
         * @private
         * @param {Object} response
         * @param {String} locationHref
         */
        _showResponse: function(response, locationHref) {
            var self = this,
                timeout = 800;
            this.element.find('.messages').html('');
            if (response.errors) {
                this._displayMessages('message-error error', response.message);
            } else {
                this._displayMessages('message-success success', response.message);
            }
            this.element.find('.messages .message').show();
            setTimeout(function() {
                if (!response.errors) {
                    self.element.modal('closeModal');
                    window.location.href = locationHref;
                }
            }, timeout);
        },

        /**
         * Show the failing message
         * @private
         */
        _showFailingMessage: function() {
            this.element.find('.messages').html('');
            this._displayMessages('message-error error', $t('An error occurred, please try again later.'));
            this.element.find('.messages .message').show();
        }
    });

    return $.ajaxwishlist.customerAuthenticationPopup;
});
