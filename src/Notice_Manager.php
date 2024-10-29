<?php //phpcs:disable WordPress.PHP.DontExtract.extract_extract, Universal.Operators.DisallowShortTernary.Found, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
/**
 * Admin_Notice_Manager class file
 *
 * @package eXtended WordPress
 * @subpackage Notice Manager
 */

namespace XWP\ANM;

use XWP\Helper\Traits\Singleton;
use XWP_Admin_Notice as Notice;

/**
 * Handles notice display and dismissal.
 *
 * @since 1.0.0
 */
final class Notice_Manager {
    use Singleton;

    /**
     * Whether the notice manager is done
     *
     * @var bool
     */
    private bool $with_js = false;

    /**
     * Constructor
     */
    private function __construct() {
        if ( ! \is_admin() && ! \wp_doing_ajax() ) {
            return;
        }

        \add_action( 'admin_notices', array( $this, 'display_notices' ), 10 );
        \add_action( 'admin_print_footer_scripts', array( $this, 'add_dismiss_js' ) );
        \add_action( 'xwp_after_notice_display', array( $this, 'remove_filters' ) );

        \add_action( 'wp_ajax_xwp_anm_dismiss_notice', array( $this, 'dismiss_notice' ), 99, 0 );

        \add_filter( 'xwp_notice_get_title', array( $this, 'format_notice_title' ), 10, 2 );
        \add_filter( 'xwp_notice_get_message', array( $this, 'format_notice_message' ), 9, 2 );
        \add_filter( 'xwp_notice_get_message', array( $this, 'format_notice_template' ), 10, 2 );
        \add_filter( 'xwp_notice_get_text_wrap', array( $this, 'format_notice_text_wrap' ), 10, 2 );
        \add_filter( 'xwp_notice_get_classes', array( $this, 'format_notice_classes' ), 10, 2 );
        \add_filter( 'xwp_notice_get_style', array( $this, 'format_notice_style' ), 10, 2 );
        \add_filter( 'xwp_notice_get_attributes', array( $this, 'format_notice_atts' ), 10, 2 );
    }

    /**
     * Display notices.
     *
     * @return void
     */
    public function display_notices(): void {
        /**
         * Fires before all notices are displayed.
         *
         * @since 1.0.0
         */
        \do_action( 'xwp_before_notice_display' );

        foreach ( $this->get_notices() as $notice ) {
            if ( ! $this->can_display( $notice ) ) {
                continue;
            }

            \wp_admin_notice( ...$this->get_notice_args( $notice ) );

            if ( $notice->get_persistent() ) {
                continue;
            }

            $notice->delete();
        }

        /**
         * Fires after all notices have been displayed.
         *
         * @since 1.0.0
         */
        \do_action( 'xwp_after_notice_display' );
    }

    /**
     * Add dismiss JS.
     *
     * @return void
     */
    public function add_dismiss_js(): void {
        if ( ! $this->with_js ) {
            return;
        }

        include_once __DIR__ . '/Views/xwp-amn-dismiss-js.php';
    }

    /**
     * Remove filters after displaying notices.
     *
     * We do this in order to remove object references and prevent memory leaks.
     *
     * @return void
     */
    public function remove_filters(): void {
        if ( ! \has_filter( 'xwp_get_display_notices' ) ) {
            return;
        }

        \remove_all_filters( 'xwp_get_display_notices' );
    }

    /**
     * Get notices.
     *
     * @return array<string,Notice>
     */
    private function get_notices(): array {
        $notices = \xwp_get_notices();

        /**
         * Filter the notices before they are displayed.
         *
         * @since 1.0.0
         *
         * @param array<string,Notice> $notices Notices to be displayed.
         */
        return \apply_filters( 'xwp_get_display_notices', $notices );
    }

    /**
     * Get notice arguments.
     *
     * @param  Notice $n Notice object.
     * @return array{message: string, args: array<string,mixed>}
     */
    private function get_notice_args( Notice $n ): array {
        return array(
            'message' => $n->get_message( 'view' ),
            'args'    => array(
                'additional_classes' => $n->get_classes( 'view' ),
                'attributes'         => $n->get_attributes( 'view' ),
                'dismissible'        => $n->get_dismissible( 'view' ),
                'id'                 => $n->get_id(),
                'paragraph_wrap'     => $n->get_text_wrap( 'view' ),
                'type'               => $n->get_type( 'view' ),
            ),
        );
    }

    /**
     * Format notice title.
     *
     * @param  string $title Notice title.
     * @param  Notice $ntc   Notice object.
     * @return string
     */
    public function format_notice_title( string $title, Notice $ntc ): string {
		if ( ! $ntc->get_title() ) {
            return $title;
        }

        return \strtr(
            '<{tag} class="notice-title">{title}</{tag}>',
            array(
                '{tag}'   => $ntc->get_title_tag() ?: 'span',
                '{title}' => $ntc->get_title(),
            ),
        );
    }

    /**
     * Format notice message. If a title or message is set.
     *
     * @param  string $message Notice message.
     * @param  Notice $notice  Notice object.
     * @return string
     */
    public function format_notice_message( string $message, Notice $notice ): string {
        if ( ! $message || ! $notice->get_title() ) {
            return $message;
        }

        return \wp_kses_post( $notice->get_title( 'view' ) . $message );
    }

