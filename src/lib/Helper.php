<?php

namespace Appsaloon\Processor\Lib;

class Helper {
	/**
	 * Loops array one by one
	 *
	 * @param $array
	 *
	 * @return \Generator
	 *
	 * @since 1.0.0
	 */
	public static function generator( $array ) {
		foreach ( $array as $a => $b ) {
			yield $a => $b;
		}
	}
}
