<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FL_Admin {

    private $page_slug = 'friendslink';

    public function __construct() {
        add_action( 'admin_menu',                  [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts',        [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_fl_save_link',      [ $this, 'handle_save' ] );
        add_action( 'admin_post_fl_delete_link',    [ $this, 'handle_delete' ] );
    }

    public function register_menu() {
        add_menu_page(
            '友链管理',
            '友链管理',
            'manage_options',
            $this->page_slug,
            [ $this, 'render_list_page' ],
            'dashicons-heart',
            30
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, $this->page_slug ) === false ) return;

        wp_enqueue_style(
            'fl-admin',
            FRIENDSLINK_PLUGIN_URL . 'admin/admin.css',
            [],
            FRIENDSLINK_VERSION
        );
        wp_enqueue_script(
            'fl-admin',
            FRIENDSLINK_PLUGIN_URL . 'admin/admin.js',
            [],
            FRIENDSLINK_VERSION,
            true
        );
    }

    public function render_list_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $notice = get_transient( 'fl_admin_notice_' . get_current_user_id() );
        if ( $notice ) delete_transient( 'fl_admin_notice_' . get_current_user_id() );

        // 判断是否在表单页
        $action = isset( $_GET['fl_action'] ) ? sanitize_key( $_GET['fl_action'] ) : '';
        if ( $action === 'edit' || $action === 'new' ) {
            $this->render_form_page( $action );
            return;
        }

        $links = FL_DB::get_all();
        $add_url = add_query_arg( [ 'page' => $this->page_slug, 'fl_action' => 'new' ], admin_url( 'admin.php' ) );
        ?>
        <div class="wrap fl-wrap">
            <h1 class="wp-heading-inline">友链管理</h1>
            <a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">添加新友链</a>
            <hr class="wp-header-end">

            <?php if ( $notice ) : ?>
            <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                <p><?php echo esc_html( $notice['message'] ); ?></p>
            </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped fl-table">
                <thead>
                    <tr>
                        <th style="width:5%">ID</th>
                        <th style="width:18%">名称</th>
                        <th style="width:25%">网址</th>
                        <th style="width:12%">分类</th>
                        <th style="width:15%">标签</th>
                        <th style="width:8%">排序</th>
                        <th style="width:17%">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $links ) ) : ?>
                    <tr><td colspan="6">暂无友链，<a href="<?php echo esc_url( $add_url ); ?>">立即添加</a>。</td></tr>
                <?php else : ?>
                    <?php foreach ( $links as $link ) :
                        $edit_url   = add_query_arg( [ 'page' => $this->page_slug, 'fl_action' => 'edit', 'id' => $link->id ], admin_url( 'admin.php' ) );
                        $delete_url = wp_nonce_url(
                            add_query_arg( [ 'action' => 'fl_delete_link', 'id' => $link->id ], admin_url( 'admin-post.php' ) ),
                            'fl_delete_' . $link->id
                        );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $link->id ); ?></td>
                        <td>
                            <?php if ( $link->logo_url ) : ?>
                            <img src="<?php echo esc_url( $link->logo_url ); ?>" class="fl-logo-thumb" alt="">
                            <?php endif; ?>
                            <?php echo esc_html( $link->name ); ?>
                        </td>
                        <td><a href="<?php echo esc_url( $link->url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $link->url ); ?></a></td>
                        <td><?php echo esc_html( $link->category ); ?></td>
                        <td><?php echo esc_html( $link->tags ); ?></td>
                        <td><?php echo esc_html( $link->sort_order ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $edit_url ); ?>">编辑</a>
                            &nbsp;|&nbsp;
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="fl-delete-link" style="color:#b32d2e;">删除</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_form_page( $action ) {
        $link = null;
        $id   = 0;

        if ( $action === 'edit' ) {
            $id   = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
            $link = $id ? FL_DB::get_one( $id ) : null;
            if ( ! $link ) {
                wp_die( '未找到该友链。' );
            }
        }

        $categories = FL_DB::get_categories();
        $list_url   = add_query_arg( 'page', $this->page_slug, admin_url( 'admin.php' ) );
        $title      = $action === 'edit' ? '编辑友链' : '添加新友链';

        $val = function( $field, $default = '' ) use ( $link ) {
            return $link ? esc_attr( $link->$field ) : esc_attr( $default );
        };
        ?>
        <div class="wrap fl-wrap">
            <h1><?php echo esc_html( $title ); ?></h1>
            <a href="<?php echo esc_url( $list_url ); ?>">&larr; 返回列表</a>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fl-form">
                <?php wp_nonce_field( 'fl_save_link', 'fl_nonce' ); ?>
                <input type="hidden" name="action"  value="fl_save_link">
                <input type="hidden" name="link_id" value="<?php echo esc_attr( $id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="fl_name">网站名称 <span class="required">*</span></label></th>
                        <td><input type="text" id="fl_name" name="fl_name" value="<?php echo $val('name'); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="fl_url">网站地址 <span class="required">*</span></label></th>
                        <td><input type="url" id="fl_url" name="fl_url" value="<?php echo $val('url'); ?>" class="regular-text" required placeholder="https://"></td>
                    </tr>
                    <tr>
                        <th><label for="fl_logo_url">图标地址</label></th>
                        <td>
                            <input type="url" id="fl_logo_url" name="fl_logo_url" value="<?php echo $val('logo_url'); ?>" class="regular-text" placeholder="https://">
                            <p class="description">留空时前台将显示网站名称首字作为头像。</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fl_description">描述</label></th>
                        <td><textarea id="fl_description" name="fl_description" rows="3" class="regular-text"><?php echo $link ? esc_textarea( $link->description ) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="fl_category">分类</label></th>
                        <td>
                            <input type="text" id="fl_category" name="fl_category" value="<?php echo $val('category'); ?>" class="regular-text" list="fl-categories-list">
                            <datalist id="fl-categories-list">
                                <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat ); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <p class="description">自由输入，已有分类会自动提示。</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fl_tags">标签</label></th>
                        <td>
                            <input type="text" id="fl_tags" name="fl_tags" value="<?php echo $val('tags'); ?>" class="regular-text" placeholder="技术, 设计, 工具">
                            <p class="description">多个标签用英文逗号分隔。</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fl_sort_order">排序</label></th>
                        <td><input type="number" id="fl_sort_order" name="fl_sort_order" value="<?php echo $val('sort_order', '0'); ?>" class="small-text"><p class="description">数值越小越靠前。</p></td>
                    </tr>
                </table>

                <?php submit_button( $action === 'edit' ? '保存修改' : '添加友链' ); ?>
            </form>
        </div>
        <?php
    }

    public function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足。' );
        if ( ! isset( $_POST['fl_nonce'] ) || ! wp_verify_nonce( $_POST['fl_nonce'], 'fl_save_link' ) ) wp_die( 'Nonce 验证失败。' );

        $name     = sanitize_text_field( $_POST['fl_name'] ?? '' );
        $url      = esc_url_raw( $_POST['fl_url'] ?? '' );
        $logo_url = esc_url_raw( $_POST['fl_logo_url'] ?? '' );
        $desc     = sanitize_textarea_field( $_POST['fl_description'] ?? '' );
        $category = sanitize_text_field( $_POST['fl_category'] ?? '' );
        $tags     = sanitize_text_field( $_POST['fl_tags'] ?? '' );
        $order    = absint( $_POST['fl_sort_order'] ?? 0 );
        $link_id  = (int) ( $_POST['link_id'] ?? 0 );

        if ( empty( $name ) || empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            $this->set_notice( 'error', '名称和合法的网站地址为必填项。' );
            wp_safe_redirect( $this->redirect_url( $link_id ) );
            exit;
        }

        $data = [
            'name'        => $name,
            'url'         => $url,
            'logo_url'    => $logo_url,
            'description' => $desc,
            'category'    => $category,
            'tags'        => $tags,
            'sort_order'  => $order,
        ];

        if ( $link_id > 0 ) {
            FL_DB::update( $link_id, $data );
            $this->set_notice( 'success', '友链已更新。' );
        } else {
            FL_DB::insert( $data );
            $this->set_notice( 'success', '友链已添加。' );
        }

        wp_safe_redirect( add_query_arg( 'page', $this->page_slug, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足。' );

        $id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
        if ( ! $id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'fl_delete_' . $id ) ) {
            wp_die( 'Nonce 验证失败。' );
        }

        FL_DB::delete( $id );
        $this->set_notice( 'success', '友链已删除。' );
        wp_safe_redirect( add_query_arg( 'page', $this->page_slug, admin_url( 'admin.php' ) ) );
        exit;
    }

    private function set_notice( $type, $message ) {
        set_transient( 'fl_admin_notice_' . get_current_user_id(), compact( 'type', 'message' ), 30 );
    }

    private function redirect_url( $link_id ) {
        $args = [ 'page' => $this->page_slug ];
        if ( $link_id > 0 ) {
            $args['fl_action'] = 'edit';
            $args['id']        = $link_id;
        } else {
            $args['fl_action'] = 'new';
        }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }
}
