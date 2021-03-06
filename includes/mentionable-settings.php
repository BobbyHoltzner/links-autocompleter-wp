<?php
/**
 * Settings class
 *
 * @author X-Team <x-team.com
 * @author Shady Sharaf <shady@x-team.com>
 */
class Mentionable_Settings {

	/**
	 * Settings key/identifier
	 */
	const KEY = 'mentionable';

	/**
	 * Plugin settings
	 * @var array
	 */
	public static $options = array();

	/**
	 * Public constructor
	 *
	 * @return \Mentionable_Settings
	 */
	public function __construct() {
		// Register settings page
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Register settings, and fields
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings link
		add_filter( 'plugin_action_links_' . MENTIONABLE_BASENAME, array( $this, 'action_link' ), 10, 4 );

		$defaults = array(
			'post_types'              => array( 'post' ),
			'autocomplete_post_types' => array( 'link' ),
			'load_template'           => false,
			'open_new_tab'            => false,
		);

		self::$options = apply_filters(
			'mentionable_options',
			wp_parse_args(
				(array) get_option( self::KEY, array() ),
				$defaults
			)
		);
	}

	/**
	 * Register menu page
	 *
	 * @action admin_menu
	 * @return void
	 */
	public function register_menu() {
		if ( current_user_can( apply_filters( 'mentionable_cap', 'manage_options' ) ) ) {
			add_options_page(
				__( 'Mentionable', 'mentionable' ),
				__( 'Mentionable', 'mentionable' ),
				'manage_options',
				self::KEY,
				array( $this, 'settings_page' )
			);
		}
	}

	/**
	 * Render settings page
	 * @return void
	 */
	public function settings_page() {
		$tag = version_compare( $GLOBALS['wp_version'], '4.3', '>=' ) ? 'h1' : 'h2';
		?>
		<div class="wrap">
			<?php printf( '<%1$s>%2$s</%1$s>', $tag, esc_html__( 'Mentionable Options', 'mentionable' ) ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::KEY );
				do_settings_sections( self::KEY );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers settings fields and sections
	 * @return void
	 */
	public function register_settings() {
		$section_name = 'post_types';

		register_setting( self::KEY, self::KEY );

		add_settings_section(
			$section_name,
			__( 'Post Types', 'mentionable' ),
			'__return_false',
			self::KEY
		);

		add_settings_field(
			'post_types',
			__( 'Activate for', 'mentionable' ),
			array( $this, 'output_field_post_types' ),
			self::KEY,
			$section_name,
			array(
				'key'         => 'post_types',
				'description' => __( 'Post types which this plugin will be activated for.', 'mentionable' ),
			)
		);

		add_settings_field(
			'autocomplete_post_types',
			__( 'Autocomplete from', 'mentionable' ),
			array( $this, 'output_field_post_types' ),
			self::KEY,
			$section_name,
			array(
				'key'         => 'autocomplete_post_types',
				'description' => __( 'Post types that auto-completion will match against.', 'mentionable' ),
			)
		);

		// Mentionable template
		$section_name = 'templates';

		add_settings_section(
			$section_name,
			__( 'Template', 'mentionable' ),
			'__return_false',
			self::KEY
		);

		add_settings_field(
			'load_template',
			__( 'Load custom template', 'mentionable' ),
			array( $this, 'output_checkbox' ),
			self::KEY,
			$section_name,
			array(
				'key'         => 'load_template',
				'description' => __( 'Replace mentionable tag with custom template (Please see wiki before enabling this)', 'mentionable' ),
			)
		);

		add_settings_field(
			'open_new_tab',
			__( 'Open links in new tab', 'mentionable' ),
			array( $this, 'output_checkbox' ),
			self::KEY,
			$section_name,
			array(
				'key'         => 'open_new_tab',
				'description' => __( 'All links generated by mentionable will be opened in a new tab instead of the current tab.', 'mentionable' ),
			)
		);

	}

	/**
	 * Render Callback for post_types field
	 *
	 * @param $args
	 *
	 * @return void
	 */
	public function output_field_post_types( $args ) {
		global $wp_post_types;
		$slugs = array_keys( $wp_post_types );
		$names = wp_list_pluck( $wp_post_types, 'label' );
		$types = array_combine( $slugs, $names );
		$types = array_diff_key( $types, array_flip( array( 'nav_menu_item', 'revision' ) ) );
		$value = self::$options[ $args['key'] ];

		$output = sprintf( '<select name="mentionable[%s][]" multiple >', esc_attr( $args['key'] ) );
		foreach ( $types as $slug => $name ) {
			$output .= sprintf( '<option value="%1$s" %3$s>%2$s</option>', $slug, $name, selected( in_array( $slug, $value ), true, false ) );
		}
		$output .= '</select>';

		$output .= sprintf(
			'<p class="description">%s</p>',
			$args[ 'description' ]
		);

		echo balanceTags( $output );
	}

	/**
	 * Output an option checkbox
	 *
	 * @param array $args
	 *
	 * @return string $output
	 */
	public function output_checkbox( $args ) {
		$output = sprintf( '<input type="checkbox" name="mentionable[%s]" %s>', esc_attr( $args['key'] ), checked( self::$options[ $args['key'] ] ,'on', false ) );

		$output .= sprintf(
			'<p class="description">%s</p>',
			$args[ 'description' ]
		);

		echo balanceTags( $output );
	}

	/**
	 * Add settings link to plugin list page
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function action_link( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=' . self::KEY ) . '">' . __( 'Settings' ) . '</a>';

		return $links;
	}

}
