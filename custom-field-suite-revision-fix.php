<?php
/*
Plugin Name: Custom field suite revision fix
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.jp/
Description: Fix revision and preview issue for Custom Field Suite Plugin
Version: 0.1.0
Author URI: http://firegoby.jp/
*/


$cfs_fix = new Custom_Field_Suite_Fix();

class Custom_Field_Suite_Fix {

function __construct()
{
    add_action('plugins_loaded', array($this, 'plugins_loaded'));
}

public function plugins_loaded()
{
    add_action('cfs_init', array($this, 'cfs_init'), 0);
    add_action('wp_insert_post', array($this, 'wp_insert_post'));
    add_filter('get_post_metadata', array($this, 'get_post_metadata'), 10, 4 );

    add_shortcode('cfs_get', function($p){
        return self::get($p['key']);
    });
}

public function get($key, $id = false)
{
    global $cfs;
    if (intval($id)) {
        return $cfs->get($key, $id);
    } elseif ($id = $this->get_preview_id(get_the_ID())) {
        return $cfs->get($key, $id);
    } else {
        return $cfs->get($key, get_the_ID());
    }
}

public function cfs_init()
{
    if (isset($_POST['wp-preview']) && $_POST['wp-preview'] === 'dopreview') {
        global $cfs;
        remove_action('cfs_init', array($cfs->form, 'init'));
    }
}

public function get_preview_id( $post_id )
{
    global $post;
    $preview_id = 0;
    if ($post->ID == $post_id && is_preview()
            && $preview = wp_get_post_autosave($post->ID)) {
        $preview_id = $preview->ID;
    }
    return $preview_id;
}

public function get_post_metadata( $return, $post_id, $meta_key, $single ) {
    if ($preview_id = $this->get_preview_id($post_id)) {
        if ($post_id != $preview_id) {
            $return = get_post_meta($preview_id, $meta_key, $single);
        }
    }
    return $return;
}

public function wp_insert_post($post_ID)
{
    if (wp_is_post_revision($post_ID)) {
        global $wpdb;
        global $cfs;

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->postmeta WHERE post_id = %d",
            $post_ID
        ));

        $cfs->form->session = new cfs_session();
        $session = $cfs->form->session->get();

        $field_groups = array();
        if (isset($session['field_groups'])) {
            $field_groups = $session['field_groups'];
        }

        foreach ($field_groups as $key => $val) {
            $field_groups[$key] = (int) $val;
        }

        $options = array(
            'format' => 'input',
            'field_groups' => $field_groups
        );

        $cfs->save($_POST['cfs']['input'], array('ID' => $post_ID), $options);

        $post_metas = array('meta');
        foreach ( $post_metas as $post_meta ) {
            foreach ( $_POST[$post_meta] as $meta_id => $meta_arr ) {
                add_metadata('post', $post_ID, $meta_arr['key'], $meta_arr['value']);
            }
        }
    }
}

}

// EOF
