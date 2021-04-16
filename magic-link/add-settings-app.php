<?php

/**
 * Add the app to the settings page in the Apps section
 */
add_filter( 'dt_settings_apps_list', function( $apps_list ){
    $root = 'prayer_calendar_app';
    $type = 'daily';
    $apps_list[$root.'_'.$type] = [
        'key' => $root.'_'.$type,
        'url_base' => $root. '/'. $type,
        'label' => __('Prayer Calendar App', 'disciple_tools'),
        'description' => __('A micro app page that creates a daily prayer list.', 'disciple_tools'),
    ];
    return $apps_list;
}, 10, 1 );

