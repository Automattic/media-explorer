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

class MEXP_Twitter_Template extends MEXP_Template {

	public function item( $id, $tab ) {
		?>
		<div id="mexp-item-twitter-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
				<div class="mexp-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>
				<div class="mexp-item-main">
					<div class="mexp-item-author">
						<span class="mexp-item-author-name">{{ data.meta.user.name }}</span>
						<span class="mexp-item-author-screen-name"><span class="mexp-item-author-at">@</span>{{ data.meta.user.screen_name }}</span>
					</div>
					<div class="mexp-item-content">
						{{{ data.content }}}
					</div>
					<div class="mexp-item-date">
						{{ data.date }}
					</div>
				</div>
			</div>
		</div>
		<a href="#" id="mexp-check-{{ data.id }}" data-id="{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'mexp' ); ?>">
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
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="hashtag"
						value="{{ data.params.hashtag }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Hashtag', 'mexp' ); ?>"
					>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'by_user':

				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="by_user"
						value="{{ data.params.by_user }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'mexp' ); ?>"
					>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'to_user':

				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="to_user"
						value="{{ data.params.to_user }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'mexp' ); ?>"
					>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'location':

				?>
				<div id="mexp_twitter_map_canvas"></div>
				<form action="#" class="mexp-toolbar-container clearfix">
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
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'mexp' ); ?>"
					>
					<label for="<?php echo esc_attr( $id ); ?>-name">
						<?php esc_attr_e( 'Location:', 'mexp' ); ?>
					</label>
					<input
						type="text"
						id="<?php echo esc_attr( $id ); ?>-name"
						name="location"
						value="{{ data.params.q }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter location', 'mexp' ); ?>"
					>
					<select
						id="<?php echo esc_attr( $id ); ?>-radius"
						type="text"
						name="radius"
						class="mexp-input-text mexp-input-select"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'mexp' ); ?>"
					>
						<?php foreach ( array( 1, 5, 10, 20, 50, 100, 200 ) as $km ) { ?>
							<option value="<?php echo absint( $km ); ?>"><?php printf( esc_html__( 'Within %skm', 'mexp' ), $km ); ?></option>
						<?php } ?>
					</select>
					<input
						type="submit"
						class="button button-large"
						value="<?php esc_attr_e( 'Search', 'mexp' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php

				break;

			case 'images':
			case 'all':
			default:

				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'mexp' ); ?>"
					>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<div class="spinner"></div>
				</form>
				<?php

				break;

		}

	}

}
