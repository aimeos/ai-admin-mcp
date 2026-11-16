<?php
namespace Aimeos\Admin\Mcp\Tool\Review;

use Aimeos\Prisma\Schema\Schema;


final class Save extends \Aimeos\Admin\Mcp\Tool\Action\Save
{
	public function description() : string
	{
		return 'Updates one or more existing Aimeos review items, only the name, response and status can be changed.';
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'items' => Schema::array()->items( Schema::object( $this->fields() )->withoutAdditionalProperties() )
				->min( 1 )->required()->description( 'Reviews to update' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		// the generic save also sets private fields, so pass the writable ones only
		$fields = $this->fields();
		$arguments['items'] = array_map( fn( $entry ) => array_intersect_key( (array) $entry, $fields ), (array) $arguments['items'] );

		return parent::run( $arguments );
	}


	/**
	 * Returns the writable review fields
	 *
	 * Reviews are written by customers for products they have bought, so only moderation fields are writable.
	 *
	 * @return array<string, \Aimeos\Prisma\Schema\Types\Type>
	 */
	private function fields() : array
	{
		return [
			'id' => Schema::string()->required()->description( 'Unique review ID' ),
			'name' => Schema::string()->description( 'Displayed name of the reviewer' ),
			'response' => Schema::string()->description( 'Response of the shop to the review' ),
			'status' => Schema::integer()->description( 'Review status: 1 = enabled, 0 = disabled, -1 = in review, -2 = archived' ),
		];
	}
}
