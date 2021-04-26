<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Prayer_Calendar_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        add_filter( "dt_custom_fields_settings", [ $this, "dt_custom_fields" ], 1, 2 );
        add_action( "dt_details_additional_section", [ $this, "dt_add_section" ], 100, 2 );
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );

        add_action( 'dt_post_list_filters_sidebar', [ $this, 'dt_list_exports_filters'], 10, 1 );
    }

    /**
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = "" ) {

        if ( $post_type === "contacts" ){
            $fields['prayer_calendar_app'] = [
                'name' => __( 'Prayer Calendar', 'disciple_tools' ),
                'type' => 'post_user_meta',
                "tile" => "status",
                'default' => [
                    'none'   => [
                        "label" => _x( 'Not on Calendar', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Not on prayer calendar", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'auto' => [
                        "label" => _x( 'Auto', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_month' => [
                        "label" => _x( 'Every Month', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_week' => [
                        "label" => _x( 'Every Week', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_day' => [
                        "label" => _x( 'Every Day', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_monday' => [
                        "label" => _x( 'Every Monday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Monday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_tuesday' => [
                        "label" => _x( 'Every Tuesday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Tuesday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_wednesday' => [
                        "label" => _x( 'Every Wednesday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Wednesday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_thursday' => [
                        "label" => _x( 'Every Thursday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Thursday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_friday' => [
                        "label" => _x( 'Every Friday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Friday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_saturday' => [
                        "label" => _x( 'Every Saturday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Saturday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_sunday' => [
                        "label" => _x( 'Every Sunday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Sunday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],

                ],
            ];
        }

        if ( $post_type === "groups" ){
            $fields['prayer_calendar_app'] = [
                'name' => __( 'Prayer Calendar', 'disciple_tools' ),
                'type' => 'post_user_meta',
                "tile" => "status",
                'default' => [
                    'none'   => [
                        "label" => _x( 'Not on Calendar', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Not on prayer calendar", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'auto' => [
                        "label" => _x( 'Auto', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_month' => [
                        "label" => _x( 'Every Month', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_week' => [
                        "label" => _x( 'Every Week', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_day' => [
                        "label" => _x( 'Every Day', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_monday' => [
                        "label" => _x( 'Every Monday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Monday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_tuesday' => [
                        "label" => _x( 'Every Tuesday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Tuesday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_wednesday' => [
                        "label" => _x( 'Every Wednesday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Wednesday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_thursday' => [
                        "label" => _x( 'Every Thursday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Thursday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_friday' => [
                        "label" => _x( 'Every Friday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Friday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_saturday' => [
                        "label" => _x( 'Every Saturday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Saturday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_sunday' => [
                        "label" => _x( 'Every Sunday', 'Prayer Calendar label', 'disciple_tools' ),
                        "description" => _x( "Pray every Sunday.", "Prayer Calendar field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],

                ],
            ];
        }

        return $fields;
    }

    public function dt_add_section( $section, $post_type ) {
        if ( $post_type === "contacts" && $section === "status" ){
            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );

            $state = false;
            if ( isset( $this_post['prayer_calendar_app'] ) && ! empty( $this_post['prayer_calendar_app'] ) ) {
                $state = $this_post['prayer_calendar_app'][0]['value'];

                if ( count( $this_post['prayer_calendar_app']) > 1 ){
                    $this->delete_extra_prayer_times( $this_post['prayer_calendar_app'][0]['id'] );
                }
            }
            ?>
            <div class="cell small-12 medium-4">
                <div class="section-subheader">
                    <img src="https://global.zume.community/wp-content/themes/disciple-tools-theme/dt-assets/images/calendar.svg">
                    <?php echo esc_html__( 'Prayer Calendar', 'disciple_tools' ) ?>
                </div>
                <select id="prayer-calendar">
                    <?php
                    foreach( $post_type_fields['prayer_calendar_app']['default'] as $key => $item ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ) ?>" <?php echo ( $state === $key ) ? 'selected' : '' ?>><?php echo esc_html( $item['label']) ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            <script>
                jQuery(document).ready(function($){
                    $('#prayer-calendar').on('change', function(){

                        let d = new Date()
                        d = d.toISOString().slice(0, 19).replace('T', ' ')

                        let data = {}
                        let v = $('#prayer-calendar').val()

                        if ( typeof post.prayer_calendar_app !== 'undefined' ) {
                            data = {
                                'prayer_calendar_app': {
                                    'values': [
                                        {
                                            id: post.prayer_calendar_app[0].id,
                                            value: v,
                                            category: v,
                                            date: d
                                        }
                                    ]
                                }
                            }
                        } else {
                            data = {
                                'prayer_calendar_app': {
                                    'values': [
                                        {
                                            value: v,
                                            category: v,
                                            date: d
                                        }
                                    ]
                                }
                            }
                        }

                        window.API.update_post(detailsSettings.post_type, detailsSettings.post_id, data )
                            .done(function(d){
                                console.log(d)
                                $('#add-to-prayer-calendar').removeClass('hollow').html('On Calendar')
                            })
                    })
                })

            </script>


        <?php }
    }

    public function delete_extra_prayer_times( $post_id_to_keep ){
        global $wpdb;
        $wpdb->query($wpdb->prepare( "DELETE FROM $wpdb->dt_post_user_meta WHERE meta_key = 'prayer_calendar_app' AND user_id = %s AND post_id = %s AND id != %s", get_current_user_id(), get_the_ID(), $post_id_to_keep) );
    }

    public function dt_user_list_filters( $filters, $post_type ){

        if ( $post_type === 'contacts' ) {
            $key = get_user_option( 'prayer_calendar_app_daily' );
            if ( ! empty( $key ) ) {
                $counts = $this->get_my_prayer_counts( $post_type );
                $category_counts = [];
                $total_prayer_items = 0;
                foreach ($counts as $count) {
                    $total_prayer_items += $count["count"];
                    dt_increment($category_counts[$count["category"]], $count["count"]);
                }

                $filters["tabs"][] = [
                    "key" => "prayer_calendar_app",
                    "label" => _x("Prayer Calendar", 'List Filters', 'disciple_tools'),
                    "count" => $total_prayer_items,
                    "order" => 20
                ];

                $post_type_fields = DT_Posts::get_post_field_settings( 'contacts' );
                foreach( $post_type_fields['prayer_calendar_app']['default'] as $key => $item ){
                    if ( 'none' === $key ){
                        continue;
                    }

                    $filters["filters"][] = [
                        'ID' => $key,
                        'tab' => 'prayer_calendar_app',
                        'name' => $item['label'],
                        'query' => [
                            'prayer_calendar_app' => [$key],
                            'sort' => 'name'
                        ],
                        "count" => $category_counts[$key] ?? 0,
                    ];
                }
            }
        }
        return $filters;
    }

    public function get_my_prayer_counts( $post_type ){

        global $wpdb;
        $current_user = get_current_user_id();

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT pum.category, count(pum.id) as count
                FROM $wpdb->dt_post_user_meta pum
                JOIN $wpdb->posts p ON p.ID=pum.post_id
                WHERE
                pum.user_id = %d
                AND pum.meta_key = 'prayer_calendar_app'
                AND p.post_type = %s
                GROUP BY pum.category
        ", $current_user, $post_type ), ARRAY_A);

        return $results;
    }

    public function dt_list_exports_filters( $post_type ){
        if ( 'contacts' === $post_type ){
            $key = get_user_option( 'prayer_calendar_app_daily' );
            if ( ! empty( $key ) ) {
                $app_link = trailingslashit( trailingslashit( site_url() ) . 'prayer_calendar_app/daily/' . esc_attr( $key ) );
                ?>
                <div class="grid-x" style="margin-top:1rem;">
                    <div class="cell center">
                        <a href="<?php echo esc_url( $app_link ) ?>">Prayer Calendar App</a>
                    </div>
                </div>
                <?php
            }
        }
    }


}
DT_Prayer_Calendar_Tile::instance();
