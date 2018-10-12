<?php global $post; ?>
<table class="form-table">
	<tbody>
		<tr id="chimpbridge-row-list">
			<th>
				<label for="chimpbridge-select-lists"><?php echo esc_html__( 'Select a list', 'chimpbridge' ); ?></label>
			</th>
			<td>
				<?php $chimpbridge_selected_list = get_post_meta( intval( $postID ), '_chimpbridge_select_lists', true ); ?>
				<?php if ( 'publish' == get_post_status() ): ?>
					<?php echo esc_html( $chimpbridge_selected_list ); ?>
				<?php else: ?>
					<select id="chimpbridge-select-lists" name="_chimpbridge_select_lists">
						<option disabled selected><?php _e('Select a list', 'chimpbridge'); ?></option>
						<?php
						$lists = $this->get_mailchimp_lists();

						foreach( $lists as $list ) {
							$selected = NULL;
							if ( $list['id'] == $chimpbridge_selected_list )
								$selected = ' selected';

							echo '<option data-default-from-name="' . esc_attr( $list[ 'default_from_name' ] ) . '" data-default-from-email="' . esc_attr( $list[ 'default_from_email' ] ) . '" value="' . esc_attr( $list['id'] ) . '"' . $selected . '>' . esc_html( $list['name'] ) . '</option>';
						}
						?>
					</select>
					<a class="button chimpbridge-refresh" id="chimpbridge-refresh-lists" href="#" title="<?php _e( 'Lists are updated every 24 hours. Click to force refresh.', 'chimpbridge' ); ?>"><div class="dashicons dashicons-update"></div></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="input-text"><?php echo esc_html__( 'Select a saved segment', 'chimpbridge' ); ?></label>
			</th>
			<td>
				<?php $chimpbridge_selected_segment = get_post_meta( intval( $postID ), '_chimpbridge_select_segments', true ); ?>
				<?php if ( 'publish' == get_post_status() ): ?>
					<?php echo esc_html( $chimpbridge_selected_segment ); ?>
				<?php else: ?>
					<select id="chimpbridge-select-segments" name="_chimpbridge_select_segments">
						<?php
						if ( empty( $chimpbridge_selected_list ) ) {
							echo '<option selected disabled>' . esc_html__( 'No List Selected', 'chimpbridge' ) . '</option>';
						} else {
							$segments = $this->get_mailchimp_segments( $chimpbridge_selected_list );
							if ( empty( $segments ) ) {
								// no segments
								echo '<option readonly value="no-segments">' . apply_filters( 'chimpbridge_msg_no_segments', __( 'Segments not available', 'chimpbridge' ) ) . '</option>';
							} else {
								// has segments
								echo '<option readonly disabled>' . esc_html__( 'Select a segment', 'chimpbridge' ) . '</option>';

								if ( $chimpbridge_selected_segment == 'send-to-all' )
									$selected = 'selected';

								echo '<option value="send-to-all" ' . $selected . '>' . esc_html__( 'Send to entire list', 'chimpbridge' ) . '</option>';
							}
						}
						if ( $chimpbridge_selected_segment && $chimpbridge_selected_list ) {
							foreach( $segments as $segment ) {
								$selected = NULL;
								if ( $segment['id'] == $chimpbridge_selected_segment )
									$selected = ' selected';

								echo '<option value="' . esc_attr( $segment['id'] ) . '"' . $selected . '>' . esc_html( $segment['name'] ) . '</option>';
							}
						}
						?>
					</select>
				<?php endif; ?>

				<?php if ( get_post_status() != 'publish' ) : ?>
					<?php if ( $chimpbridge_selected_list ) : ?>
						<a class="button chimpbridge-refresh" id="chimpbridge-refresh-segments" href="#" title="<?php _e( 'Segments are updated every 24 hours. Click to force refresh.', 'chimpbridge' ); ?>"><div class="dashicons dashicons-update"></div></a>
					<?php else : ?>
						<a class="button disabled chimpbridge-refresh" id="chimpbridge-refresh-segments" href="#" title="<?php _e( 'Segments are updated every 24 hours. Click to force refresh.', 'chimpbridge' ); ?>"><div class="dashicons dashicons-update"></div></a>
					<?php endif; ?>
					<p class="description"><?php echo apply_filters( 'chimpbridge_msg_segments_help', sprintf( esc_html__( 'Send to specific segments/groups on your list using %sChimpBridge Pro%s.', 'chimpbridge' ), '<a target="_blank" href="https://chimpbridge.com/?utm_source=plugin&utm_campaign=segments-help">', '</a>' ) ); ?></p>
				<?php endif; ?>

			</td>
		</tr>
	</tbody>
</table>
