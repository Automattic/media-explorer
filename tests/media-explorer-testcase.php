<?php

/**
 * Base unit test class for Media Explorer
 */
class MediaExplorer_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $media_explorer;
		$this->_me = $media_explorer;
	}
}
