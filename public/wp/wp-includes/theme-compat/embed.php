<?php
/**
 * Contains the post embed base template
 *
 * When a post is embedded in an iframe, this file is used to create the output
 * if the active theme does not include an embed.php template.
 *
 * @since 4.4.0
 */
get_header('embed');

if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('embed', 'content');
    }
} else {
    get_template_part('embed', '404');
}

get_footer('embed');
