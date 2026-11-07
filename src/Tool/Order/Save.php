<?php
namespace Aimeos\Admin\Mcp\Tool\Order;
final class Save extends \Aimeos\Admin\Mcp\Tool\Action\Save
{
	/**
	 * Returns the domains of the referenced items which must be loaded for updating
	 *
	 * @param array<int, array<string, mixed>> $entries
	 * @return array<int, string>
	 */
	protected function references( array $entries ) : array
	{
		$result = parent::references( $entries );

		$parts = ['address', 'product', 'service'];

		foreach( $entries as $entry )
		{
			// changing any order part recalculates the price from products and services, so load all parts
			if( array_intersect_key( $entry, array_flip( $parts ) ) !== [] ) {
				array_push( $result, 'order/address', 'order/product', 'order/service' );
			}

			// read-only statuses are compared to the stored ones, coupons are read separately
			// because saving an order with loaded coupons adds their product references again
			isset( $entry['status'] ) ? $result[] = 'order/status' : null;
		}

		return array_values( array_unique( $result ) );
	}
}
