<div class="wrap chimpbridge">
	<div class="leftcol">
	    <h2><?php esc_html_e( 'ChimpBridge Settings', 'chimpbridge' ); ?></h2>

		<?php if( isset($_GET['settings-updated']) ) : ?>
			<div id="message" class="updated">
				<p><strong><?php esc_html_e( 'Settings saved.', 'chimpbridge' ) ?></strong></p>
			</div>
		<?php endif; ?>

		<form action="options.php" method="POST">
			<?php settings_fields( 'chimpbridge_settings_fields' ); ?>

			<?php do_settings_sections( 'chimpbridge-settings' ); ?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php if ( ! class_exists( 'ChimpBridgePro' ) ): ?>
    <div class="rightcol">
		<div class="chimpbridge-cta">
			<h3><?php esc_html_e( 'ChimpBridge Pro', 'chimpbridge' ); ?></h3>
			<?php esc_html_e( 'This plugin has a premium version, unlocking several powerful features.', 'chimpbridge' ); ?> <a href="https://chimpbridge.com/features/?utm_campaign=cta-rightcol-top&utm_source=plugin" target="_blank"><?php esc_html_e( 'Have a look!', 'chimpbrige' ); ?></a>
		</div>
	</div>
    <?php endif; ?>
</div>
