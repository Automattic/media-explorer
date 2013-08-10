<?php

class EMM_Youtube_Template extends EMM_Template {

	/**
	 * Template for single elements returned from the API
	 *
	 * @param string $id  the id of the view
	 * @param string $tab the tab were the user is right now
	 */
	public function item( $id, $tab ) {
		?>
		<div id="emm-item-youtube-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="emm-item-area emm-item-youtube" data-id="{{ data.id }}">
			<div class="emm-item-container clearfix">
				<div class="emm-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>
				<div class="emm-item-main">
					<div class="emm-item-content">
						{{ data.content }}
					</div>
					<div class="emm-item-channel">
						<?php _e( 'by', 'emm' ) ?> {{ data.meta.user }}
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

	/**
	 * Template for the search form
	 *
	 * @param string $id  the id of the view
	 * @param string $tab the tab were the user is right now
	 */
	public function search( $id, $tab ) {
		switch ( $tab ) 
		{
			case 'all':
				?>
				<form action="#" class="emm-toolbar-container clearfix tab-all">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search Youtube', 'emm' ); ?>"
					>
					<input type="hidden" name="tab" value="all" />
					<input type="hidden" name="page_token" value="" id="page_token" class="all"/>
					<label for="type" style="display: none">Type:</label>
					<select name="type">
						<option value="video">videos</option>
						<option value="playlist">playlists</option>
					</select>
					<div class="spinner"></div>
				</form>
				<?php
			break;
			
			case 'by_user':
				?>
				<form action="#" class="emm-toolbar-container clearfix
				tab-by_user">
					<input
						type="text"
						name="channel"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Enter Username', 'emm' ); ?>"
					>
					<input type="hidden" name="tab" value="by_user">
					<input type="hidden" name="page_token" value="" id="page_token" class="by_user"/>
					<div class="spinner"></div>
				</form>
				<?php
			break;
		}
	}

}
