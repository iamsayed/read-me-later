<?php
/**
 * Plugin Name: Read Me Later
 * Plugin URI: http://sayedrahman.com
 * Description: This plugin allow you to add blog posts in read me later lists using ajax
 * Version: 1.0.0
 * Author: Sayed Rahman
 * Author URI: http://sayedrahman.com
 * License: GPL3
 */

define( 'RML_DIR', plugin_dir_path( __FILE__ ) );
require(RML_DIR.'widgets.php');

class ReadMeLater {
	
    /**
     * Action hooks
     */
	public function run() {
		
		// Enqueue plugin styles and scripts
        	add_action( 'plugins_loaded', array( $this, 'rml_scripts' ) );
        	add_action( 'plugins_loaded', array( $this, 'rml_styles' ) );
		
		// Setup filter hook to show Read Me Later link
		add_filter( 'the_excerpt', array( $this, 'rml_button' ) );
		add_filter( 'the_content', array( $this, 'rml_button' ) );

		// Setup Ajax action hook
		add_action( 'wp_ajax_read_me_later', array( $this, 'read_me_later' ) );
		
	} 
	
    /**
     * Register plugin styles and scripts
     */
	public function register_rml_scripts() {
		wp_register_script( 'rml-script', plugins_url( 'js/read-me-later.js', __FILE__ ), array('jquery'), null, true );
		wp_register_style( 'rml-style', plugin_dir_url( __FILE__ ) .'css/read-me-later.css' );
	}
	
    /**
     * Enqueues plugin-specific scripts.
     */
    public function rml_scripts() {        
        wp_enqueue_script( 'rml-script' );
		wp_localize_script( 'rml-script', 'rml_obj', array( 'ajax_url' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('rml-nonce') ) );
    } 
	
    /**
     * Enqueues plugin-specific styles.
     */
    public function rml_styles() {         
        wp_enqueue_style( 'rml-style' ); 
    } 
    
        /**
         * Adds a read me later button at the bottom of each post excerpt that allows logged in users
         * to save those posts in their read me later list.
         *
	 * @param string $content
	 * @return string
	 */
	public function rml_button( $content ) {
	
		// Show read me later link only when user is logged in
		if( is_user_logged_in() && get_post_type() == post ) {
			$html .= '<a href="#" class="rml_bttn" data-id="' . get_the_id() . '">Read Me Later</a>';
			$content .= $html;
		}
		return $content;
		
	} 
		
	/**
	 * Hook into wp_ajax_ to save post ids, then display those posts using get_posts() function
	 *
	 * @access public
	 * @return mixed
	 */
	public function read_me_later() {
	
		check_ajax_referer( 'rml-nonce', 'security' );
		$rml_post_id = $_POST['post_id']; 
		$echo = array();
		
		if( get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true ) !== null ) {
			$value = get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true );
		}
		
		if( $value ) {
			$echo = $value;
			array_push( $echo, $rml_post_id );
		}
		else {
			$echo = array( $rml_post_id );
		}
		
		update_user_meta( wp_get_current_user()->ID, 'rml_post_ids', $echo );
		$ids = get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true );
		
		function limit_words($string, $word_limit) {
			$words = explode(' ', $string);
			return implode(' ', array_slice($words, 0, $word_limit));
		}
		
		// Query read me later posts
		$args = array( 
			'post_type' => 'post',
			'orderby' => 'DESC', 
			'posts_per_page' => -1, 
			'numberposts' => -1,
			'post__in' => $ids
		);
		
		$rmlposts = get_posts( $args );
		if( $ids ) :
			global $post;
			foreach ( $rmlposts as $post ) :
				setup_postdata( $post );
				$img = wp_get_attachment_image_src( get_post_thumbnail_id() ); 
				?>			
				<div class="rml_posts">					
					<div class="rml_post_content">
						<h5><a href="<?php echo get_the_permalink(); ?>"><?php the_title(); ?></a></h5>
						<p><?php echo limit_words(get_the_excerpt(), '20'); ?></p>
					</div>
					<img src="<?php echo $img[0]; ?>" alt="<?php echo get_the_title(); ?>" class="rml_img">					
				</div>
			<?php 
			endforeach; 
			wp_reset_postdata(); 
		endif;		

		// Always die in functions echoing Ajax content
		die();
		
	} 	
}
$rml = new ReadMeLater();
$rml->register_rml_scripts();
$rml->run();
?>
