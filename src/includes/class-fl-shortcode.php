<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FL_Shortcode {

    public function __construct() {
        add_shortcode( 'friendslink', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'friendslink',
            FRIENDSLINK_PLUGIN_URL . 'public/friendslink.css',
            [],
            FRIENDSLINK_VERSION
        );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( [
            'category'  => '',
            'cols'      => '2',
            'show_desc' => 'true',
        ], $atts, 'friendslink' );

        $category  = sanitize_text_field( $atts['category'] );
        $cols      = max( 1, min( 12, (int) $atts['cols'] ) );
        $show_desc = $atts['show_desc'] !== 'false';

        $links = FL_DB::get_all( [ 'category' => $category ] );

        if ( empty( $links ) ) {
            return '<p class="fl-empty">暂无友链。</p>';
        }

        ob_start();
        echo '<div class="fl-links-wrap" style="--fl-cols:' . esc_attr( $cols ) . '">';

        if ( $category !== '' ) {
            // 单分类：直接输出扁平网格
            echo '<div class="fl-links-grid">';
            foreach ( $links as $link ) {
                $this->render_card( $link, $show_desc );
            }
            echo '</div>';
        } else {
            // 多分类时按分类分组，无分类的放最后
            $grouped    = [];
            $no_cat     = [];
            foreach ( $links as $link ) {
                if ( $link->category !== '' ) {
                    $grouped[ $link->category ][] = $link;
                } else {
                    $no_cat[] = $link;
                }
            }

            $has_groups = count( $grouped ) > 0 || ( count( $grouped ) === 0 && count( $no_cat ) > 0 );

            if ( count( $grouped ) > 0 ) {
                foreach ( $grouped as $cat_name => $cat_links ) {
                    echo '<div class="fl-category-group">';
                    echo '<h3 class="fl-category-title">' . esc_html( $cat_name ) . '</h3>';
                    echo '<div class="fl-links-grid">';
                    foreach ( $cat_links as $link ) {
                        $this->render_card( $link, $show_desc );
                    }
                    echo '</div></div>';
                }
            }

            if ( ! empty( $no_cat ) ) {
                $wrap_class = count( $grouped ) > 0 ? ' fl-category-group' : '';
                echo '<div class="' . esc_attr( trim( 'fl-category-group' . $wrap_class ) ) . '">';
                if ( count( $grouped ) > 0 ) {
                    echo '<h3 class="fl-category-title">其他</h3>';
                }
                echo '<div class="fl-links-grid">';
                foreach ( $no_cat as $link ) {
                    $this->render_card( $link, $show_desc );
                }
                echo '</div></div>';
            }
        }

        echo '</div>';
        return ob_get_clean();
    }

    private function render_card( $link, $show_desc ) {
        $name     = esc_html( $link->name );
        $url      = esc_url( $link->url );
        $logo     = esc_url( $link->logo_url );
        $desc     = esc_html( $link->description );
        $category = esc_html( $link->category );
        $initial  = mb_substr( $link->name, 0, 1, 'UTF-8' );
        $tags     = array_filter( array_map( 'trim', explode( ',', $link->tags ) ) );

        echo '<a class="fl-link-card" href="' . $url . '" target="_blank" rel="noopener noreferrer">';

        if ( $logo ) {
            echo '<img class="fl-link-logo" src="' . $logo . '" alt="' . $name . '" loading="lazy">';
        } else {
            echo '<span class="fl-link-logo fl-link-initial" aria-hidden="true">' . esc_html( $initial ) . '</span>';
        }

        echo '<span class="fl-link-body">';
        echo '<span class="fl-link-name">' . $name . '</span>';

        if ( $show_desc && $desc !== '' ) {
            echo '<span class="fl-link-desc">' . $desc . '</span>';
        }

        if ( ! empty( $tags ) ) {
            echo '<span class="fl-link-tags">';
            foreach ( $tags as $tag ) {
                echo '<span class="fl-link-tag">' . esc_html( $tag ) . '</span>';
            }
            echo '</span>';
        }

        echo '</span>';

        if ( $category !== '' ) {
            echo '<span class="fl-link-category">' . $category . '</span>';
        }

        echo '</a>';
    }
}
