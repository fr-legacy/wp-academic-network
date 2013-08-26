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
 * Provides a general purpose framework to assist in the generation of admin list tables.
 */
class Teachblog_Admin_Table extends Teachblog_Base_Object {
	/**
	 * @var bool
	 */
	protected $checkbox = true;

	/**
	 * @var array
	 */
	protected $columns = array();

	/**
	 * This should be an array of arrays, the outer array representing each row and the inner array
	 * the column data within.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Container for the data callback function, if set.
	 *
	 * @var mixed
	 */
	protected $data_callback = false;

	/**
	 * This should be an array of key:value pairs, where the key is the action label and the value is
	 * the callback function which will execute that action.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * @var array
	 */
	protected $css_table_classes = array(
		'widefat'
	);


	/**
	 * Sets the column headers to be used in the table.
	 *
	 * @param array $columns
	 */
	public function set_columns(array $columns) {
		$this->columns = (array)apply_filters('teachblog_admin_table_columns', $columns);
		return $this;
	}


	/**
	 * Turns the checkbox column on or off (it is on by default).
	 *
	 * @param $bool
	 */
	public function use_checkbox($bool) {
		$this->checkbox = (bool)apply_filters('teachblog_admin_table_checkbox', $bool);
		return $this;
	}


	/**
	 * Sets the possible action operations, should be key:value pairs expressing the label and the
	 * callback respectively.
	 *
	 * @param array $actions
	 */
	public function set_actions(array $actions) {
		$this->actions = (array)apply_filters('teachblog_admin_table_actions', $actions);
		return $this;
	}


	/**
	 * Can directly inject the data to be used (each array element should represent a row, and in turn
	 * should contain an array of data corresponding to the number of columns).
	 *
	 * @see Teachblog_Admin_Table::set_data_callback()
	 * @param array $data
	 */
	public function set_data(array $data) {
		$this->data = (array)apply_filters('teachblog_admin_table_data', $data);
		return $this;
	}


	public function add_data_row($id, array $row) {
		$this->data[$id] = (array)apply_filters('teachblog_admin_table_data_row', $row);
		return $this;
	}


	/**
	 * Sets up a callback which will be used to obtain the data needed for the current table view. The
	 * callback function will receive a Teachblog_Admin_Table object with which it can derive offset
	 * and limit data for the current view.
	 *
	 * @param $callback
	 */
	public function set_data_callback($callback) {
		if (is_callable($callback)) $this->data_callback = $callback;
		return $this;
	}


	public function render() {
		$this->admin->view('admin_table', array(
			'actions' => $this->actions,
			'checkbox' => $this->checkbox,
			'classes' => $this->table_classes(),
			'columns' => $this->columns,
			'data' => $this->data
		));
	}


	/**
	 * @param $classes (string|array)
	 */
	public function add_css_class($classes) {
		$classes = (array)$classes;
		$this->css_table_classes = array_merge($this->css_table_classes, $classes);
		return $this;
	}

	protected function table_classes() {
		$classes = (array)apply_filters('teachblog_admin_table_css_classes', $this->css_table_classes);
		return join(' ', $classes);
	}
}