<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName, Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * Admin Notice Manager functions and utilities.
 *
 * @package eXtended WordPress
 * @subpackage Admin Notice Manager
 */

use XWP\ANM\Notice_Repository;

/**
 * Create a new notice.
 *
 * @param  array{
 *   id?: string,
 *   type?: string,
 *   message?: string,
 *   template?: string,
 *   params?: array<string,mixed>,
 *   persistent?: bool,
 *   dismissible?: bool,
 *   screens?: string|array<string>,
 *   caps?: string|array<string>,
 *   color?: string,
 *   individual?: bool,
 *   attributes?: array<string,string>,
 *   classes?: array<string>,
 *   text_wrap?: bool,
 *   style?: string
 * } $args Notice arguments.
 * @return XWP_Admin_Notice
 */
function xwp_create_notice( array $args = array() ): XWP_Admin_Notice {
    return ( new XWP_Admin_Notice() )->set_props( $args );
}

/**
 * Get a notice by ID.
 *
 * By default, this function will create a new notice with requested ID if it doesn't exist.
 *
 * @param  string $id   Notice ID.
 * @param  bool   $make Create the notice if it doesn't exist.
 * @return ?XWP_Admin_Notice
 */
function xwp_get_notice( string $id, bool $make = true ): ?XWP_Admin_Notice {
    $notice = Notice_Repository::instance()->get( $id );

    if ( ! $notice && $make ) {
        $notice = ( new XWP_Admin_Notice() )->set_id( $id );
    }

    return $notice;
}

/**
 * Get all notices.
 *
 * @return array<string,XWP_Admin_Notice>
 */
function xwp_get_notices(): array {
    return Notice_Repository::instance()->all();
}

/**
 * Delete a notice by ID.
 *
 * @param  string $id  Notice ID.
 * @param  bool   $now Delete the notice immediately.
 * @return bool
 */
function xwp_delete_notice( string $id, bool $now = false ): bool {
    return xwp_get_notice( $id, false )?->delete( $now ) ?? false;
}

/**
 * Clear all notices.
 *
 * @param  bool $force Force clear all notices.
 * @return void
 */
function xwp_clear_notices( bool $force = false ): void {
    Notice_Repository::instance()->clear( $force );
}
