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
defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

get_header();
global $wp;
global $wdtax_taxonomies; //instance of object from inc/taxonomy_class.php
$term_id = get_queried_object_id();
$term = get_term( $term_id );
$wdtax_rel = str_replace('wdtax_','',$term->taxonomy);
$wdtax_taxonomy = $wdtax_taxonomies[$wdtax_rel];
//$term_meta = get_term_meta( $term_id );
$type = get_term_meta( $term_id, 'schema_type', True );
?>
	<div id="primary" class="content-area"
	     vocab="http://schema.org/"
			 resource="<?php echo home_url( $wp->request ).'#id'; ?>"
			 typeof ="<?php echo  $type ?>" >
		<main id="main" class="site-main" role="main">
			<header class="page-header">
				<?php
				  echo '<h1 class="page-title">Index page for ';
					echo $wdtax_taxonomy->schema_text( $term_id, 'wd_name' );
					echo '</h1>';
          echo '<div class="taxonomy-description" > ';
					if ( 'Person' === $type ){
						echo $wdtax_taxonomy->schema_person_details( $term_id );
					} elseif ('Organization' === $type ) {
						echo $wdtax_taxonomy->schema_organization_details( $term_id );
					} elseif ('Book' === $type ) {
						echo $wdtax_taxonomy->schema_book_details( $term_id );
					} elseif ('CreativeWork' === $type ) {
						echo $wdtax_taxonomy->schema_creativework_details( $term_id );
					} elseif ('Place' === $type ) {
						echo $wdtax_taxonomy->schema_place_details( $term_id );
					} elseif ('Event' === $type ) {
						echo $wdtax_taxonomy->schema_event_details( $term_id );
					} else {
						echo $wdtax_taxonomy->schema_text($term_id, 'wd_description');
					}
					echo '</div>';
					echo $wdtax_taxonomy->schema_sameas_all( $term_id );
				?>
			</header><!-- .page-header -->
<?php
$options_arr = get_option( 'wdtax_options' );
if ( isset( $options_arr['rels'] ) ) {
	//multiloop for each relation taxonomy
	foreach ( $options_arr['rels'] as $rel ) {
		$args = array(
			'post_type' => 'any',
			'tax_query' => array(
				array(
					'taxonomy' => 'wdtax_'.$rel,
					'field'    => 'name',
					'terms'    => array( $term->name )
				)
			)
		);
		$wdtax_query = new WP_Query( $args );
		if ('about' == $rel) {
			$heading = 'Pages about ';
		} elseif ( 'mentions' == $rel ) {
			$heading = 'Pages that mention ';
		} elseif ( 'citation' == $rel ) {
			$heading = 'Pages citing ';
		} else  {
			$heading = 'Pages that '.$rel.': ';
		}
		if ( $wdtax_query->have_posts() ) :
			echo '<h2>'.$heading.$wdtax_taxonomy->schema_text( $term_id, 'wd_name' ).'</h2>';
			// Start the Loop.
			while ( $wdtax_query->have_posts() ) : $wdtax_query->the_post();
		?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>
					resource="<?php echo( esc_url( get_permalink() ) )?>"
					typeof="WebPage">
					<header class="entry-header">
						<?php the_title( sprintf( '<h3 property="name"><a href="%s">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
					</header><!-- .entry-header -->

					<?php the_excerpt(); ?>
					<link property="<?php echo $rel ?>"
					      href="<?php echo home_url( $wp->request ).'#id'; ?>" />

				</article><!-- #post-## -->
		<?php
		endwhile; //a loop
		endif;
		wp_reset_postdata();
	} //end multiloop over relation taxonomies
} else {
	//no relation taxonomies
	get_template_part( 'template-parts/content', 'none' );
}
// Previous/next page navigation.
the_posts_pagination( array(
	'prev_text'          => __( 'Previous page', 'twentysixteen' ),
	'next_text'          => __( 'Next page', 'twentysixteen' ),
	'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>',
) );

		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>