<?php

namespace EMM\Services\Youtube;

class Template extends \EMM\Template {

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

	/**
	 * Template for the search form
	 *
	 * @param string $id  the id of the view
	 * @param string $tab the tab were the user is right now
	 */
	public function search( $id, $tab ) {
		switch ( $tab ) 
		{
			case 'by_channel':
				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search by channel name', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php
			break;

			case 'by_freebase_topic':
				?>
				<form action="#" class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="40"
						placeholder="<?php esc_attr_e( 'Search by Freebase topic', 'emm' ); ?>"
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
						placeholder="<?php esc_attr_e( 'Search Youtube', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</form>
				<?php
			break;
		}
	}

}
