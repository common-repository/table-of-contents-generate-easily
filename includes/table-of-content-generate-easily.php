<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Table_of_Contents_Generate_Easily {

	/**
	 * The single instance of Table_of_Contents_Generate_Easily.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;
	/** plugin title 
	*/
	public $plugin_title;
	public $option_name;
	public $localisation_domain;
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'Table_of_Contents_Generate_Easily';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->plugin_title = 'Table of Contents Generator easily';
		$this->option_name = 'toc_generator_options';
		$this->localisation_domain = 'table-of-content-generate-easily';
		
		// when plguin is activated.
		register_activation_hook( $this->file, array( $this, 'plugin_activate') );
		// Handle localisation
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		// Add a menu to settings
		add_action('admin_menu', array( $this, 'add_admin_menu') );
		// enqueue scripts & css
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
		// for generating table of contents
		add_filter( 'the_content', array( $this, 'the_content' ), 999 );
		// add meta box for supporting post types
		add_action( 'add_meta_boxes', 	array( $this, 'add_meta_box') );
		add_action( 'save_post', 		array( $this, 'save_meta_box_data'),10, 3 );
	} // End __construct ()

	/**  when plugin is activated */
	public function plugin_activate(){
		// set default options
		$options["post_types"]['post'] = 'post';
		$options["position"] = 'top';
		$options["header"]       = __( 'Table of Contents', $this->localisation_domain );
		$options["toggle"]           = '1';
		$options["init_hide"]           = '1';
		$options["style"]           = 'style1';
		
		update_option( $this->option_name, $options);
	}
	/** add meta box for supporting post types */
	public function add_meta_box(){
		$configurations = get_option( $this->option_name ); 
		
		add_meta_box( 
		'table-of-content-generate-easily-meta-box',
		__( 'Options of "Table of Contents"', $this->localisation_domain ),
		array( $this, 'displayMetaboxes' ),
		$configurations['post_types'],
		'normal',
		'high',
		null );
	}
	public function displayMetaboxes(){
		global $post;
		$post_id = $post;
		if (is_object($post_id)) $post_id = $post_id->ID;
		
		wp_nonce_field( plugin_basename( $this->file ), 'nonce_toc_generator_disabled' );
		$disable_toc = get_post_meta( $post_id, 'toc_generator_disabled' , true ); ?>
		<div>
        <input id="toc_generator_disabled" name="toc_generator_disabled" type="checkbox" value="true" <?php checked('true',$disable_toc);?> />
        <label for="toc_generator_disabled"><?php _e("Disable TOC generator on this post", $this->localisation_domain ); ?></label>
		</div>
		<?php
	}
	/** save meta datas when posts saving */
	public function save_meta_box_data( $post_id, $post, $update ){
		// Check if our nonce is set.
		if ( ! isset( $_POST['nonce_toc_generator_disabled'] ) ) {
			return;
		}
		
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['nonce_toc_generator_disabled'], plugin_basename( $this->file ) ) ) {
			return;
		}
		
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post' ) ) {
			return;
		}
		
		$name = 'toc_generator_disabled';
		if ( isset( $_POST[$name] ) ) {
			$new = ( $_POST[$name] == 'true' ? 'true' : 'false' );
			$old = get_post_meta( $post_id, $name , true );
			
			if ( $new !='' && $new != $old ) {
				update_post_meta( $post_id, $name, $new );
			} elseif ('' == $new && $old) {
				delete_post_meta( $post_id, $name );
			}
		} else {
			delete_post_meta( $post_id, $name );
		}
	}
	
	/** Add a menu 'TOC Generator' to settings */
	public function add_admin_menu(){
		if (current_user_can('manage_options'))
			add_options_page("TOC Generator", __("TOC Generator", $this->localisation_domain ), 'manage_options', $this->file, array( $this, 'options') );
	}
	/** Enqueue scripts & css */
	public function enqueue_scripts(){
		$configurations = get_option( $this->option_name ); 
		
		wp_enqueue_style( 'table-of-content-generate-easily-css', $this->assets_url . $configurations['style'] . '.css', false, $this->_version, 'screen' );
		
		// not load js if the user don't allow to toggle visibility
		if($configurations["toggle"]):
			wp_enqueue_script( 'table-of-content-generate-easily-js', $this->assets_url . 'scripts.js', array( 'jquery' ), $this->_version, false );
		endif;
	}
	/** Filter contents to generator TOC */
	public function the_content( $content ){
		global $post;
		$post_id = $post;
		if (is_object($post_id)) $post_id = $post_id->ID;
		
		$configurations = get_option( $this->option_name ); 
		$disable_toc = get_post_meta(  $post_id , 'toc_generator_disabled' , true ); 
		$current_post_type = get_post_type( $post_id );
		// do not generate TOC if this post type is disabled or this post is disabled.
		if( !in_array( $current_post_type , $configurations['post_types'] ) || $disable_toc || !is_single() ) return $content ;
		
		$header_list = $this->get_header_list( $content );
		if(count($header_list)>0 && is_array( $header_list ) ):
			
			$toc_list_return = $this->create_toc_list($header_list, $content, 1);

			if(empty($toc_list_return)):
				$the_toc = '';
			else:
				$style = '';
				if($configurations["position"] == 'left'):
					$style = ' style="float:left;"';
				elseif($configurations["position"] == 'right'):
					$style = ' style="float:right;"';
				endif;

				$the_toc = '<div id="table-of-contents-easily" class="table-of-contents-easily"'.$style.'>';
				$the_toc .= '<div class="the-toc-title">'.$configurations["header"].'</div>';
				
				if($configurations["toggle"]):
					if( $configurations["init_hide"] ):
						$the_toc .= '<span class="the-toc-toctoggle">[<a id="the-toc-togglelink" href="javascript:content_index_toggleToc()">'.__("Show", $this->localisation_domain ).'</a>]</span>';
						$the_toc .= '<ul id="the-toc-body" style="display: none;">'.$toc_list_return.'</ul>';
					else:
						$the_toc .= '<span class="the-toc-toctoggle">[<a id="the-toc-togglelink" href="javascript:content_index_toggleToc()">'.__("Hide", $this->localisation_domain ).'</a>]</span>';
						$the_toc .= '<ul id="the-toc-body">'.$toc_list_return.'</ul>';
					endif;
				else:
					$the_toc .= '<ul id="the-toc-body">'.$toc_list_return.'</ul>';
				endif;
				
				$the_toc .= '</div>';
				
				if($configurations["position"] == 'top'):
					$the_toc .= '<div style="clear:both;"></div>';
				endif;
				
			endif;
			$content = $the_toc . $content;
		endif;
			
		return $content;
	}
	/** create table of contens */
	public function create_toc_list( $list,&$content, $deep = 1 ){
		$configurations = get_option( $this->option_name ); 
		$out = '';
		foreach($list as $hk => $hv){
			$content = str_replace($hv["source"], $hv["replace"], $content);
			
			$out .= '<li class="toc-body-level-'.$deep.'">';
			$out .= '<a href="'.get_permalink()."#".$hv["slug"].'" title="'.$hv["content"].'" class="the-toc-link"><em>'.$hv["sign"].'&nbsp;</em><span>'.$hv["content"].'</span></a>';
			if(isset($hv["child"])){
				$out .= '<ul class="children">' . $this->create_toc_list( $hv["child"], $content, $deep + 1) . '</ul>';
			}
			$out .= '</li>';
		}
		return $out;
	}
	/** get headers list */
	public function get_header_list( $content ){
		$configurations = get_option( $this->option_name ); 
		if( !preg_match_all( "/<h(\d)([^>]*)>(.*)<\/h\d>/isU", $content, $matched, PREG_SET_ORDER ) ) return '';
		
		$header_list = $matched;
		$new_hlist = array();
		$_tp_level = 0;
		foreach($header_list as $hk => $hv) {
			$index = $hk + 1;
		
			$hv[2] = preg_match("/id=/", $hv[2]) ? preg_replace("/.*id=\"?([^\"]*)\"?.*/is", "$1", $hv[2]) : '';		
			$hv[3] = trim(strip_tags($hv[3]));
			
			$replace = preg_replace("/<\/?a[^>]*>/i", '', $hv[0]);
			if(empty($hv[2])) {
				if(empty($hv[3])) $hv[3] = "toc_id_" . $index;
				$idIndex = $hv[3];
				$replace = preg_replace("/(<h\d)([^>]*>.*<\/h\d>)/is", "$1".' id="'.$idIndex.'"'."$2", $replace, 1);
			} else {
				$idIndex = $hv[2];
			}
			
			$hv["index"] = $index;
			$hv["source"] = $hv[0];
			$hv["replace"] = $replace;
			$hv["slug"] = $idIndex;
			$hv["content"] = $hv[3];
			$hv["level"] = intval($hv[1]);
			
			unset($hv[0], $hv[1], $hv[2], $hv[3]);
			
			$deep = $hv["level"] - $_tp_level;
			$new_hlist[] = $hv;
			
			$_tp_level = $hv["level"];
		}

		$header_list = $new_hlist;
		if( count($header_list)>0 && $header_list[0]['level'] >1 ){
			$header_list[0]['level'] = 1;
		}
		
		$_tp = array();
		$_tp_sign = array();
		foreach($header_list as $hk => $hv){
			$parent = $hv["level"] - 1;
			if($parent > 0){
				while(!isset($_tp[$parent]) && $parent > 1){
					$parent --;
				}
				
				$header_list[$_tp[$parent]]["child"][$hk] = $hv;
				$header_list[$hk] = &$header_list[$_tp[$parent]]["child"][$hk];
			}
		
			$_tp_sign = array_slice($_tp_sign, 0, $hv["level"], TRUE);
			if(empty($_tp_sign[$hv["level"]])){
				$_tp_sign[$hv["level"]] = 0;
			}
			$_tp_sign[$hv["level"]]++;
			$header_list[$hk]["sign"] = join(".", $_tp_sign);
			
			$_tp[$hv["level"]] = $hk;
			$_tp = array_slice($_tp, 0, $hv["level"], TRUE);
		}
		foreach($header_list as $hk => $hv)
			if($hv["level"] > 1)
				unset($header_list[$hk]);
		
		return $header_list;
	}
	/** show admin options */
	public function options(){
		global $_POST;
		$msg = '';
		if(isset($_POST["action"]) && $_POST['action'] == 'save' ):
			$this->configurations_save();
			$msg = __('Configurations saved.', $this->localisation_domain );
		endif;
		?>
		<div class="wrap">
			<h2><?php echo $this->plugin_title; ?></h2>
			<?php if( $msg !='' ): ?>
				<div class="updated" style="padding:10px;"><?php echo $msg; ?></div>
			<?php endif; ?>
		<?php $configurations = get_option( $this->option_name ); 
		if( $configurations && (!isset( $configurations['header'] ) && $configurations['header'] == '') ) $configurations['header'] = __( 'Table of Contents', $this->localisation_domain );;
		if( $configurations && (!isset( $configurations['position'] ) && $configurations['position'] == '') ) $configurations['position'] = 'top';
		if( $configurations && (!isset( $configurations['style'] ) || $configurations['style'] == '') ) $configurations['style'] = 'style1';
		?>
		<form method="post">
		<div class="widget">
			<table class="widefat" width="100%" border="0" cellspacing="10" cellpadding="0">
				<tbody>
					<tr>
						<th style="text-align:right;"><?php _e("Support post types", $this->localisation_domain );?></th>
						<td>
							<?php $args = array('public'   => true );
							$post_types = get_post_types( $args );
							// skip the attachment
							foreach( $post_types as $type ): if( $type == 'attachment' ) continue;?>
							<input id="toc-settings[post_types][<?php echo $type;?>]" name="toc-settings[post_types][<?php echo $type;?>]" type="checkbox" value="<?php echo $type;?>" <?php checked($type, $configurations&&isset($configurations['post_types'][$type])?$configurations['post_types'][$type]:'', TRUE );?> />&nbsp;<label for="toc-settings[post_types][<?php echo $type;?>]"><?php echo $type; ?></label><br />
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"><?php _e("TOC Position", $this->localisation_domain);?></th>
						<td>
							<input id="position_left" name="toc-settings[position]" type="radio" value="left" <?php checked('left',$configurations&&isset($configurations['position'])?$configurations['position']:'');?> /><label for="position_left"><?php _e("Top Left", $this->localisation_domain);?></label><br/>
							<input id="position_top" name="toc-settings[position]" type="radio" value="top" <?php checked('top',$configurations&&isset($configurations['position'])?$configurations['position']:'');?> /><label for="position_top"><?php _e("Top", $this->localisation_domain);?></label><br/>
							<input id="position_right" name="toc-settings[position]" type="radio" value="right" <?php checked('right',$configurations&&isset($configurations['position'])?$configurations['position']:'');?> /><label for="position_right"><?php _e("Top Right", $this->localisation_domain);?></label></td><br/>
					</tr>
					<tr>
						<th style="text-align:right;"><?php _e("Header of TOC", $this->localisation_domain );?></th>
						<td>
							<input id="toc-settings[header]" name="toc-settings[header]" type="text" value="<?php echo $configurations&&isset($configurations["header"])?esc_html($configurations["header"]):'';?>" style="width:300px;"/><label for="title_text"><?php _e("Empty means default title: Table of Contents", $this->localisation_domain );?></label>
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"><?php _e("Toggle", $this->localisation_domain );?></th>
						<td>
							<input id="toc-settings[toggle]" name="toc-settings[toggle]" type="checkbox" value="1" <?php checked(1, $configurations&&isset($configurations["toggle"])?$configurations["toggle"]:'' );?> /><label for="toc-settings[toggle]"><?php _e("Allow users to toggle the visibility.", $this->localisation_domain );?></label>
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"><?php _e("Initially hide", $this->localisation_domain );?></th>
						<td>
							<input id="toc-settings[init_hide]" name="toc-settings[init_hide]" type="checkbox" value="1" <?php checked(1, $configurations&&isset($configurations["init_hide"])?$configurations["init_hide"]:'' );?> /><label for="toc-settings[init_hide]"><?php _e("Hide", $this->localisation_domain );?></label>
						</td>
					</tr>
					<tr>
						<th style="text-align:right;"><?php _e("Style", $this->localisation_domain);?></th>
						<td>
							<input id="style1" name="toc-settings[style]" type="radio" value="style1" <?php checked('style1',$configurations&&isset($configurations['style'])?$configurations['style']:'');?> /><label for="style1"><?php _e("Style 1: Green", $this->localisation_domain);?></label><br/>
							<input id="style2" name="toc-settings[style]" type="radio" value="style2" <?php checked('style2',$configurations&&isset($configurations['style'])?$configurations['style']:'');?> /><label for="style2"><?php _e("Style 2: Grey", $this->localisation_domain);?></label><br/>
					</tr>
					<tr>
						<th></th>
						<td>
							<input type="hidden" name="action" value="save" />
							<input class="button-primary" type="submit" value="  <?php _e("Save", $this->localisation_domain );?>  " />
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</form>
		</div>
		<?php
	}
	/** save options to database */
	public function configurations_save(){
		global $_POST;
		
		$options["post_types"] 	= $this->validate_post_types($_POST['toc-settings']['post_types']);
		$options["position"] 	= $this->validate_position($_POST['toc-settings']['position']);
		$options["header"]      = sanitize_text_field(trim($_POST['toc-settings']['header']));
		$options["toggle"]      = isset($_POST['toc-settings']['toggle'])?'1':'0';
		$options["init_hide"]   = isset($_POST['toc-settings']['init_hide'])?'1':'0';
		$options["style"] 		= $this->validate_style($_POST['toc-settings']['style']);
		
		update_option( $this->option_name, $options);
		
		return true;
	}
	/** Validate post types */
	public function validate_post_types( $post_types ){
		$system_post_types = get_post_types( array('public'   => true ) );
		
		$new_post_types = array();
		foreach( $post_types as $type ){
			if( in_array( $type, $system_post_types  ) ){
				$new_post_types[$type] = $type ;
			}
		}
		
		return $new_post_types;
	}
	/** Validate Position */
	public function validate_position( $position ){
		if( $position == 'left' || $position == 'top' || $position == 'right' ) return $position ;
		
		return 'top';
	}
	/** Validate style */
	public function validate_style( $style ){
		if( $style == 'style1' || $style == 'style2' ) return $style;
		
		return 'style1';
	}
	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'table-of-content-generate-easily', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Main BBPress_Improvements_for_Yoast Instance
	 *
	 * Ensures only one instance of BBPress_Improvements_for_Yoast is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see BBPress_Improvements_for_Yoast()
	 * @return Main BBPress_Improvements_for_Yoast instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()
}