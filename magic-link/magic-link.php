<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

if ( strpos( dt_get_url_path(), 'prayer_calendar_app' ) !== false ){
    DT_Prayer_Calendar_Magic_Link::instance();
}

class DT_Prayer_Calendar_Magic_Link
{

    public $magic = false;
    public $parts = false;
    public $title = 'Prayer Calendar App';
    public $root = "prayer_calendar_app";
    public $type = 'daily';
    public $post_type = 'user';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, '_register_type' ], 10, 1 );

        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, '_authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );

        // fail if not valid url
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }

        // fail to blank if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            // @note this returns a blank page for bad url, instead of redirecting to login
            add_filter( 'dt_templates_for_urls', function ( $template_for_url ) {
                $url = dt_get_url_path();
                $template_for_url[ $url ] = 'template-blank.php';
                return $template_for_url;
            }, 199, 1 );
            add_filter( 'dt_blank_access', function(){ return true;
            } );
            add_filter( 'dt_allow_non_login_access', function(){ return true;
            }, 100, 1 );
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_filter( "dt_blank_title", [ $this, "_browser_tab_title" ] );
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key

        // load page elements
        add_action( 'wp_print_scripts', [ $this, '_print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, '_print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, '_register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function _register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => $this->title,
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => $this->root . '_' . $this->type,
            'actions' => [
                '' => 'Manage',
            ],
            'post_type' => $this->post_type,
        ];
        return $types;
    }
    public function _register_url( $template_for_url ){
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( ! $parts ){ // parts returns false
            return $template_for_url;
        }

        // test 2 : only base url requested
        if ( empty( $parts['public_key'] ) ){ // no public key present
            $template_for_url[ $parts['root'] . '/'. $parts['type'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 3 : no specific action requested
        if ( empty( $parts['action'] ) ){ // only root public key requested
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 4 : valid action requested
        $actions = $this->magic->list_actions( $parts['type'] );
        if ( isset( $actions[ $parts['action'] ] ) ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
        }

        return $template_for_url;
    }

    public function _has_access() : bool {
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( $parts ){ // parts returns false
            return true;
        }

        return false;
    }

    public function wp_enqueue_scripts(){
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) !== false ) {
            wp_enqueue_script( 'jquery-ui' );
            wp_enqueue_script( 'jquery-touch-punch');
        }
    }

    public function _header(){
        wp_head();
        $this->header_style();
        $this->header_javascript();
    }
    public function _footer(){
        wp_footer();
    }
    public function _authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->root . '/v1/'.$this->type ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    public function _print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'jquery-ui',
            'lodash',
            'moment',
            'datepicker',
            'site-js',
            'shared-functions',
            'mapbox-gl',
            'mapbox-cookie',
            'mapbox-search-widget',
            'google-search-widget',
            'jquery-cookie',
            'jquery-touch-punch'
        ];

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
    }
    public function _print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'jquery-ui-site-css',
            'site-css',
            'datepicker-css',
            'mapbox-gl-css'
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }


    public function _browser_tab_title( $title ){
        /**
         * Places a title on the web browser tab.
         */
        return __( "Prayer Calendar", 'disciple_tools' );
    }

    public function header_style(){
        ?>
        <style>
            body {
                background-color: white;
                max-width: 800px;
                margin: 0 auto;
            }
        </style>
        <?php
    }
    public function header_javascript(){
        ?>
        <script>
            let jsObject = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [
                    'add' => __( 'Add Prayer Calendar', 'disciple_tools' ),
                ],
            ]) ?>][0]

            jQuery(document).ready(function(){
                clearInterval(window.fiveMinuteTimer)
            })

            window.get_magic = () => {
                jQuery.ajax({
                    type: "POST",
                    data: JSON.stringify({ action: 'get', parts: jsObject.parts }),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
                    }
                })
                    .done(function(data){
                        window.load_magic( data )
                    })
                    .fail(function(e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
            }

            window.log_prayer_action = ( post_id ) => {
                // note parts.post_id is the user_id, not the post_id
                jQuery.ajax({
                    type: "POST",
                    data: JSON.stringify({ action: 'log', parts: jsObject.parts, post_id: post_id }),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
                    }
                })
                    .done(function(data){
                        console.log(data)
                    })
                    .fail(function(e) {
                        console.log(e)
                    })
            }

            window.load_magic = ( data ) => {
                let content = jQuery('#content')
                let spinner = jQuery('.loading-spinner')

                content.empty()
                jQuery.each(data, function(i,v){
                    content.prepend(`
                         <div class="cell prayer-list-wrapper">
                            <div class="draggable ui-widget-content prayer-list" data-value="${v.post_id}" id="item-${v.post_id}">
                                ${v.name}
                            </div>
                         </div>
                     `)
                })

                let prayer_list = jQuery('.prayer-list')

                prayer_list.draggable({
                    axis: "x",
                    revert: true,
                    grid: [200],
                    stop: function(e) {
                        window.log_prayer_action(e.target.dataset.value)
                        jQuery('#item-'+e.target.dataset.value).addClass('checked-off')
                    }
                })
                prayer_list.click(function(e){
                    window.log_prayer_action(e.target.dataset.value)
                    jQuery('#item-'+e.target.dataset.value).addClass('checked-off')
                })

                spinner.removeClass('active')

            }
        </script>
        <?php
        return true;
    }


    public function body(){
        ?>
        <div id="custom-style"></div>
        <style>
            #wrapper {
                margin-top: 1em;
            }
            #content {
                overflow-x: hidden;
            }
            .prayer-list-wrapper {
                background-color: green;
            }
            .prayer-list {
                padding: 1.5em .5em;
                border:1px solid lightgrey;
                background-color: white;
                font-size: 1.3em;
                font-weight: bolder;
            }
            .checked-off {
                margin-left: 40px;
            }
        </style>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title">Prayer List</h2>
                </div>
            </div>
            <div class="grid-x" id="content"><span class="loading-spinner active"></span><!-- javascript container --></div>
        </div>
        <script>
            jQuery(document).ready(function($){
                window.get_magic()
            })
        </script>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'endpoint' ],
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        switch ( $action ) {
            case 'get':
                return $this->endpoint_get( $params['parts'] );
            case 'log':
                if ( ! isset( $params['post_id'] ) ){
                    return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
                }
                return $this->endpoint_log( $params['parts'], $params['post_id'] );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function endpoint_get( $parts ) {
        global $wpdb;
//        $user_id = $this->parts['post_id'];

        // @todo build the query to make the list filter from
        // current day (i.e. thursday),
        // every day
        // every week (if not prayed for this week)
        // every month ( if not prayed for this month)
        // auto

        // max list 20

        $data = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id, p.post_title as name
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s AND pum.meta_key = %s
        ", $parts['post_id'], 'prayer_calendar' ), ARRAY_A );

        return $data;
    }

    public function endpoint_log( $parts, $post_id ) {

        $args = [
            'parent_id' => $parts['post_id'], // using parent_id to record the user_id. i.e. parent of the record is the user.
            'post_id' => $post_id,
            'post_type' => 'contacts',
            'type' => $parts['root'],
            'subtype' => $parts['type'],
            'payload' => null,
            'value' => 1,
            'time_end' => time(),
        ];

        // get geolocation of the contact, not the user
        $contact = DT_Posts::get_post('contacts', $post_id, false, false, true );
        if ( isset( $contact['location_grid_meta'] ) ) {
            $location = $contact['location_grid_meta'][0];
            $args['lng'] = $location['lng'];
            $args['lat'] = $location['lat'];
            $args['level'] = $location['level'];
            $args['label'] = $location['label'];
            $args['grid_id'] = $location['grid_id'];
        } else if ( isset( $contact['location_grid'] ) ) {
            $location = $contact['location_grid'][0];
            $grid_record = Disciple_Tools_Mapping_Queries::get_by_grid_id($location['grid_id']);
            $args['lng'] = $grid_record['lng'];
            $args['lat'] = $grid_record['lat'];
            $args['level'] = $grid_record['level'];
            $args['label'] = $location['label'];
            $args['grid_id'] = $location['grid_id'];
        }


        return Disciple_Tools_Reports::insert( $args );

    }
}

