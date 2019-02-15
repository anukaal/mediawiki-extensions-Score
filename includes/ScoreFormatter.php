<?php

use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * Formats Lilypond string based on the known formats
 * - text/plain: used in the value input field of Wikidata
 * - text/x-wiki: wikitext
 * - text/html: used in Wikidata to display the value of properties
 * Formats can look like this: "text/html; disposition=diff"
 * or just "text/plain"
 */
class ScoreFormatter implements ValueFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * Loads format to distinguish the type of formatting
	 *
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 */
	public function __construct( $format ) {
		$this->format = $format;
	}

	/**
	 * @param StringValue $value
	 *
	 * @throws InvalidArgumentException if not called with a StringValue
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( '$value must be a StringValue' );
		}

		$valueString = $value->getValue();

		switch ( $this->format ) {
			case SnakFormatter::FORMAT_PLAIN:
				return $valueString;
			case SnakFormatter::FORMAT_WIKI:
				return "<score>$valueString</score>";
			default:
				try {
					$valueHtml = Score::renderScore(
						$valueString,
						[],
						MediaWikiServices::getInstance()->getParser()
					);
				} catch ( ScoreException $exception ) {
					return (string)$exception;
				}

				if ( $this->format === SnakFormatter::FORMAT_HTML_DIFF ) {
					$valueHtml = $this->formatDetails( $valueHtml, $valueString );
				}

				return $valueHtml;
		}
	}

	/**
	 * Constructs a detailed HTML rendering for use in diff views.
	 *
	 * @param string $valueHtml
	 * @param string $valueString
	 *
	 * @return string HTML
	 */
	private function formatDetails( $valueHtml, $valueString ) {
		$detailsHtml = '';
		$detailsHtml .= Html::rawElement( 'h4',
			[ 'class' => 'wb-details wb-musical-notation-details wb-musical-notation-rendered' ],
			$valueHtml
		);

		$detailsHtml .= Html::rawElement( 'div',
			[ 'class' => 'wb-details wb-musical-notation-details' ],
			Html::element( 'code', [], $valueString )
		);

		return $detailsHtml;
	}

	/**
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}