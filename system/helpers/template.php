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
 * Templates are a simple tool for loading public ("front end") views.
 *
 * It works on a cascading principle: by default the shipped view in the Teachblog public_views directory will be used,
 * however this can be overriden by the existence of a template of the same name in the active theme's teachblog/views
 * directory. In turn, either of these can be overriden using a filter hook.
 */
class Teachblog_Template extends Teachblog_Base_Object {
	protected $path;
	protected $vars = array();


	public function __construct($path, array $vars = null) {
		parent::__construct();

		$default_path = $this->system->dir . "system/public_views/$path.php";
		$theme_path = locate_template("teachblog/views/$path.php");

		if (!empty($theme_path) and file_exists($theme_path)) $this->path = $theme_path;
		elseif (file_exists($default_path)) $this->path = $default_path;

		$this->path = apply_filters('teachblog_template_path', $this->path);
		if (is_array($vars)) $this->vars = $vars;
	}


	public function __toString() {
		ob_start();
		extract($this->vars);
		include $this->path;
		return ob_get_clean();
	}
}