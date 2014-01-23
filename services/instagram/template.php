<?php

class MEXP_Instagram_Template extends MEXP_Template {

	public function thumbnail( $id ) {}

	public function item( $id, $tab ) {
		?>
		<div id="mexp-item-instagram-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area mexp-item-instagram" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
				<div class="mexp-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>
				<div class="mexp-item-main">
					<div class="mexp-item-content">
						<strong>{{ data.meta.user.username }}{{ data.content ? ':' : '' }}</strong> {{ data.content }}
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

	public function search( $id, $tab ) {

		switch ( $tab ) {

			case 'tag':
				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.tag }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter a tag', 'mexp' ); ?>"
						>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<input type="hidden" name="type" value="tag">
					<div class="spinner"></div>
				</form>
				<?php
			break;

			case 'by_user':

				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.by_user }}"
						class="mexp-input-text mexp-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter an Instagram Username', 'mexp' ); ?>"
						>
					<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'mexp') ?>">
					<input type="hidden" name="type" value="by_user">
					<div class="spinner"></div>
				</form>
				<?php
			break;

			case 'mine':
				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<span class="description"><?php _e( 'A selection of your own recent Instagrams.', 'mexp' ) ?></span>
					<input type="hidden" name="type" value="mine">
					<div class="spinner"></div>
				</form>
				<?php
			break;

			case 'feed':
				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<span class="description"><?php _e( 'The latest Instagrams from your personal feed.', 'mexp' ) ?></span>
					<input type="hidden" name="type" value="mine">
					<div class="spinner"></div>
				</form>
				<?php
			break;

			case 'popular':
				?>
				<form action="#" class="mexp-toolbar-container clearfix">
					<span class="description"><?php _e( 'A selection of trending content on Instagram.', 'mexp' ) ?></span>
					<input type="hidden" name="type" value="popular">
					<div class="spinner"></div>
				</form>
				<?php
		}
	}
}
