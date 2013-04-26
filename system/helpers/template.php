<?php
/*
	Teachblog - provides a teacher led collaborative blogging environment
	Copyright (C) 2013 Barry Hughes

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http: *www.gnu.org/licenses/>.
*/

/**
 * Sets up and manages student posts.
 */
class Teachblog_Template extends Teachblog_Base_Object {
	protected $path;
	protected $vars = array();


	public function __construct($path, array $vars = null) {
		parent::__construct();

		$path = $this->system->dir."system/public_views/$path.php";
		if (file_exists($path)) $this->path = $path;
		if (is_array($vars)) $this->vars = $vars;
	}


	public function __toString() {
		ob_start();
		extract($this->vars);
		include $this->path;
		return ob_get_clean();
	}
}