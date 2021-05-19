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
    public $key = 'prayer_calendar_app_daily';
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
        add_action( 'wp_enqueue_scripts', [ $this, '_wp_enqueue_scripts' ], 100 );

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

        if ( $this->magic->is_valid_key_url( $this->type ) && 'manifest' === $this->parts['action'] ) {
            add_filter( 'dt_json_access', [ $this, '_has_access' ] );
            add_action( 'dt_json_content', [ $this, 'manifest_json' ] );
        }
        else {
            add_action( 'dt_blank_body', [ $this, 'body' ] );
        }


        // load if valid url
        add_filter( "dt_blank_title", [ $this, "_browser_tab_title" ] );
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );

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
                'manifest' => 'Manifest',
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
        if ( isset( $actions[ $parts['action'] ] ) && 'manifest' === $parts['action'] ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank-json.php';
        }
        else if ( isset( $actions[ $parts['action'] ] ) ){
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
    public function _wp_enqueue_scripts(){
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) !== false ) {
            wp_enqueue_script( 'lodash' );
            wp_enqueue_script( 'jquery-ui' );
            wp_enqueue_script( 'jquery-touch-punch' );

            wp_enqueue_script( $this->key, trailingslashit( plugin_dir_url( __FILE__ ) ) . 'prayer-calendar-daily.js', [
                'jquery',
                'jquery-touch-punch'
            ], filemtime( plugin_dir_path( __FILE__ ) .'prayer-calendar-daily.js' ), true );
            wp_enqueue_script( 'p2r', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'jquery.p2r.min.js', [
                'jquery',
                'jquery-touch-punch'
            ], filemtime( plugin_dir_path( __FILE__ ) .'jquery.p2r.min.js' ), true );
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
            'site-js',
            'shared-functions',
            'mapbox-gl',
            'mapbox-cookie',
            'mapbox-search-widget',
            'google-search-widget',
//            'jquery-cookie',
//            'serviceWorker',
//            'moment',
//            'datepicker',
            'jquery-touch-punch',
            'prayer_calendar_app_daily',
            'p2r'
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
            'mapbox-gl-css',
//            'datepicker-css',
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
        return __( "Prayer Calendar", 'disciple_tools' );
    }
    public function header_style(){
        ?>
        <style>
            body {
                background-color: white;
            }
            #wrapper {
                max-width: 800px;
                margin: 0 auto;
            }
            #content {
                overflow-x: hidden;
            }
            .prayer-list-wrapper {
                background-color: #8BC34A;
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
            #title_link {
                color: white;
            }
            .item-name {
                padding-left:1em;
            }
            .basic_lists {
                cursor: pointer;
                color: #3f729b;
            }
            #offCanvasLeft ul {
                list-style-type: none;
            }

            #spinner-background {
                position:absolute;
                left: 49%;
                margin: 5px auto;
            }
            .link {
                cursor: pointer;
                color: #3f729b;
            }
            .list-item {
                cursor: pointer;
                color: #3f729b;
            }
        </style>
        <?php
    }
    public function header_javascript(){

        // add manifest for pwa
        ?>
<!--        <link rel="manifest" href="--><?php //echo trailingslashit( site_url() ) . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'] . '/manifest' ?><!--">-->
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


            //if ("serviceWorker" in navigator) {
            //    window.addEventListener("load", function() {
            //        navigator.serviceWorker
            //            .register("<?php //echo trailingslashit( plugin_dir_url( __FILE__ ) ) . 'prayer-calendar-daily.js' ?>//")
            //            .then(res => console.log("service worker registered"))
            //            .catch(err => console.log("service worker not registered", err))
            //    })
            //}

        </script>
        <?php
        return true;
    }

    public function body(){
        include('prayer-calendar-daily.html');
    }

    public function manifest_json() {

        return [
            "name" => "Prayer Calendar",
            "short_name" => "PrayerCalendar",
            "start_url" => trailingslashit( site_url() ) . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'],
            "scope" => trailingslashit( site_url() ) . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'],
            "display" => 'standalone',
            "background_color" => "#fdfdfd",
            "theme_color" => "#db4938",
            "orientation" => "portrait-primary",
            "icons" => [
                [
                    "src" => get_stylesheet_directory_uri() . "/dt-assets/favicons/mstile-70x70.png",
                    "type" => "image/png",
                    "sizes" => "128x128"
                ],
                [
                    "src"=> get_stylesheet_directory_uri() . "/dt-assets/favicons/mstile-144x144.png",
                    "type"=> "image/png",
                    "sizes"=> "144x144"
                ],
                [
                    "src"=> get_stylesheet_directory_uri() . "/dt-assets/favicons/mstile-150x150.png",
                  "type"=> "image/png",
                    "sizes"=> "270x270"
                ],
                [
                    "src"=> get_stylesheet_directory_uri() . "/dt-assets/favicons/mstile-310x310.png",
                    "type"=> "image/png",
                    "sizes"=> "558x558"
                ]
            ]
        ];
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
            case 'get_all':
                return $this->endpoint_get_all( $params['parts'] );
            case 'get':
                return $this->endpoint_get( $params['parts'] );
            case 'filter_list':
                return $this->endpoint_filter_lists( $params['parts'] );
            case 'filter':
                return $this->endpoint_filter( $params['parts'], $params['post_type'], $params['meta_value'] );
            case 'log':
                if ( ! isset( $params['post_id'] ) ){
                    return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
                }
                return $this->endpoint_log( $params['parts'], $params['post_id'] );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function endpoint_get_all( $parts ) {
        global $wpdb;
        $data = [
            'list' => [],
            'totals' => [],
        ];

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id,
                   p.post_title as name,
                   p.post_type,
                   pum.id,
                   pum.user_id,
                   pum.meta_value as type,
                   (
                    SELECT r.timestamp
                    FROM $wpdb->dt_reports r
                    WHERE r.parent_id = 2 AND r.post_id = p.ID
                    ORDER BY timestamp LIMIT 1
                    ) as last_report
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
              AND pum.meta_key = %s
        ", $parts['post_id'], $this->key ), ARRAY_A );

        foreach( $results as $item ) {

            $item['last_report'] = (int) $item['last_report'];
            $item['id'] = (int) $item['id'];
            $item['post_id'] = (int) $item['post_id'];
            $item['user_id'] = (int) $item['user_id'];

            $data['list'][$item['post_id']] = $item;

            if ( ! isset($item['post_type'] ) ) {
                $data['totals'][$item['post_type']] = 0;
            }
            $data['totals'][$item['post_type']]++;
        }

        return $data;
    }

    public function endpoint_get( $parts ) {
        global $wpdb;

        $data = [
            'lists' => [
                'today' => [],
                'weekly' => [],
                'monthly' => [],
            ],
            'counts' => [
                'today' => 0,
                'weekly' => 0,
                'monthly' => 0,
            ],
        ];

        $day_number = gmdate( 'N', time() );
        $day_key = 'every_' .  $day_number;

        $time_at_start_of_day = strtotime( 'today' );

        $data['lists']['today'] = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id, p.post_title as name, p.post_type
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
                AND pum.meta_key = %s
                AND ( pum.meta_value = 'every_day' OR pum.meta_value = %s )
                AND pum.post_id NOT IN (
                    SELECT DISTINCT r.post_id
                    FROM $wpdb->dt_reports r
                    WHERE r.timestamp > %d
                      AND r.parent_id = %d
                    AND r.type = 'prayer_calendar_app'
                    AND r.subtype = 'daily'
                )
        ", $parts['post_id'], $this->key, $day_key, $time_at_start_of_day, $parts['post_id'] ), ARRAY_A );
        if ( ! empty( $data['lists']['today'] ) ){
            $data['counts']['today'] = count( $data['lists']['today'] );
        }

        $time_a_week_ago = strtotime( '1 week ago' );
        $data['lists']['weekly'] = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id, p.post_title as name, p.post_type
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
                AND pum.meta_key = %s
                AND pum.meta_value = 'every_week'
                AND pum.post_id NOT IN (
                    SELECT DISTINCT r.post_id
                    FROM $wpdb->dt_reports r
                    WHERE r.timestamp > %d
                      AND r.parent_id = %d
                    AND r.type = 'prayer_calendar_app'
                    AND r.subtype = 'daily'
                )
        ", $parts['post_id'], $this->key, $time_a_week_ago, $parts['post_id'] ), ARRAY_A );
        if ( ! empty( $data['lists']['weekly'] ) ){
            $data['counts']['weekly'] = count( $data['lists']['weekly'] );
        }

        $time_a_month_ago = strtotime( '1 month ago' );
        $data['lists']['monthly'] = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id, p.post_title as name, p.post_type
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
                AND pum.meta_key = %s
                AND pum.meta_value = 'every_month'
                AND pum.post_id NOT IN (
                    SELECT DISTINCT r.post_id
                    FROM $wpdb->dt_reports r
                    WHERE r.timestamp > %d
                    AND r.parent_id = %d
                    AND r.type = 'prayer_calendar_app'
                    AND r.subtype = 'daily'
                )
        ", $parts['post_id'], $this->key, $time_a_month_ago, $parts['post_id'] ), ARRAY_A );
        if ( ! empty( $data['lists']['monthly'] ) ){
            $data['counts']['monthly'] = count( $data['lists']['monthly'] );
        }

        return $data;
    }

    public function endpoint_filter( $parts, $post_type, $meta_value ) {
        global $wpdb;

        $data = [
            'lists' => [],
            'counts' => [],
        ];

        if ( ! isset( $data['lists'][$meta_value] ) ) {
            $data['lists'][$meta_value] = [];
        }
        if ( ! isset( $data['counts'][$meta_value] ) ) {
            $data['counts'][$meta_value] = 0;
        }

        $data['lists'][$meta_value] = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID as post_id, p.post_title as name, p.post_type
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
              AND pum.meta_key = %s
              AND p.post_type = %s
              AND pum.meta_value = %s
        ", $parts['post_id'], $this->key, $post_type, $meta_value ), ARRAY_A );
        if ( ! empty( $data['lists'][$meta_value] ) ){
            $data['counts'][$meta_value] = count( $data['lists'][$meta_value] );
        }

        return $data;
    }

    public function endpoint_filter_lists( $parts ) {
        global $wpdb;

        $data = [
            'lists' => [],
            'counts' => [],
        ];

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.post_type, pum.meta_value, count(p.ID) as count
            FROM $wpdb->dt_post_user_meta pum
            JOIN $wpdb->posts p ON p.ID=pum.post_id
            WHERE pum.user_id = %s
              AND pum.meta_key = %s
            GROUP BY p.post_type, pum.meta_value
        ", $parts['post_id'], $this->key ), ARRAY_A );

        foreach ( $results as $result ) {
            if ( ! isset( $data['lists'][$result['post_type']] ) ) {
                $data['lists'][$result['post_type']] = [];
            }
            $data['lists'][$result['post_type']][$result['meta_value']] = $result;
        }

        return $data;
    }

    public function endpoint_log( $parts, $post_id ) {
        $post_type = get_post_type( $post_id );

        $args = [
            'parent_id' => $parts['post_id'], // using parent_id to record the user_id. i.e. parent of the record is the user.
            'post_id' => $post_id,
            'post_type' => $post_type,
            'type' => $parts['root'],
            'subtype' => $parts['type'],
            'payload' => null,
            'value' => 1,
            'time_end' => time(),
        ];

        // get geolocation of the contact, not the user
        $post_object = DT_Posts::get_post( $post_type, $post_id, false, false, true );
        if ( isset( $post_object['location_grid_meta'] ) ) {
            $location = $post_object['location_grid_meta'][0];
            if ( isset( $location['lng'] ) ) {
                $args['lng'] = $location['lng'];
                $args['lat'] = $location['lat'];
                $args['level'] = $location['level'];
                $args['label'] = $location['label'];
                $args['grid_id'] = $location['grid_id'];
            }
        } else if ( isset( $post_object['location_grid'][0] ) ) {
            $location = $post_object['location_grid'][0];
            $grid_record = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location['id'] );
            if ( isset( $grid_record['lng'] ) ) {
                $args['lng'] = $grid_record['lng'];
                $args['lat'] = $grid_record['lat'];
                $args['level'] = $grid_record['level'];
                $args['label'] = $location['label'];
                $args['grid_id'] = $location['grid_id'];
            }
        }

        return Disciple_Tools_Reports::insert( $args );
    }

    public function get_filter_counts() {
        global $wpdb;
        $current_user = get_current_user_id(); // @todo can't use this in magic because of non-logged in

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.post_type, pum.meta_value, count(pum.id) as count
                FROM $wpdb->dt_post_user_meta pum
                JOIN $wpdb->posts p ON p.ID=pum.post_id
                WHERE
                pum.user_id = %d
                AND pum.meta_key = %s
                GROUP BY p.post_type, pum.meta_value
        ", $current_user, $this->key ), ARRAY_A);

        $filters = [];
        if ( ! empty( $results ) ) {
            foreach ( $results as $result ){
                if ( ! isset( $filters[$result['post_type']][$result['meta_value']] ) ){
                    $filters[$result['post_type']][$result['meta_value']] = 0;
                }
                $filters[$result['post_type']][$result['meta_value']] = $filters[$result['post_type']][$result['meta_value']] + $result['count'];
            }
        }

        return $filters;
    }
}

