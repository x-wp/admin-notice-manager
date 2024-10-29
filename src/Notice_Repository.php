<?php //phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
/**
 * Notice_Repository class file.
 *
 * @package eXtended WordPress
 * @subpackage Notice Manager
 */

namespace XWP\ANM;

use XWP\Helper\Traits\Singleton;
use XWP_Admin_Notice;

/**
 * Storage for admin notices
 *
 * @since 1.0.0
 */
class Notice_Repository {
    use Singleton;

    /**
     * Admin notices
     *
     * @var array<string,array<string,mixed>>
     */
    private array $notices;

    /**
     * Notice data hash
     *
     * @var string
     */
    private string $hash;

    /**
     * Constructor
     */
    protected function __construct() {
        $this->notices = \get_option( 'xwp_anm_notices', array() );
        $this->hash    = $this->hash();

        \add_action( 'shutdown', array( $this, 'persist' ), 99 );
    }

    /**
     * Get the hash of the notices
     *
     * @return string
     */
    private function hash(): string {
        return \md5( \serialize( $this->notices ) );
    }

    /**
     * Append a notice to the repository
     *
     * @param  array<string,mixed> $notice Notice data.
     * @return self
     */
    private function append( array $notice ): self {
        // @phpstan-ignore assign.propertyType
        $this->notices[ $notice['id'] ] = $notice;

        return $this;
    }

    /**
     * Get all notices
     *
     * @return array<string,XWP_Admin_Notice>
     */
    public function all(): array {
        return \array_map(
            static fn( $n ) => new \XWP_Admin_Notice( $n ),
            $this->notices,
        );
    }

    /**
     * Check if a notice exists in the repository.
     *
     * @param  string $id Notice ID.
     * @return bool
     */
    public function has( string $id ): bool {
        return isset( $this->notices[ $id ] );
    }

    /**
     * Get a notice by ID
     *
     * @param  string $id Notice ID.
     * @return ?XWP_Admin_Notice
     */
    public function get( string $id ): ?XWP_Admin_Notice {
        return $this->has( $id ) ? new XWP_Admin_Notice( $this->notices[ $id ] ) : null;
    }

    /**
     * Clear all notices
     *
     * @param  bool $force Force the clear.
     * @return void
     */
    public function clear( bool $force = false ): void {
        $this->notices = array();
        $this->hash    = \md5( \uniqid( 'xwp-ntc' ) );

        $this->persist( $force );
    }

    /**
     * Create a notice
     *
     * @param  XWP_Admin_Notice $n Notice object.
     * @return self
     *
     * @throws \InvalidArgumentException If notice ID Exists.
     */
    public function create( XWP_Admin_Notice &$n ): self {
        if ( $this->has( $n->get_id() ) ) {
            throw new \InvalidArgumentException( 'Notice ID is required' );
        }

        return $this->append( $n->apply_changes()->get_data() );
    }

    /**
     * Read a notice
     *
     * @param  XWP_Admin_Notice $n Notice object.
     * @return void
     *
     * @throws \InvalidArgumentException If the notice ID is invalid.
     */
    public function read( XWP_Admin_Notice &$n ): void {
        if ( ! $n->get_id() || ! $this->has( $n->get_id() ) ) {
            throw new \InvalidArgumentException( 'Invalid notice ID' );
        }

        $n->set_props( $this->notices[ $n->get_id() ] )->set_object_read( true );
    }

    /**
     * Update a notice
     *
     * @param  XWP_Admin_Notice $n Notice object.
     * @return self
     */
    public function update( XWP_Admin_Notice &$n ): self {
        return $this->append( $n->apply_changes()->get_data() );
    }

    /**
     * Delete a notice
     *
     * @param  XWP_Admin_Notice $ntc  Notice object.
     * @param  bool             $save Save the changes immediately.
     * @return bool
     */
    public function delete( XWP_Admin_Notice &$ntc, bool $save = false ): bool {
        if ( $this->has( $ntc->get_id() ) ) {
            unset( $this->notices[ $ntc->get_id() ] );
        }

        $this->persist( $save );

        return true;
    }

    /**
     * Persist the notices to the database
     *
     * @param  bool $force Force the update.
     * @return void
     */
    public function persist( bool $force = false ): void {
        if ( ! $force && ! \doing_action( 'shutdown' ) ) {
            return;
        }

        if ( $this->hash === $this->hash() ) {
            return;
        }

        \update_option( 'xwp_anm_notices', $this->notices, autoload: false );

        $this->hash = $this->hash();
    }
}
