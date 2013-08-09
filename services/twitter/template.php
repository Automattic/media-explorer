<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class EMM_Twitter_Template extends EMM_Template {

	public function item( $id, $tab ) {
		?>
		<div id="emm-item-twitter-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="emm-item-area" data-id="{{ data.id }}">
			<div class="emm-item-container clearfix">
				<div class="emm-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>
				<div class="emm-item-main">
					<div class="emm-item-author">
						<span class="emm-item-author-name">{{ data.meta.user.name }}</span>
						<span class="emm-item-author-screen-name"><span class="emm-item-author-at">@</span>{{ data.meta.user.screen_name }}</span>
					</div>
					<div class="emm-item-content">
						{{{ data.content }}}
					</div>
					<div class="emm-item-date">
						{{ data.date }}
					</div>
				</div>
			</div>
		</div>
		<a href="#" id="emm-check-{{ data.id }}" data-id="{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'emm' ); ?>">
			<div class="media-modal-icon"></div>
		</a>
		<?php
	}

	public function thumbnail( $id ) {
		?>
		<?php
	}

	public function search( $id, $tab ) {

		switch ( $tab ) {

			case 'hashtag':

				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="hashtag"
						value="{{ data.params.hashtag }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Hashtag', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'by_user':

				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="by_user"
						value="{{ data.params.by_user }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'to_user':

				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="to_user"
						value="{{ data.params.to_user }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'location':

				?>
				<div id="emm_twitter_map_canvas"></div>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						id="<?php echo esc_attr( $id ); ?>-coords"
						type="hidden"
						name="coords"
						value="{{ data.params.location }}"
					>
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
					<label for="<?php echo esc_attr( $id ); ?>-name">
						<?php _e( 'Location:', 'emm' ); ?>
					</label>
					<input
						type="text"
						id="<?php echo esc_attr( $id ); ?>-name"
						name="location"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter location', 'emm' ); ?>"
					>
					<select
						id="<?php echo esc_attr( $id ); ?>-radius"
						type="text"
						name="radius"
						class="emm-input-text emm-input-select"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
						<?php foreach ( array( 1, 5, 10, 20, 50, 100, 200 ) as $km ) { ?>
							<option value="<?php echo absint( $km ); ?>"><?php printf( esc_html__( 'Within %skm', 'emm' ), $km ); ?></option>
						<?php } ?>
					</select>
					<input
						type="submit"
						class="button button-large"
						value="<?php esc_attr_e( 'Search', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'all':
			default:

				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

		}

	}

}
