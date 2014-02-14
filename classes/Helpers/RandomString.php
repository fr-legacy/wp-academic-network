<?php
namespace WPAN\Helpers;


class RandomString
{
	protected static $dictionary = array(
		'bettering', 'codfish', 'mirror', 'mount', 'rugged', 'realms', 'stray', 'breadless', 'supernova', 'obtaining',
		'notion', 'appellation', 'gifts', 'considering', 'missy', 'confined', 'assist', 'successful', 'blessing',
		'which', 'ragged', 'gravehill', 'friendships', 'ends', 'stockraiser', 'prosperity', 'twentyfour', 'sturdily',
		'thanechampions', 'warfamous', 'reproach', 'doll', 'precipitate', 'emulate', 'fellowinscrutables', 'spirit',
		'guilty', 'counsellors', 'firefighter', 'complished', 'setoff', 'saxon', 'backwoods', 'attendance',
		'shopkeeper', 'guest', 'farland', 'glories', 'engineering', 'faintness', 'winding', 'epitomized', 'flambeau',
		'entails', 'herebald', 'harried', 'cruelest', 'warstorms', 'then', 'pertly', 'assiduously', 'vast',
		'warstrength', 'quota', 'dangling', 'wizardry', 'higharched', 'workreducing', 'claptrap', 'forgiveness',
		'violent', 'themthat', 'multitude', 'restoration', 'charmed', 'essentials', 'ecclesiastics', 'tramping',
		'footlights', 'overstepping', 'catering', 'inward', 'pyramids', 'determinedly', 'goodwife', 'overequipped',
		'onetheres', 'preparation', 'etcall', 'choused', 'perceivere', 'clustered', 'veinous', 'exclude', 'veriest',
		'licking', 'honors', 'understood', 'abbreviations', 'rushing', 'situation', 'alien', 'howling', 'cottageowning',
		'bde', 'richmond', 'sworddrink', 'accumulating', 'sobbing', 'illconcealed', 'wealthy', 'parsimonious',
		'hearers', 'verging', 'quainter', 'wonderful', 'intelligenceor', 'predicated', 'reputation', 'sharpening',
		'undulating', 'gaspard', 'regular', 'painful', 'purchase', 'expectant', 'wavecurrents', 'laydown', 'greece',
		'imitated', 'refresh', 'laughingly', 'veritable', 'sparkled', 'occupations', 'penalties', 'carnage', 'hissed',
		'saddlebright', 'sank', 'servility', 'warstrife', 'theewhich', 'traitor', 'betook', 'gutter', 'wayland',
		'blindnesses', 'distressful', 'boyish', 'pleasures', 'fishing', 'temporary', 'ahigh', 'kampf', 'clumsily',
		'shiver', 'prevaricate', 'strive', 'academic', 'student', 'pupil', 'excellence', 'stars', 'brilliance', 'high',
		'achiever', 'success', 'wonderful', 'amazing', 'shine', 'excel', 'hardwork', 'tremendous', 'awesome', 'sauce',
		'stupendous', 'courage', 'honesty', 'bright', 'catfish', 'dogfish', 'eel', 'owl', 'minerva', 'zombie',
		'nation', 'richly', 'egg', 'gardener', 'paramedic', 'doctor', 'lawyer', 'solicitor', 'programmer', 'milkman',
		'singer', 'songwriter', 'traindriver', 'brilliance', 'short', 'tall', 'crown', 'imperial', 'surely', 'red',
		'green', 'blue', 'yellow', 'purple', 'mauve', 'violet', 'grey', 'orange', 'gorilla', 'manitee', 'seahorse',
		'ninja', 'druid', 'vortex', 'vacuum', 'batfish', 'echo', 'dungeon', 'nightly', 'build', 'crimson', 'eggchop',
		'horse', 'creature', 'pin', 'rolling', 'smart', 'clever', 'elephant', 'memory'
	);

	protected static $symbols = '!@#$%^&*()-=_+[]{};\':",.<>/?';

	public static function generate() {
		$suitable = false;
		$password = '';

		while ( ! $suitable ) {
			$password .= self::add_element();
			if ( strlen( $password ) > 10 ) $suitable = true;
		}

		return self::mogrify( $password );
	}

	protected static function add_element() {
		switch ( rand( 0, 6 ) ) {
			case 2: return self::symbol(); break;
			case 3: return self::number(); break;
			default: return self::word(); break;
		}
	}

	public static function word() {
		$word = self::$dictionary[rand( 0, count( self::$dictionary ) - 1 )];

		switch( rand( 0, 3 ) ) {
			case 0: return strtoupper( $word ); break;
			case 1: return ucfirst( $word ); break;
			case 2: return lcfirst( $word ); break;
		}

		return $word;
	}

	public static function symbol() {
		$symbol = '';
		$number = rand( 1, 2 );
		$max = strlen( self::$symbols ) - 1;

		for ( $i = 0; $i <= $number; $i++ )
			$symbol .= self::$symbols[$i];

		return $symbol;
	}

	public static function number() {
		return rand( 0, 99 );
	}

	public static function mogrify( $string ) {
		$length = strlen( $string );

		for ( $i = 0; $i < $length; $i++ ) {
			if ( 0 === rand( 0, 6 ) ) continue;
			if ( 'l' === $string[$i] ) $string[$i] = '1';
			if ( 'I' === $string[$i] ) $string[$i] = '1';
			if ( 'o' === $string[$i] ) $string[$i] = '0';
			if ( 'O' === $string[$i] ) $string[$i] = '0';
			if ( 'e' === $string[$i] ) $string[$i] = '3';
			if ( 'E' === $string[$i] ) $string[$i] = '3';
			if ( 's' === $string[$i] ) $string[$i] = '5';
			if ( 'S' === $string[$i] ) $string[$i] = '5';
			if ( 'z' === $string[$i] ) $string[$i] = '2';
			if ( 'Z' === $string[$i] ) $string[$i] = '2';
			if ( 'G' === $string[$i] ) $string[$i] = '6';
			if ( 'B' === $string[$i] ) $string[$i] = '8';
			if ( 'b' === $string[$i] ) $string[$i] = '8';
			if ( 'a' === $string[$i] ) $string[$i] = '@';
			if ( 'h' === $string[$i] ) $string[$i] = '4';
		}

		return $string;
	}
}