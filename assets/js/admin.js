/**
 * Claims Angel Admin JavaScript
 * Basic admin functionality for the Claims Angel plugin
 * 
 * Created: 2025-02-22
 * Last Modified: 2025-02-22 12:24:27
 * Author: DVVTEO
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize notice handling
        initNotices();
    });

    /**
     * Initialize notice functionality
     */
    function initNotices() {
        $('.notice.is-dismissible').each(function() {
            var $notice = $(this);
            
            if (!$notice.find('button.notice-dismiss').length) {
                var $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                $notice.append($button);
                
                $button.on('click', function(e) {
                    e.preventDefault();
                    $notice.fadeOut(100, function() {
                        $notice.remove();
                    });
                });
            }
        });
    }

})(jQuery);