<?php
/**
 * Custom Login Template
 *
 * This template is used for the custom client login page.
 * It outputs a full HTML page with a Bootstrap‑styled login box,
 * without calling the theme’s header or footer.
 *
 * @package ClaimsManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Client Login', 'claims-management' ); ?></title>
	<!-- Optionally include wp_head() if you need some WordPress scripts -->
	<?php wp_head(); ?>
	<!-- Enqueue Bootstrap CSS (if not already enqueued) -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<style>
		/* Additional custom styles if needed */
		body {
			background-color: #f5f5f5;
		}
	</style>
</head>
<body <?php body_class(); ?>>
	<div class="container">
		<div class="row justify-content-center" style="margin-top: 50px;">
			<div class="col-md-6 col-lg-4">
				<div class="card shadow">
					<div class="card-header text-center bg-primary text-white">
						<h3 class="mb-0"><?php esc_html_e( 'Client Login', 'claims-management' ); ?></h3>
					</div>
					<div class="card-body">
						<?php
						// Display the WordPress login form with redirect to the client portal after login.
						echo wp_login_form( [
							'echo'     => false,
							'redirect' => home_url( '/client-portal/' ),
						] );
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>