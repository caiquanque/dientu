<?php
/**
 * Plugin Name: Get Content From Url
 * Description: Get Content From Url is aplugin
 * Version: 1.0
 */
function do_stuff ($post_id){
    $url = $_POST['post_title'];

    if($url !== null && filter_var($url, FILTER_VALIDATE_URL) == true){
        remove_action( 'save_post', 'do_stuff' );
        $response = wp_remote_get($url);
        $body = $response['body'];
        $dom  = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$body);

        $xpath = new DOMXPath($dom);

        if (strpos($url, 'vnexpress') !== false) {
            $title_tags = $xpath->query('//h1');
            $content_tags = $xpath->query(
                '//article[@class="content_detail fck_detail width_common block_ads_connect"]'
            );
        }
        if (strpos($url, 'vietnamplus') !== false) {
            $title_tags = $xpath->query('//h1');
            $content_tags = $xpath->query(
                '//div[@class="articleContents"]'
            );
        }
        if (strpos($url, 'thegioitiepthi') !== false) {
            $title_tags = $xpath->query('//h1');
            $content_tags = $xpath->query(
                '//div[@class="content-news"]'
            );
        }

        $title = '';
        foreach ($title_tags as $title_tag) {
            $title = $title_tag->nodeValue;
        }


        $post_name = str_replace(' ','_',$title);

        $content = '';
        foreach ($content_tags as $content_tag) {
            $content = trim($content_tag->nodeValue);
        }

        wp_insert_post([
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_name'    => $post_name
        ]);
        add_action('save_post','do_stuff');
    }
}
add_action('save_post','do_stuff');