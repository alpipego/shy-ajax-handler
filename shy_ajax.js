/**
 * Created by alpipego on 04/06/16.
 */
jQuery(document).ready(function ($) {
    // get url query params
    $.urlParam = function(name, url){
        var url = url || window.location.href,
            results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);

        if( results === null ){
            return null;
        }

        return results[1] || 0;
    };

    $(document).ajaxSend(function(event, jqxhr, settings) {
        var request = $.urlParam('action', settings.type === 'GET' ? settings.url : '?' + settings.data),
            action = 'wp_ajax_' + (shy_ajax.loggedin ? '' : 'nopriv_') + request,
            handlers = JSON.parse(shy_ajax.collection);

        if (settings.type === 'GET') {
            settings.url += '&handler=' + encodeURIComponent(handlers[action]);
        } else {
            settings.data += '&handler=' + encodeURIComponent(handlers[action]);
        }
    });
});
