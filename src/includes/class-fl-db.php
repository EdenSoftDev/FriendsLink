<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FL_DB {

    public static function create_table() {
        global $wpdb;
        $table      = $wpdb->prefix . 'friendslink';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            name        VARCHAR(100)    NOT NULL DEFAULT '',
            url         VARCHAR(500)    NOT NULL DEFAULT '',
            logo_url    VARCHAR(500)    NOT NULL DEFAULT '',
            description TEXT            NOT NULL,
            category    VARCHAR(100)    NOT NULL DEFAULT '',
            tags        VARCHAR(500)    NOT NULL DEFAULT '',
            sort_order  INT             NOT NULL DEFAULT 0,
            created_at  DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY category (category),
            KEY sort_order (sort_order)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * @param array $args {
     *   string $category  Filter by exact category. Empty = all.
     *   string $orderby   Column name. Default 'sort_order'.
     *   string $order     'ASC' or 'DESC'. Default 'ASC'.
     * }
     * @return array
     */
    public static function get_all( $args = [] ) {
        global $wpdb;
        $table = $wpdb->prefix . 'friendslink';

        $defaults = [
            'category' => '',
            'orderby'  => 'sort_order',
            'order'    => 'ASC',
        ];
        $args = wp_parse_args( $args, $defaults );

        $allowed_orderby = [ 'sort_order', 'name', 'created_at', 'id' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'sort_order';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        if ( $args['category'] !== '' ) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE category = %s ORDER BY {$orderby} {$order}",
                $args['category']
            );
        } else {
            $sql = "SELECT * FROM {$table} ORDER BY {$orderby} {$order}";
        }

        return $wpdb->get_results( $sql );
    }

    public static function get_one( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'friendslink';
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ) );
    }

    public static function insert( $data ) {
        global $wpdb;
        $data['created_at'] = current_time( 'mysql' );
        $result = $wpdb->insert( $wpdb->prefix . 'friendslink', $data );
        return $result !== false ? $wpdb->insert_id : false;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'friendslink', $data, [ 'id' => (int) $id ] );
    }

    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'friendslink', [ 'id' => (int) $id ] );
    }

    public static function get_categories() {
        global $wpdb;
        $table = $wpdb->prefix . 'friendslink';
        return $wpdb->get_col(
            "SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category ASC"
        );
    }
}
