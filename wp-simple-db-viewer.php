<?php
/**
 * Plugin Name: Simple Database Tables Viewer
 * Description: View your database tables
 * Author: florentroques
 */

if (!defined('ABSPATH')) exit;

define('WSDV_PLUGIN_SLUG', 'wp-simple-db-viewer');

add_action('admin_enqueue_scripts', 'wsdv_add_bootstrap_css');

function wsdv_add_bootstrap_css()
{
    global $pagenow;

    if (!(
        ($pagenow == 'admin.php') &&
        (isset($_GET['page']) && $_GET['page'] == WSDV_PLUGIN_SLUG)
    )) {
        return;
    }

    wp_enqueue_style('wsdv-bootstrap-styles', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
}

add_action('admin_menu', 'wsdv_setup_menu_plugin');

function wsdv_setup_menu_plugin()
{
    add_menu_page(
        'Database Viewer',
        'Database Viewer',
        'manage_options',
        WSDV_PLUGIN_SLUG,
        'wp_simple_db_viewer',
        '',
        3.1
    );
}

function wp_simple_db_viewer()
{
    global $wpdb;

    function is_table_empty($table_name)
    {
        global $wpdb;

        return '0' === $wpdb->get_var("
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
            echo $table_name;
            echo ' | ';
            echo '<a href="admin.php?page=' . WSDV_PLUGIN_SLUG . '&table_name=' . $table_name . '">Show data</a>';
            echo ' | ';
            echo '<a href="admin.php?page=' . WSDV_PLUGIN_SLUG . '&table_name=' . $table_name . '&structure">Show structure</a>';
            echo '<br>';
        }
    } elseif (in_array($_GET['table_name'], $tables_names)) {
        $table_name = $_GET['table_name'];

        if (isset($_GET['structure'])) {
            echo '<h1>Structure of ' . $table_name . '</h1>';
            echo '<a href="admin.php?page=' . WSDV_PLUGIN_SLUG . '"><- Back</a>';

            echo '<table class="table table-striped w-auto">';
            echo '<tr>';
            echo '<th>Column</th>';
            echo '<th>Type</th>';
            echo '</tr>';

            // An array of table field names
            $existing_columns = $wpdb->get_col("DESC $table_name");

            foreach ($existing_columns as $column) {
                $result = $wpdb->get_results("
                    SELECT COLUMN_TYPE, IS_NULLABLE, EXTRA
                    FROM information_schema.columns
                    WHERE table_name = '$table_name'
                    AND column_name = '$column'
                ");

                echo '<tr>';
                echo '<td>' . $column . '</td>';
                echo '<td>' . $result[0]->COLUMN_TYPE;

                if ($result[0]->IS_NULLABLE === 'YES') {
                    echo ' NULL';
                } else {
                    echo ' NOT NULL';
                }

                if ($result[0]->EXTRA === 'auto_increment') {
                    echo ' AUTO_INCREMENT';
                }

                echo '</td>';
                echo '</tr>';
            }

            echo '</table>';
            return;
        }

        if (is_table_empty($table_name)) {
            echo 'The table ' . $table_name . ' is empty';
            return;
        }

        $results = $wpdb->get_results("SELECT * from {$table_name}");

        echo '<h1>Content of ' . $table_name . '</h1>';
        echo '<a href="admin.php?page=' . WSDV_PLUGIN_SLUG . '"><- Back</a>';
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
