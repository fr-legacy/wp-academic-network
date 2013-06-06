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
 * Helper methods for working with strings.
 */
class Teachblog_Strings {
    /**
     * Shortens a string to the desired length (unless it is already of that length or shorter) and appends an ellipsis.
     * It has no comprehension of HTML entities even though the ellipsis itself is one.
     *
     * The ellipsis can be overriden by specifying the third optional $marker param. When this param contains a non
     * empty string and when the input string must be shortened the length will be reduced by one (to accommodate the
     * marker symbol).
     *
     * @param $string
     * @param $length
     * @param string $marker
     * @return string
     */
    public static function truncate($string, $length, $marker = '&hellip;') {
        if (!empty($marker)) $length--;
        if (strlen($string) > $length) $string = substr($string, 0, --$length).$marker;
        return $string;
    }
}