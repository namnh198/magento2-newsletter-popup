define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/mage',
    'jquery/ui'
], function ($, modal) {
    'use strict';

    $.widget('techiz.processPopupNewsletter', {

        /**
         *
         * @private
         */
        _create: function () {
            var self = this;
            if(this._getCookie('newsletter_popup') == null) {
                var popup_newsletter_options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        title: this.options.popupTitle,
                        buttons: false,
                        modalClass : 'popup-newsletter'
                    };

                modal(popup_newsletter_options, this.element);

                setTimeout(function() {
                    self._setStyleCss();
                    self.element.modal('openModal');
                }, 3000);

                this.element.find('form').submit(function() {
                    if ($(this).validation('isValid')) {
                        $.ajax({
                            url: $(this).attr('action'),
                            cache: true,
                            data: $(this).serialize(),
                            dataType: 'json',
                            type: 'POST',
                            showLoader: true
                        }).done(function (data) {
                            self.element.find('.messages .message div').html(data.message);
                            if (data.error) {
                                self.element.find('.messages .message').addClass('message-error error');
                            } else {
                                self._setCookie('newsletter_popup', 1, 1);

                                console.log(self._getCookie('newsletter_popup'));

                                self.element.find('.messages .message').addClass('message-success success');
                                setTimeout(function() {
                                    self.element.modal('closeModal');
                                }, 1000);
                            }
                            self.element.find('.messages').show();
                            setTimeout(function() {
                                self.element.find('.messages').hide();
                            }, 5000);
                        });
                    }
                    return false;
                });

                this._resetStyleCss();


            }
        },

        /**
         * Set width of the popup
         * @private
         */
        _setStyleCss: function(width) {

            width = width || 400;

            if (window.innerWidth > 786) {
                this.element.parent().parent('.modal-inner-wrap').css({'max-width': width+'px'});
            }
        },

        /**
         * Reset width of the popup
         * @private
         */
        _resetStyleCss: function() {
            var self = this;
            $( window ).resize(function() {
                if (window.innerWidth <= 786) {
                    self.element.parent().parent('.modal-inner-wrap').css({'max-width': 'initial'});
                } else {
                    self._setStyleCss(self.options.innerWidth);
                }
            });
        },

        /**
         * get cookie
         */
        _getCookie: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        },

        /**
         * Set cookie
         */
        _setCookie: function (name,value,days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days*24*60*60*1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }
    });

    return $.techiz.processPopupNewsletter;
});
