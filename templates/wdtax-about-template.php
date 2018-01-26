<?php
/**
 * A template displaying archive pages for the about taxonomy
 *
 * Used to display the about taxonomy if the theme does not provide a
 * does not provide a more specific archive template
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each one. For example, tag.php (Tag archives),
 * category.php (Category archives), author.php (Author archives), etc.
 *
 * based on the twentysixteen archive and content templates
 */

get_header();
$term_id = get_queried_object_id();
$term_meta = get_term_meta( $term_id );

?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
			<header class="page-header">
				<?php
					the_archive_title( '<h1 class="page-title">', '</h1>' );
					the_archive_description( '<div class="taxonomy-description">', '</div>' );
					foreach ( array_keys( $term_meta ) as $key ) {
						print_r( '<b>'.$key.': </b>');
						print_r($term_meta[$key][0]);
						echo '<br />' ;
					}
				?>
			</header><!-- .page-header -->

		<?php if ( have_posts() ) : 
			// Start the Loop.
			while ( have_posts() ) : the_post();

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( sprintf( '<h3 ><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
	</header><!-- .entry-header -->

	<?php the_excerpt(); ?>

	<?php twentysixteen_post_thumbnail(); ?>

</article><!-- #post-## -->
<?php
			endwhile;

			// Previous/next page navigation.
			the_posts_pagination( array(
				'prev_text'          => __( 'Previous page', 'twentysixteen' ),
				'next_text'          => __( 'Next page', 'twentysixteen' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>',
			) );

		// If no content, include the "No posts found" template.
		else :
			?>
			<header class="page-header">
				<?php
					the_archive_title( '<h1 class="page-title">', '</h1>' );
					the_archive_description( '<div class="taxonomy-description">', '</div>' );
				?>
			</header><!-- .page-header -->
<?php
			get_template_part( 'template-parts/content', 'none' );
		endif;
		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
