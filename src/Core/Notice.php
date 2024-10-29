<?php //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder

use XWP\ANM\Notice_Repository;

/**
 * Admin notice.
 *
 * @method self set_title( string $title )                Set notice title.
 * @method self set_title_tag( string $tag )              Set notice title tag.
 * @method self set_message( string $message )            Set notice message.
 * @method self set_template( string $file )              Set notice file template.
 * @method self set_params( array<string,mixed> $params ) Set file parameters.
 * @method self set_persistent( bool $persistent )        Set whether the notice is persistent.
 * @method self set_dismissible( bool $dismissible )      Set whether the notice is dismissible.
 * @method self set_color( string $color )                     Set notice color.
 * @method self set_individual( bool $individual ) Set whether the notice is per user.
 * @method self set_attributes( array<string,string> $notice_atts ) Set notice attributes.
 * @method self set_style( string|array<string,string> $notice_css ) Set notice CSS.
 * @method self set_text_wrap( bool $text_wrap ) Set whether to wrap the message.
 * @method self set_dismissed( array<int,int> $dismissed ) Set dismissed users.
 *
 * @method string               get_title( string $context = 'edit' )       Get notice title.
 * @method string               get_title_tag( string $context = 'edit' )   Get notice title tag.
 * @method string               get_message( string $context = 'edit' ) Get notice message.
 * @method string               get_template( string $context = 'edit' ) Get notice file template.
 * @method array<string,mixed>  get_params( string $context = 'edit' ) Get file parameters.
 * @method array<string>        get_caps( string $context = 'edit' )        Get capabilities required to see the notice.
 * @method bool                 get_dismissible( string $context = 'edit' ) Get whether the notice is dismissible.
 * @method array<int>           get_dismissed( string $context = 'edit' ) Get dismissed users.
 * @method string               get_color( string $context = 'edit' ) Get notice color.
 * @method bool                 get_persistent( string $context = 'edit' ) Get whether the notice is persistent.
 * @method bool                 get_individual( string $context = 'edit' ) Get whether the notice is per user.
 * @method array<string>        get_screens( string $context = 'edit' ) Get screens where the notice should be displayed.
 * @method string               get_type( string $context = 'edit' ) Get notice type.
 * @method array<string,string> get_attributes( string $context = 'edit' ) Get notice attributes.
 * @method array<string>        get_classes( string $context = 'edit' ) Get notice class.
 * @method string               get_style( string $context = 'edit' ) Get notice CSS.
 * @method bool                 get_text_wrap( string $context = 'edit' ) Get whether to wrap the message.
 */
class XWP_Admin_Notice {
    /**
     * Whether the notice is trashed
     *
     * @var bool
     */
    private bool $trashed = false;

    /**
     * Whether the notice exists
     *
     * @var bool
     */
    private bool $exists = false;

    /**
     * Default notice types.
     *
     * If a registered notice type is not in this array, we expect a hex color.
     *
     * @var array<int,string>
     */
    private $notice_types = array(
        'success',
        'error',
        'warning',
        'info',
    );

    /**
     * Notice ID
     *
     * @var string
     */
    private string $id = '';

    /**
     * Whether the notice object has been read
     *
     * @var bool
     */
    private bool $object_read = false;

    /**
     * Notice data
     *
     * @var array<string, mixed>
     */
    private array $data = array(
        'type'        => 'info',
        'message'     => '',
        'title'       => '',
        'title_tag'   => 'span',
        'template'    => '',
        'params'      => array(),
        'persistent'  => false,
        'dismissible' => true,
        'screens'     => array(),
        'caps'        => array( 'manage_options' ),
        'color'       => '',
        'individual'  => false,
        'attributes'  => array(),
        'classes'     => array(),
        'style'       => '',
        'text_wrap'   => true,
        'dismissed'   => array(),
    );

    /**
     * Default notice data
     *
     * @var array<string, mixed>
     */
    private array $defs = array();

    /**
     * Changes to the notice
     *
     * @var array<string, mixed>
     */
    private array $changes = array();

    /**
     * Constructor
     *
     * @param array<string,mixed>|string|XWP_Admin_Notice $data Notice data.
     */
    public function __construct( array|string|XWP_Admin_Notice $data = '' ) {
        $this->defs = $this->data;

        match ( true ) {
            is_string( $data ) && $data               => $this->set_id( $data ),
            $data instanceof self                     => $this->set_id( $data->get_id() ),
            is_array( $data ) && isset( $data['id'] ) => $this->set_id( $data['id'] ),
            default                                   => $this->set_object_read( true ),
        };

        if ( $this->get_object_read() ) {
            return;
        }

        $this
            ->set_exists()
            ->get_repo()
            ->read( $this );
    }

