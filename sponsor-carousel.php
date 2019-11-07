<?php
/**
 * Plugin Name: Sponsor Carousel
 * Version: 1.0.0
 */

class SponsorCarousel {

    const VERSION = '1.0.0';

    public function __construct() {
        add_action('init', [$this, 'register']);
        add_shortcode('sponsor', [$this, 'displayCarousel']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetadata']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    function register() {
        // Start our post type and our taxonomies
        $labels = array(
            'name'               => _x( 'Sponsors', 'post type general name', 'sponsor-carousel' ),
            'singular_name'      => _x( 'Sponsor', 'post type singular name', 'sponsor-carousel' ),
            'menu_name'          => _x( 'Sponsors', 'admin menu', 'sponsor-carousel' ),
            'name_admin_bar'     => _x( 'Sponsor', 'add new on admin bar', 'sponsor-carousel' ),
            'add_new'            => _x( 'Add New', 'sponsor', 'sponsor-carousel' ),
            'add_new_item'       => __( 'Add New Sponsor', 'sponsor-carousel' ),
            'new_item'           => __( 'New Sponsor', 'sponsor-carousel' ),
            'edit_item'          => __( 'Edit Sponsor', 'sponsor-carousel' ),
            'view_item'          => __( 'View Sponsor', 'sponsor-carousel' ),
            'all_items'          => __( 'All Sponsors', 'sponsor-carousel' ),
            'search_items'       => __( 'Search Sponsors', 'sponsor-carousel' ),
            'parent_item_colon'  => __( 'Parent Sponsors:', 'sponsor-carousel' ),
            'not_found'          => __( 'No sponsors found.', 'sponsor-carousel' ),
            'not_found_in_trash' => __( 'No sponsors found in Trash.', 'sponsor-carousel' )
        );
    
        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Description.', 'sponsor-carousel' ),
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => array( 'slug' => 'sponsor' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title', 'thumbnail']
        );
    
        register_post_type( 'sponsor', $args );

        // Add sponsor group taxonomy
        $labels = array(
            'name'              => _x( 'Sponsor Groups', 'taxonomy general name', 'textdomain' ),
            'singular_name'     => _x( 'Sponsor Group', 'taxonomy singular name', 'textdomain' ),
            'search_items'      => __( 'Search Sponsor Groups', 'textdomain' ),
            'all_items'         => __( 'All Sponsor Groups', 'textdomain' ),
            'parent_item'       => __( 'Parent Sponsor Group', 'textdomain' ),
            'parent_item_colon' => __( 'Parent Sponsor Group:', 'textdomain' ),
            'edit_item'         => __( 'Edit Sponsor Group', 'textdomain' ),
            'update_item'       => __( 'Update Sponsor Group', 'textdomain' ),
            'add_new_item'      => __( 'Add New Sponsor Group', 'textdomain' ),
            'new_item_name'     => __( 'New Sponsor Group Name', 'textdomain' ),
            'menu_name'         => __( 'Sponsor Group', 'textdomain' ),
        );
    
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => array( 'slug' => 'sponsor-group' ),
        );
    
        register_taxonomy( 'sponsor-group', array( 'sponsor' ), $args );

    }

    function addMetaBoxes() {
        add_meta_box(
            'sponsor-carousel-details', // Unique ID
            'Details',  // Box title
            [$this, 'displayMetaboxContent'], // Content callback, must be of type callable
            'sponsor', // Post type,
            'normal',
            'high'
        );
    }

    function displayMetaboxContent($post) {
        $value = get_post_meta($post->ID, 'sponsor_target_url', true);
    ?>
        <p>
            <label for="sponsor_target_url">Target URL:</label>
            <input type="text" name="sponsor_target_url" id="sponsor_target_url" class="postbox" value="<?= $value ?>" />
        </p>
    <?php
    }

    function saveMetadata($postId) {
        if (array_key_exists('sponsor_target_url', $_POST)) {
            update_post_meta(
                $postId,
                'sponsor_target_url',
                $_POST['sponsor_target_url']
            );
        }
    }

    function enqueue() {
        wp_register_style('sponsor-carousel', plugin_dir_url(__FILE__).'/sponsor-carousel.css', [], self::VERSION);
        wp_register_script('sponsor-carousel', plugin_dir_url(__FILE__).'/sponsor-carousel.js', ['jquery', 'slick'], self::VERSION);
        wp_register_style('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
        wp_register_script('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1');
    }

    function displayCarousel($atts, $content = null) {

        $a = shortcode_atts([
            'group' => '',
            'title' => 'Our Sponsors'
        ], $atts);

        wp_enqueue_style('sponsor-carousel');
        wp_enqueue_style('slick');
        wp_enqueue_script('jquery');
        wp_enqueue_script('slick');
        wp_enqueue_script('sponsor-carousel');

        $args = [
            'post_type' => 'sponsor',
            'nopaging' => false,
            'orderby' => 'post_title',
            'order' => 'asc'
        ];

        if ($a['group'] !== '') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'sponsor-group',
                    'field' => 'slug',
                    'terms' => $a['group']
                ]
            ];
        }
        
        // Build my output here
        $sponsors = get_posts($args);

        if ($a['title'] !== '') {
            $output = '<h2>'.$a['title'].'</h2>';
        }

        $output .= '<div class="sponsors">';
        foreach($sponsors as $sponsor) {
            $title = $sponsor->post_title;
            $image = get_the_post_thumbnail_url($sponsor->ID, 'large');
            $url = get_post_meta($sponsor->ID, 'sponsor_target_url', true);

            // TODO: Handle missing data, like no image or no URL

            // Generate output, using HEREDOC
            $output .= <<<SPONSOR
            <div style="background-image:url({$image});" class="one-sponsor">
                <a href="{$url}" target="_blank" class="content">
                    <div class="sponsor-title">{$title}</div>
                </a>
            </div>
SPONSOR;
        }
        $output .= '</div>';

        return $output;
    }
}

$sponsorCarousel = new SponsorCarousel();
