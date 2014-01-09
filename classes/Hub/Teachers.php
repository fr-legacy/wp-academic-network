<?php
namespace WPAN\Hub;

use WPAN\Helpers\WordPress;


class Teachers
{
	public function get_page() {
		return $this->menu();
	}

	protected function menu() {
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub&tab=teachers' );
		$subtabs = array(
			'current' => __( 'Current Roster', 'wpan' ),
			'update' => __( 'Update/Import', 'wpan' )
		);
		return WordPress::sub_menu( $subtabs, $base_url );
	}
}