	/**
     * Universal prop getter / setter
     *
     * @param  string           $name Method name.
     * @param  array<int,mixed> $args Method arguments.
     * @return mixed        Void or prop value.
     *
     * @throws \BadMethodCallException If prop does not exist.
     */
    public function __call( string $name, array $args ): mixed {
        \preg_match( '/^([gs]et)_(.+)$/', $name, $m );

        if ( 3 !== \count( $m ) || ( 'set' === ( $m[1] ?? '' ) && ! isset( $args[0] ) ) ) {
            return null;
        }

        [ $name, $type, $prop ] = $m;

        return 'get' === $type
            ? $this->get_prop( $prop, $args[0] ?? 'edit' )
            : $this->set_prop( $prop, $args[0] );
	}

    /**
     * Check if a prop has changed
     *
     * @param  string $prop  Prop name.
     * @param  mixed  $value Prop value.
     * @return bool
     */
    private function has_change( string $prop, mixed $value ): bool {
        return array_key_exists( $prop, $this->changes ) || $value !== $this->data[ $prop ];
    }

    /**
     * Set multiple props
     *
     * @param  array<string, mixed> $props Props to set.
     * @return self
     */
    public function set_props( array $props, ): self {
        foreach ( $props as $prop => $value ) {
            $this->{"set_{$prop}"}( $value );
        }

        return $this;
    }

    /**
     * Set a prop
     *
     * @param  string $prop  Prop name.
     * @param  mixed  $value Prop value.
     * @return self
     */
    private function set_prop( string $prop, mixed $value ): self {
        if ( array_key_exists( $prop, $this->data ) ) {
            $var = match ( true ) {
                ! $this->object_read               => 'data',
                $this->has_change( $prop, $value ) => 'changes',
                default                            => 'data',
            };

            $this->{$var}[ $prop ] = $value;
        }

        return $this;
    }

    /**
     * Set whether the notice exists
     *
     * @param  bool $exists Whether the notice exists.
     * @return self
     */
    private function set_exists( bool $exists = true ): self {
        $this->exists = $exists;

        return $this;
    }

    /**
     * Set defaults
     *
     * @return self
     */
    public function set_defaults(): self {
        $this->data    = $this->defs;
        $this->changes = array();

        return $this->set_object_read( false );
    }

    /**
     * Set whether the notice has been read
     *
     * @param  bool $read Whether the notice has been read.
     * @return self
     */
    public function set_object_read( bool $read ): self {
        $this->object_read = $read;

        return $this;
    }

    /**
     * Set notice ID
     *
     * @param  string $id Notice ID.
     * @return self
     */
    public function set_id( string $id ): self {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the notice message.
     *
     * @param  string $type Notice type.
     * @return self
     */
    public function set_type( string $type ): self {
        if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $type ) ) {
            return $this->set_prop( 'color', $type )->set_prop( 'type', 'info' );
        }

        if ( ! in_array( $type, $this->notice_types, true ) ) {
            return $this->set_prop( 'type', 'info' );
        }

