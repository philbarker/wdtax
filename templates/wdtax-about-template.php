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
global $wp;
global $wdtax_about_taxonomy; //instance of object from inc/taxonomy_class.php
$term_meta = get_term_meta( $term_id );
$type = $wdtax_about_taxonomy->type_map[$term_meta['wd_type'][0]]
?>
	<div id="primary" class="content-area"
	     vocab="http://schema.org/"
			 resource="<?php echo home_url( $wp->request ).'#id'; ?>"
			 <?php echo $wdtax_about_taxonomy->schema_type( $term_id ) ?> >
		<main id="main" class="site-main" role="main">
			<header class="page-header">
				<?php
				  echo '<h1 class="page-title">Pages about: ';
					echo $wdtax_about_taxonomy->schema_text( $term_id, 'wd_name' );
					echo '</h1>';
          echo '<div class="taxonomy-description" > ';
					if ( 'Person' === $type ){
						echo $wdtax_about_taxonomy->schema_person_details( $term_id );
					} elseif ('Book' === $type ) {
						echo $wdtax_about_taxonomy->schema_book_details( $term_id );
					} elseif ('Place' === $type ) {
						echo $wdtax_about_taxonomy->schema_place_details( $term_id );
					}
					echo '</div>';
					echo $wdtax_about_taxonomy->schema_sameas_all( $term_id );
				?>
			</header><!-- .page-header -->

		<?php if ( have_posts() ) :
			// Start the Loop.
			while ( have_posts() ) : the_post();

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>
	property="subjectOf" resource="<?php echo( esc_url( get_permalink() ) )?>"
	typeof="WebPage">
	<header class="entry-header">
		<?php the_title( sprintf( '<h3 property="name"><a href="%s">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
	</header><!-- .entry-header -->

	<?php the_excerpt(); ?>

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
			get_template_part( 'template-parts/content', 'none' );
		endif;
		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
