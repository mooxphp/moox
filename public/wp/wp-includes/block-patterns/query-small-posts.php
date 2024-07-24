<?php
/**
 * Query: Small image and title.
 */

return [
    'title' => _x('Small image and title', 'Block pattern title'),
    'blockTypes' => ['core/query'],
    'categories' => ['query'],
    'content' => '<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
					<div class="wp-block-query">
					<!-- wp:post-template -->
					<!-- wp:columns {"verticalAlignment":"center"} -->
					<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"25%"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:25%"><!-- wp:post-featured-image {"isLink":true} /--></div>
					<!-- /wp:column -->
					<!-- wp:column {"verticalAlignment":"center","width":"75%"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:75%"><!-- wp:post-title {"isLink":true} /--></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->
					<!-- /wp:post-template -->
					</div>
					<!-- /wp:query -->',
];