        return $this->set_prop( 'type', $type );
	}

    /**
     * Set the notice classes
     *
     * @param  string|array<string> $classes Classes.
     * @return self
     */
    public function set_classes( string|array $classes ): self {
        return $this->set_prop( 'classes', xwp_str_to_arr( $classes ) );
    }

    /**
     * Set capabilities required to see the notice
     *
     * @param  string|array<string> $caps Capabilities.
     * @return self
     */
    public function set_cap( string|array $caps ): self {
        return $this->set_prop( 'caps', \xwp_str_to_arr( $caps ) );
    }

    /**
     * Set screens where the notice should be displayed
     *
     * @param  string|array<string> $screens Comma separated list of screen IDs, or an array of screen IDs.
     * @return self
     */
    public function set_screens( string|array $screens ): self {
        return $this->set_prop( 'screens', \xwp_str_to_arr( $screens ) );
    }

    /**
     * Set the trashed status
     *
     * @param  bool $trashed Trashed status.
     * @return self
     */
    protected function set_trashed( bool $trashed ): self {
        $this->trashed = $trashed;
        $this->exists  = false;

        return $this;
    }

    /**
     * Get all props
     *
     * @param  string $context Context.
     * @return array<string,mixed>
     */
	public function get_data( string $context = 'save' ): array {
        $data = array( 'id' => $this->get_id() );

        foreach ( $this->data as $k => $v ) {
            if ( 'save' === $context && $v === $this->defs[ $k ] ) {
                continue;
            }

            $data[ $k ] = $v;
        }

		return $data;
	}

    /**
     * Get a prop
     *
     * @param  string $prop    Prop name.
     * @param  string $context Context. View or edit.
     * @return mixed
     */
	private function get_prop( string $prop, string $context = 'edit' ): mixed {
        $var = array_key_exists( $prop, $this->changes ) ? 'changes' : 'data';
		$val = $this->{$var}[ $prop ] ?? null;

        return 'view' === $context
            ? apply_filters( "xwp_notice_get_{$prop}", $val, $this )
            : $val;
	}

    /**
     * Get the notice repository
     *
     * @return Notice_Repository
     */
    public function get_repo(): Notice_Repository {
        return Notice_Repository::instance();
    }

    /**
     * Check if the notice exists
     *
     * @return bool
     */
    private function exists(): bool {
        return $this->exists;
    }

    /**
     * Get the notice ID.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get whether the object has been read.
     *
     * @return bool
     */
    public function get_object_read(): bool {
        return $this->object_read;
    }

    /**
	 * Return data changes only.
	 *
	 * @since 3.0.0
	 * @return array<string,mixed>
	 */
	public function get_changes() {
		return $this->changes;
	}

    /**
     * Check if we can show temporary notice.
     *
     * @return bool
     */
    public function can_show(): bool {
        return \is_admin() && ! \wp_doing_ajax() && ! did_action( 'xwp_after_notice_display' );
    }

    /**
     * Is the alterative display.
     *
     * @return bool
     */
    public function is_alt(): bool {
        return count( array_intersect( $this->get_classes(), array( 'alt', 'notice-alt' ) ) ) > 0;
    }

    /**
     * Check if the notice is dismissed.
     *
     * @return bool
     */
    public function is_dismissed(): bool {
        $user_id = $this->get_individual() ? get_current_user_id() : 0;

        return in_array( $user_id, $this->get_dismissed(), true );
    }

    /**
     * Dismiss a notice.
     *
     * @return self
     */
    public function dismiss(): self {
        if ( ! $this->get_dismissible() || ! $this->get_individual() ) {
            $this->delete();

            return $this;
        }

        // @phpstan-ignore ternary.alwaysTrue
        $user_id = $this->get_individual() ? get_current_user_id() : 0;
        $dsm_ids = array_merge( $this->get_dismissed(), array( $user_id ) );

        return $this->set_dismissed( array_values( array_unique( $dsm_ids ) ) );
    }

	/**
	 * Merge changes with data and clear.
	 *
     * @return self
	 */
	public function apply_changes(): self {
		$this->data    = array_merge( $this->data, $this->changes );
		$this->changes = array();

        return $this;
	}

    /**
     * Append classes to the notice.
     *
     * @param  string ...$classes Classes to add.
     * @return self
     */
    public function with_classes( string ...$classes ): self {
        return $this->set_classes( array_merge( $this->get_classes(), $classes ) );
    }

    /**
     * Append attributes to the notice.
     *
     * @param  string $att   Attribute name.
     * @param  mixed  $value Attribute value.
     * @return self
     */
    public function with_attribute( string $att, mixed $value ): self {
        return $this->set_attributes( array_merge( $this->get_attributes(), array( $att => $value ) ) );
    }

    /**
     * Save a notice.
     *
     * @param  bool $now Whether to save immediately.
     * @return self
     */
    public function save( bool $now = false ): self {
        if ( $this->trashed ) {
            return $this;
        }

        $this->exists()
            ? $this->get_repo()->update( $this )
            : $this->get_repo()->create( $this );

        $this->get_repo()->persist( $now );

        return $this;
    }

    /**
     * Show a notice.
     *
     * If notices have already been displayed, this will create a new non-persistent notice with a unique ID and save it.
     *
     * @return self
     */
    public function show(): self {
        return $this
            ->with_attribute( 'data-id', $this->get_id() )
            ->set_id( $this->get_id() . '-' . uniqid() )
            ->set_persistent( false )
            ->set_individual( false )
            ->save();
    }

    /**
     * Delete a notice.
     *
     * @param  bool $now Whether to delete immediately.
     * @return bool
     */
    public function delete( bool $now = false ): bool {
        if ( $this->trashed || ! $this->exists() ) {
            return false;
        }

        return $this
            ->set_trashed( true )
            ->get_repo()
            ->delete( $this, $now );
    }
}
