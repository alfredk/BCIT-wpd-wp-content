<?php

function fue_add_custom_fields( $matches ) {
    if ( empty($matches) ) return '';

    $id = $matches[1];
    $cf = $matches[2];

    $meta = get_post_meta( $id, $cf, true );

    if ($meta) {

        if ( $cf == '_downloadable_files' ) {
            if ( count($meta) == 1 ) {
                $file = array_pop($meta);
                $meta = '<a href="'. $file['file'] .'">'. $file['name'] .'</a>';
            } else {

                $list = '<ul>';
                foreach ( $meta as $file ) {
                    $list .= '<li><a href="'. $file['file'] .'">'. $file['name'] .'</a></li>';
                }
                $list .= '</ul>';

                $meta = $list;
            }
        }

        return $meta;
    }
    return '';
}

function fue_add_post( $matches ) {
    if ( empty($matches) ) return '';
    if (! isset($matches[1]) || empty($matches[1]) ) return '';

    $id = $matches[1];

    $post = get_post( $id );

    if ( isset($post->post_excerpt) )
        return $post->post_excerpt;
    else
        return '';
}

function fue_get_page_id( $page ) {
    $page = get_option('fue_' . $page . '_page_id');
    return ( $page ) ? $page : -1;
}

if (! function_exists('sfn_get_product') ) {
    function sfn_get_product( $product_id ) {
        if ( function_exists('get_product') ) {
            return get_product( $product_id );
        } else {
            return new WC_Product( $product_id );
        }
    }
}
