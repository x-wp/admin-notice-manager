<?php
/**
 * Admin Notice Manager initialization function.
 *
 * @package eXtended WordPress
 * @subpackage Admin Notice Manager
 */

if ( ! function_exists( 'xwp_anm_init' ) && function_exists( 'add_action' ) ) :
    /**
     * Initialize the Admin Notice Manager.
     *
     * @return void
     */
    function xwp_anm_init(): void {
        \XWP\ANM\Notice_Manager::instance();
    }

    did_action( 'admin_init' )
        ? xwp_anm_init()
        : add_action( 'admin_init', 'xwp_anm_init' );
endif;
