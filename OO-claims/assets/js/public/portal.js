(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('#cm-logout').on('click', function(e) {
            e.preventDefault();
            window.location = cm_portal_data.logout_url;
        });
    });
})(jQuery);