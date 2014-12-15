<?php

class TG_REST_API {
	const THEME_OPTION_GROUP = 'health_theme_options';
	const TGAD_PREFIX        = 'mad_';

	private static $instance;

	private function __construct() {}

	public static function get_instance() {
		if( !isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	// switch theme
	static public function switch_theme() {
		if ( !class_exists('Nginx_Mobile_Theme') )
			return;
		if ( !isset($_SERVER['REQUEST_URI']) || !preg_match('#/(tgslide|tgcat_tab)/mobile#' , $_SERVER['REQUEST_URI']))
			return;

		$mobile_theme = apply_filters(
			'nginxmobile_mobile_themes',
			get_option('nginxmobile_mobile_themes', array('smartphone' => 'appwoman-theme-frame-mobile'))
			);
		$mobile_theme = isset($mobile_theme['smartphone']) ? $mobile_theme['smartphone'] : 'appwoman-theme-frame-mobile';
		if ( $mobile_theme ) {
		    $switch_theme = new Megumi_SwitchTheme($mobile_theme);
		    $switch_theme->apply();
		}
	}

	// Nginx Cache Controle
	public function nginx_cache_controle() {
		// nginx reverse proxy cache
		if ( class_exists('NginxChampuru_FlushCache') ){
			$ncf = NginxChampuru_FlushCache::get_instance();
			$ncf->template_redirect();
		}
		if ( class_exists('NginxChampuru_Caching') ){
			$ncc = NginxChampuru_Caching::get_instance();
			$ncc->template_redirect();
		}
	}

	private function is_singular() {
		if ( !isset($_SERVER['REQUEST_URI']) )
			return false;

		$preg_pattern = '#^/wp-json/('.
			implode('|', array(
				'posts',
				'tgrecommend/(pc|mobile)',
				'tgcontent_nav',
				'tgeditor_choice',
				'tgcat_tab/(pc|mobile)',
			))
			.')/(?<id>[\d]+)#';
		if ( preg_match($preg_pattern, $_SERVER['REQUEST_URI'], $matches) )
			$id = $matches['id'];
		else
			$id = false;
		unset($matches);

		return $id;
	}

	public function nginxchampuru_get_post_type( $post_type ) {
		if ( $this->is_singular() ) {
			$post_type = 'is_singular';
		}
		return $post_type;
	}

	public function nginxchampuru_get_post_id( $post_id ) {
		if ( $id = $this->is_singular() ) {
			$post_id = $id;
		}
		return $post_id;
	}

	// patch for json rest api term
	public function json_prepare_term( $data, $term ) {
		if ( $data['ID'] !== intval($term->term_id) )
			$data['ID'] = intval($term->term_id);
		return $data;
	}

	// regist path
	public function register_routes( $routes ) {
		// menus
		$routes['/tgmenu'] = array(
			array( array( $this, 'get_nav_menus'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgmenu/(?P<menu_id>.+)'] = array(
			array( array( $this, 'get_nav_menu_items'), WP_JSON_Server::READABLE ),
		);

		$routes['/tgthememenu'] = array(
			array( array( $this, 'get_nav_menus'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgthememenu/(?P<theme_location>.+)'] = array(
			array( array( $this, 'get_wp_nav_menu'), WP_JSON_Server::READABLE ),
		);

		// sidebars
		$routes['/tgsidebar'] = array(
			array( array( $this, 'get_sidebars_widgets'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgsidebar/(?P<index>.+)'] = array(
			array( array( $this, 'dynamic_sidebar'), WP_JSON_Server::READABLE ),
		);

		// theme option
		$routes['/tgthemeoption'] = array(
			array( array( $this, 'get_theme_options'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgthemeoption/(?P<option_name>.+)'] = array(
			array( array( $this, 'get_theme_option'), WP_JSON_Server::READABLE ),
		);

		// ad
		$routes['/tgad'] = array(
			array( array( $this, 'get_tgad_options'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgad/(?P<option_name>.+)'] = array(
			array( array( $this, 'get_tgad_option'), WP_JSON_Server::READABLE ),
		);

		// pickup
		$routes['/tgpickup'] = array(
			array( array( $this, 'get_tgpickup'), WP_JSON_Server::READABLE ),
		);

		// recomend
		$routes['/tgrecommend/(?P<device>pc|mobile)/(?P<id>\d.+)'] = array(
			array( array( $this, 'get_recommend'), WP_JSON_Server::READABLE ),
		);

		// external-site
		$routes['/posts/(?P<id>\d.+)/external'] = array(
			array( array( $this, 'get_external_site'), WP_JSON_Server::READABLE ),
		);

		// Related Menu
		$routes['/tgrelated_menu'] = array(
			array( array( $this, 'get_tgrelated_menu'), WP_JSON_Server::READABLE ),
		);

		// adjacent_posts_rel_link
		$routes['/tgcontent_nav/(?P<id>\d+)'] = array(
			array( array( $this, 'get_health_content_nav'), WP_JSON_Server::READABLE ),
		);
		$routes['/posts/(?P<id>\d+)/(?P<adjacent>prev|next)'] = array(
			array( array( $this, 'adjacent_posts_rel_link'), WP_JSON_Server::READABLE ),
		);

		// slide post list
		$routes['/tgslide/(?P<device>pc|mobile)'] = array(
			array( array( $this, 'get_slide_post_list'), WP_JSON_Server::READABLE ),
		);

		// carousel post list
		$routes['/tgcarousel'] = array(
			array( array( $this, 'get_carousel_post_list'), WP_JSON_Server::READABLE ),
		);

		// editor choice
		$routes['/tgeditor_choice/(?P<device>pc|mobile)'] = array(
			array( array( $this, 'get_editor_choice'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgeditor_choice/(?P<id>\d+)'] = array(
			array( array( $this, 'get_editor_choice_with_id'), WP_JSON_Server::READABLE ),
		);

		// category tab
		$routes['/tgcat_tab/(?P<device>pc|mobile)'] = array(
			array( array( $this, 'get_category_post_list'), WP_JSON_Server::READABLE ),
		);
		$routes['/tgcat_tab/(?P<device>pc|mobile)/(?P<id>\d+)'] = array(
			array( array( $this, 'get_category_post_list_with_id'), WP_JSON_Server::READABLE ),
		);

		return $routes;
	}

	// menus
	public function get_nav_menus( $_headers ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT t.term_id as ID, t.name, t.slug
			 FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			 where tt.taxonomy = %s",
			 'nav_menu');
		$menus = $wpdb->get_results($sql);
		return $menus;
	}

	public function get_nav_menu_items( $menu_id, $_headers ) {
		$menu = wp_get_nav_menu_object($menu_id);
		$menu_items = 
			( $menu && ! is_wp_error($menu) )
			? wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) )
			: array();
		return $menu_items;
	}

	public function get_wp_nav_menu( $theme_location, $_headers ) {
		$menu_items = wp_nav_menu(array(
			'theme_location'  => $theme_location,
			'echo'            => false,
			'fallback_cb'     => '',
			'container'       => '',
		));
		return array('name' => $theme_location, 'content' => $menu_items);
	}

	// sidebars
	public function get_sidebars_widgets( $_headers = array() ) {
		return wp_get_sidebars_widgets();
	}

	public function dynamic_sidebar( $index, $_headers ) {
		$sidebars = $this->get_sidebars_widgets();
		if ( !isset($sidebars[$index]) )
			return new WP_Error( 'tg_rest_api_tgsidebar_invalid_index', __( 'Invalid index.' ), array( 'status' => 400 ) );

		ob_start();
		dynamic_sidebar( $index );
		$content = ob_get_contents();
		ob_end_clean();
		return array( 'name' => $index, 'content' => $content );
	}

	// theme option
	private function get_theme_options_name() {
		static $health_theme_options;
		global $new_whitelist_options;

		if ( isset($health_theme_options) )
			return $health_theme_options;

		$new_whitelist_options_old = $new_whitelist_options;
		if (function_exists('health_register_setting'))
			health_register_setting();
		$health_theme_options =
			isset($new_whitelist_options[self::THEME_OPTION_GROUP])
			? $new_whitelist_options[self::THEME_OPTION_GROUP]
			: array();
		$health_theme_options =
			isset($new_whitelist_options[self::THEME_OPTION_GROUP])
			? $new_whitelist_options[self::THEME_OPTION_GROUP]
			: array();
		return $health_theme_options;
	}

	public function get_theme_options( $_headers = array() ) {
		$health_theme_options_key = $this->get_theme_options_name();
		$health_theme_options = array();
		foreach( $health_theme_options_key as $option_name ) {
			$value = $this->get_theme_option( $option_name );
			if ( ! is_wp_error($value) )
				$health_theme_options[] = $value;
		}
		return $health_theme_options;
	}

	public function get_theme_option( $option_name, $_headers = array() ) {
		static $health_theme_options_key;

		if ( !isset($health_theme_options_key) ) {
			$health_theme_options_key = $this->get_theme_options_name();
			if (function_exists('health_register_setting'))
				health_register_setting();
			if (function_exists('health_theme_options_fields'))
				health_theme_options_fields();
		}

		if ( !in_array($option_name ,$health_theme_options_key) )
			return new WP_Error( 'tg_rest_api_tgthemeoption_invalid_option_name', __( 'Invalid option name.' ), array( 'status' => 400 ) );

		return array('name' => $option_name, 'value' => get_option($option_name));
	}

	// ad
	private function get_tgad_options_name() {
		static $option_names;
		global $wpdb;

		if ( isset($option_names) )
			return $option_names;

		$sql = $wpdb->prepare(
			"SELECT option_name
			 FROM {$wpdb->options}
			 where option_name like %s",
			 self::TGAD_PREFIX.'%');
		$option_names = $wpdb->get_col($sql);
		return $option_names ? $option_names : array();
	}

	public function get_tgad_options( $_headers = array() ) {
		$tgad_options_key = $this->get_tgad_options_name();
		$tgad_options = array();
		foreach( $tgad_options_key as $option_name ) {
			$value = $this->get_tgad_option( $option_name );
			if ( ! is_wp_error($value) )
				$tgad_options[] = $value;
		}
		return $tgad_options;
	}

	public function get_tgad_option( $option_name, $_headers = array() ) {
		static $tgad_options;
		if ( !isset($tgad_options) )
			$tgad_options = $this->get_tgad_options_name();
		if ( !in_array($option_name, $tgad_options) ) {
			return new WP_Error( 'tg_rest_api_tgad_invalid_option_name', __( 'Invalid option name.' ), array( 'status' => 400 ) );
		}
		return array('name' => $option_name, 'value' => get_option($option_name));
	}

	// pickup
	public function get_tgpickup( $_headers ) {
		if ( !function_exists('health_get_pickup'))
			return new WP_Error( 'tg_rest_api_tgpickup', __( 'Function health_get_pickup() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_pickup();
		return array('content' => $content ? $content : '');
	}

	// recomend
	public function get_recommend( $device, $id, $_headers ) {
		global $posts, $post;
		$id = intval($id);
		if ( !function_exists('health_get_related_post_list'))
			return new WP_Error( 'tg_rest_api_tgrecommend', __( 'Function health_get_related_post_list() is not exists.' ), array( 'status' => 400 ) );
		$post = get_post($id);
		$posts = array($post);
		$content = health_get_related_post_list( array( 'device' => $device ) );
		return array('post_id' => $id, 'content' => $content ? $content : '');
	}

	// external-site
	public function get_external_site( $id, $_headers ) {
		global $posts, $post;
		$id = intval($id);
		if ( !function_exists('health_get_external_site'))
			return new WP_Error( 'tg_rest_api_external_site', __( 'Function health_get_external_site() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_external_site( array( 'id' => $id ) );
		return array('post_id' => $id, 'content' => $content ? $content : '');
	}


	// Related Menu
	public function get_tgrelated_menu( $_headers ) {
		if ( !function_exists('health_get_related_menu'))
			return new WP_Error( 'tg_rest_api_tgrelated_menu', __( 'Function health_get_related_menu() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_related_menu();
		return array('content' => $content ? $content : '');
	}

	// adjacent_posts_rel_link
	public function get_health_content_nav( $id, $_headers ) {
		global $posts, $post;
		$id = intval($id);
		if ( !function_exists('get_health_content_nav'))
			return new WP_Error( 'tg_rest_api_content_nav', __( 'Function get_health_content_nav() is not exists.' ), array( 'status' => 400 ) );
		$post = get_post($id);
		$posts = array($post);
		$content = get_health_content_nav();
		return array('post_id' => $id, 'content' => $content ? $content : '');
	}

	public function adjacent_posts_rel_link( $id, $adjacent, $_headers ) {
		global $posts, $post;
		$id = intval($id);
		$post = get_post($id);
		$posts = array($post);

		$current_post = $post;
		if ( $current_post )
			$current_post->permalink = get_permalink($current_post->ID);

		$in_same_term = false;
		$excluded_terms = '';
		$previous = ('prev' === $adjacent);

		if ( $previous && is_attachment() && $post )
			$post = get_post( $post->post_parent );
		else
			$post = get_adjacent_post( $in_same_term, $excluded_terms, $previous );

		if ($post)
			$post->permalink = get_permalink($post->ID);

		return array('current' => $current_post, $adjacent => $post);
	}

	// slide post list
	public function get_slide_post_list( $device, $_headers ) {
		$content = '';
		if ( !function_exists('health_get_slide_post_list'))
			return new WP_Error( 'tg_rest_api_tgslide', __( 'Function health_get_slide_post_list() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_slide_post_list( array( 'device' => $device ) );
		return array('content' => $content ? $content : '');
	}

	// carousel post list
	public function get_carousel_post_list( $_headers ) {
		if ( !function_exists('health_get_carousel_post_list'))
			return new WP_Error( 'tg_rest_api_tgcarousel', __( 'Function health_get_carousel_post_list() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_carousel_post_list();
		return array('content' => $content ? $content : '');
	}

	// editor choice
	public function get_editor_choice( $device, $_headers ) {
		$content = '';
		if ( !function_exists('health_get_editor_choice'))
			return new WP_Error( 'tg_rest_api_tgeditor_choice', __( 'Function health_get_editor_choice() is not exists.' ), array( 'status' => 400 ) );
		$content = health_get_editor_choice( '', '', $device );
		return array('content' => $content ? $content : '');
	}

	public function get_editor_choice_with_id( $id, $_headers) {
		global $posts, $post;
		$id = intval($id);
		if ( !function_exists('health_get_editor_choice'))
			return new WP_Error( 'tg_rest_api_tgeditor_choice', __( 'Function health_get_editor_choice() is not exists.' ), array( 'status' => 400 ) );
		$post = get_post($id);
		$posts = array($post);
		$content = health_get_editor_choice();
		return array('post_id' => $id, 'content' => $content ? $content : '');
	}

	// category tab
	public function get_category_post_list( $device, $_headers) {
		$content = '';
		switch ($device) {
		case 'pc':
			if ( !function_exists('health_get_category_induction_post_list'))
				return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Function health_get_category_induction_post_list() is not exists.' ), array( 'status' => 400 ) );
			$content = health_get_category_induction_post_list();
			break;
		case 'mobile':
			if ( !function_exists('health_get_category_posts_tab'))
				return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Function health_get_category_posts_tab() is not exists.' ), array( 'status' => 400 ) );
			$content = health_get_category_posts_tab();
			break;
		default:
			return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Invalid device.' ), array( 'status' => 400 ) );
		}
		return array('content' => $content ? $content : '');
	}

	public function get_category_post_list_with_id( $device, $id, $_headers) {
		global $posts, $post;
		$id = intval($id);
		$post = get_post($id);
		$posts = array($post);
		switch ($device) {
		case 'pc':
			if ( !function_exists('health_get_category_induction_post_list'))
				return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Function health_get_category_induction_post_list() is not exists.' ), array( 'status' => 400 ) );
			$content = health_get_category_induction_post_list();
			break;
		case 'mobile':
			if ( !function_exists('health_get_category_posts_tab'))
				return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Function health_get_category_posts_tab() is not exists.' ), array( 'status' => 400 ) );
			$content = health_get_category_posts_tab();
			break;
		default:
			return new WP_Error( 'tg_rest_api_tgcat_tab', __( 'Invalid device.' ), array( 'status' => 400 ) );
		}
		return array('post_id' => $id, 'content' => $content ? $content : '');
	}
}