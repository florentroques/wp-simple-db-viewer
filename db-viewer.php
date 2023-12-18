<?php

/**
 * Plugin Name: Database tables viewer
 * Description: View your database tables
 * Author: florentroques
 */

if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', 'add_bootstrap_css_db_viewer');

function add_bootstrap_css_db_viewer()
{
    global $pagenow;

    if (!(
        ($pagenow == 'admin.php') &&
        ($_GET['page'] == 'db-viewer')
    )) {
        return;
    }

    wp_enqueue_style('bootstrap-styles', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
}


add_action('admin_menu', 'setup_menu_plugin_db_viewer');

function setup_menu_plugin_db_viewer()
{
    add_menu_page(
        'Database Viewer',
        'Database Viewer',
        'manage_options',
        'db-viewer',
        'init',
        '',
        3
    );
}

function init()
{
    global $wpdb;

    function is_table_empty($table_name)
    {
        global $wpdb;

        return '0' == $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$table_name}
            LIMIT 1
        ");
    }

    $tables_names = [];
    $array_of_objects_containing_table_name = $wpdb->get_results("SHOW TABLES");

    foreach ($array_of_objects_containing_table_name as $object) {
        foreach ($object as $table_name) {
            $tables_names[] = $table_name;
        }
    }

    if (!isset($_GET['table_name'])) {
        echo '<h1>Tables</h1>';

        foreach ($tables_names as $table_name) {
            echo '<a href="admin.php?page=db-viewer&table_name=' . $table_name . '">' . $table_name . '</a><br>';
        }
    } elseif (in_array($_GET['table_name'], $tables_names)) {
        $table_name = $_GET['table_name'];

        if (is_table_empty($table_name)) {
            echo 'The table ' . $table_name . ' is empty';
            return;
        }

        $results = $wpdb->get_results("SELECT * from {$table_name}");

        echo '<h1>' . $table_name . '</h1>';
        echo '<table class="table table-striped w-auto">';

        $keys = array_keys((array) $results[0]);

        echo '<tr>';
        foreach ($keys as $key) {
            echo '<th>' . $key . '</th>';
        }
        echo '</tr>';

        foreach ($results as $result) {
            echo '<tr>';
            foreach ($result as $key => $value) {
                echo '<td>' . $value . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
}
