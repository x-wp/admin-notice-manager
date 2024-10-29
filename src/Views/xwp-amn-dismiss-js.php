<?php
/**
 * Dismiss notice JavaScript.
 *
 * @package eXtended WordPress
 * @subpackage Admin Notice Manager
 */

defined( 'ABSPATH' ) || exit;

\printf(
    <<<'JS'
    <script type="text/javascript">
        (function($) {
            $('.xwp-anm-notice.is-dismissible.is-persistent').on('click', '.notice-dismiss', function() {
                var $notice = $(this).closest('.xwp-anm-notice');

                $.post(window.ajaxurl, {
                    action: 'xwp_anm_dismiss_notice',
                    id: $notice.data('id'),
                    _wpnonce: $notice.data('nonce')
                });
            });
        }) (jQuery);
    </script>
    JS,
);