    /**
     * Format notice message.
     *
     * @param  string $message Notice message.
     * @param  Notice $notice  Notice object.
     * @return string
     */
    public function format_notice_template( string $message, Notice $notice ): string {
        if ( $message || ! $notice->get_template() || ! \file_exists( $notice->get_template() ) ) {
            return $message;
        }

        return \xwp_get_template_html( $notice->get_template(), $notice->get_params() );
    }

    /**
     * Format notice text wrap.
     *
     * @param  bool   $wrap   Notice text wrap.
     * @param  Notice $notice Notice object.
     * @return bool
     */
    public function format_notice_text_wrap( bool $wrap, Notice $notice ): bool {
        if ( $notice->get_template() ) {
            return false;
        }

        if ( ! $notice->get_title() ) {
            return $wrap;
        }

        return 0 === \preg_match( '/^h[1-6]$/', $notice->get_title_tag() );
    }

    /**
     * Format notice classes.
     *
     * @param  array<string> $classes Notice classes.
     * @param  Notice        $notice       Notice object.
     * @return array<string>
     */
    public function format_notice_classes( array $classes, Notice $notice ): array {
        $classes[] = 'xwp-anm-notice';

        if ( $notice->get_dismissible() && $notice->get_persistent() ) {
            $classes[] = 'is-persistent';
        }

        foreach ( array( 'alt', 'large' ) as $c ) {
            $cc = array( $c, "notice-{$c}" );

            if ( ! \array_intersect( $classes, $cc ) ) {
                continue;
            }

            $classes   = \array_diff( $classes, $cc );
            $classes[] = "notice-{$c}";
        }

        return $classes;
    }

    /**
     * Format notice style.
     *
     * @param  string $style Notice style.
     * @param  Notice $n     Notice object.
     * @return string
     */
    public function format_notice_style( string $style, Notice $n ): string {
        if ( ! $n->get_color() ) {
            return $style;
        }

        $color = $n->get_color();

        $style .= "border-left-color: {$color} !important; ";

        if ( $n->is_alt() ) {
            $style .= "background-color: {$this->lighten_color($color)} !important; ";
        }

        return $style;
    }

    /**
     * Format notice attributes.
     *
     * @param  array<string,string> $atts Notice attributes.
     * @param  Notice               $n    Notice object.
     * @return array<string,string>
     */
    public function format_notice_atts( array $atts, Notice $n ): array {
        $atts['style']     = $n->get_style( 'view' );
        $atts['data-id'] ??= $n->get_id();

        if ( $n->get_dismissible() ) {
            $this->with_js = true;

            $atts['data-nonce'] = \wp_create_nonce( 'xwp_anm_dismiss_notice' );
        }

        return \array_filter( $atts );
    }

    /**
     * Check if a notice can be displayed.
     *
     * @param  Notice $ntc Notice object.
     * @return bool
     */
    private function can_display( Notice $ntc ): bool {
        $show = $this->data_valid( $ntc ) && $this->screen_valid( $ntc ) && $this->cap_valid( $ntc );

        /**
         * Filter whether a notice can be displayed.
         *
         * @param  bool   $show Whether the notice can be displayed.
         * @param  Notice $ntc  Notice object.
         * @return bool
         *
         * @since 1.0.0
         */
        return \apply_filters( 'xwp_can_display_notice', $show, $ntc );
    }

    /**
     * Check if the notice data is valid.
     *
     * @param  Notice $notice Notice object.
     * @return bool
     */
    private function data_valid( Notice $notice ): bool {
        return ( $notice->get_message() || $notice->get_template() ) && ! $notice->is_dismissed();
    }

    /**
     * Check if the notice is valid for the current screen.
     *
     * @param  Notice $notice Notice object.
     * @return bool
     */
    private function screen_valid( Notice $notice ): bool {
        static $screen_id;

        $screen_id ??= \get_current_screen()?->id ?? '';

        return ! $notice->get_screens() || \in_array( $screen_id, $notice->get_screens(), true );
    }

    /**
     * Check if the notice is valid for the current user.
     *
     * @param  Notice $notice Notice object.
     * @return bool
     */
    private function cap_valid( Notice $notice ): bool {
        return ! $notice->get_caps() || \array_reduce(
            $notice->get_caps(),
            static fn( $r, $c ) => $r || \current_user_can( $c ),
            false,
        );
    }

    /**
     * Ajax callback to dismiss a notice.
     *
     * @return void
     */
    public function dismiss_notice(): void {
        \check_ajax_referer( 'xwp_anm_dismiss_notice', '_wpnonce' );

        $notice_id = \xwp_fetch_post_var( 'id', '' );

        \xwp_get_notice( $notice_id, false )?->dismiss()?->save( true );

        exit;
    }

    /**
     * Lightens a border color to make it usable as a background color.
     *
     * Each channel is lightened by 90% towards white.
     *
     * @param  string $color Hex color.
     * @param  float  $factor Lighten factor.
     * @return string
     */
    private function lighten_color( string $color, float $factor = 0.9 ): string {
        $color = \ltrim( $color, '#' );

        $r = \hexdec( \substr( $color, 0, 2 ) );
        $g = \hexdec( \substr( $color, 2, 2 ) );
        $b = \hexdec( \substr( $color, 4, 2 ) );

        $r = (int) ( ( $r * ( 1 - $factor ) ) + ( 255 * $factor ) );
        $g = (int) ( ( $g * ( 1 - $factor ) ) + ( 255 * $factor ) );
        $b = (int) ( ( $b * ( 1 - $factor ) ) + ( 255 * $factor ) );

        return \sprintf( '#%02x%02x%02x', $r, $g, $b );
    }
}
