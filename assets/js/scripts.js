/*
*   SALES NOTIFICATIONS FOR WOOCOMMERCE
*   COPYRIGHT 2018, ROYALZ TOOLKITS
*   ROYALZTOOLKITSCOM
*/

jQuery(function($) {
    
    function SNFW() {

        // SET DEFAULTS
        var delayInitial    = 5000;
        var delayDisplay    = 5000;
        var delayTime       = 5000;

        var history         = {};

        function init() {

            // GET SETTINGS
            delayInitial    = parseInt(snfw_settings.delays_initial) * 1000;
            delayDisplay    = parseInt(snfw_settings.delays_display) * 1000;
            delayTime       = parseInt(snfw_settings.delays_time) * 1000;

            // DISPLAY NEXT MESSAGE
            setTimeout( display, delayInitial );

        }

        function display() {

            $.ajax({
                type: 'POST',
                data: { action: 'snfw_message', history: JSON.stringify(history) },
                dataType: 'html',
                url: snfw_ajax_object.ajax_url,
                success: function (data) {

                    if ( !data.startsWith('error') || $('.snfw-message').length ) {

                        // APPEND MESSAGE TO BODY
                        $('body').append(data);

                        // GET MESSAGE HISTORY
                        if ( typeof $('#snfw-message').attr('data-message-id') != 'undefined' && typeof $('#snfw-message').attr('data-message-type') != 'undefined' ) {
                            history[$('#snfw-message').attr('data-message-type')] = $('#snfw-message').attr('data-message-id');
                        }

                        // ACTIVATE MESSAGE
                        var message_height = $('#snfw-message').height();
                        var message_bottom = $('#snfw-message').css('bottom');
                        var buttons_height = $('#snfw-message').find(".snfw-message-buttons").height();
                        $('.snfw-message').addClass('snfw-message-active').hover( function(){
                            var y = buttons_height / 4;
                            var h = message_height + buttons_height;
                            var message = anime({
                                targets: '.snfw-message-expand',
                                translateY: { value: y, duration: 100, easing: 'easeInOutSine' },
                                height: { value: h, duration: 100, easing: 'easeInOutSine' },
                            });
                        }, function(){
                            var y = 0;
                            var h = message_height;
                            var message = anime({
                                targets: '.snfw-message-expand',
                                translateY: { value: y, duration: 50, easing: 'easeInOutSine' },
                                height: { value: h, duration: 50, easing: 'easeInOutSine' },
                            });
                        }).find('.snfw-message-close').on('click', function(event){
                            event.preventDefault();
                            $('#snfw-message').remove();
                        });

                        // HIDE MESSAGE AFTER SELECTED TIME
                        setTimeout( function() {

                            // DEACTIVATE MESSAGE
                            $('#snfw-message').removeClass('snfw-message-active');
                            setTimeout( function() {

                                // REMOVE CURRENT MESSAGE
                                $('#snfw-message').remove();

                                // DISPLAY NEXT MESSAGE
                                setTimeout( display, delayDisplay );

                            }, 1000);

                        }, delayTime);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR + " :: " + textStatus + " :: " + errorThrown);
                }
            });

        }

        init();

    }
    
    var SNFW = SNFW();

    function base64Encode(string) {
        var Base64 = {_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
        return Base64.encode(string);
    }

    function base64Decode(string) {
        var Base64 = {_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
        return Base64.decode(string);
    }

});