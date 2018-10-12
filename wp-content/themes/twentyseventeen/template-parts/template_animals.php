<?php
/**
 * Template Name: Animals
 */

get_header();

$animals = get_posts([
    'post_type' => 'animal',
    'posts_per_page' => -1
]);

?>
<div class="main-navigation">
    <div>
        <h1 class="title"><?php the_title(); ?></h1>
    </div>
    <?php foreach ($animals as $animal):
        if ($animal):
            $post_id = $animal->ID;
            $arr = get_post_meta($post_id, 'animal_info_meta_key');
            $title = $animal->post_title;
            $content = $animal->post_content;
            ?>
            <div class="item">
                <h2 class="title-post"><?php
                    echo $arr[0].' - '.$title ; ?></h2>
                <p class="content-bottom-widgets"><?php echo $content; ?></p>
                <?php if (get_the_post_thumbnail_url($post_id)): ?>
                    <div class="image"><img
                            src="<?php echo get_the_post_thumbnail_url($post_id, 'full'); ?>"
                            alt="title"></div>
                <?php endif; ?>
            </div>
        <?php
        endif;
    endforeach;
    wp_reset_postdata();
    ?>
</div>
<?php get_footer(); ?>