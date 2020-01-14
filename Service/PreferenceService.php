<?php

namespace PrimeSoftware\Service;

use Doctrine\ORM\EntityManagerInterface;

abstract class PreferenceService {

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	/**
	 * Holds the preferences, key is the code
	 * @var array
	 */
	private $preferences = [];

	/**
	 * Name of the 'Preference' class
	 * @var string
	 */
	protected $preferenceClassName = 'Preference';

	public function __construct( EntityManagerInterface $em ) {
		$this->em = $em;

		// Get the query builder
		$qb = $this->em->createQueryBuilder();

		// Build the query
		$qb->select( [ 'p' ] )
			->from( $this->preferenceClassName, 'p' );

		// Get the query object
		$query = $qb->getQuery();

		// Get the results
		$results = $query->getResult();

		// Iterate over the results
		foreach( $results as $result ) {
			$this->preferences[ $result->getCode() ] = $result;
		}
	}

	/**
	 * Get the value of the preference
	 * @param $code
	 * @return null
	 */
	public function get( $code ) {
		if ( array_key_exists( $code, $this->preferences ) ) {
			return $this->preferences[ $code ]->getValue();
		}
		else {
			return null;
		}
	}

	/**
	 * Set the value of the preference
	 * @param $code
	 * @param $value
	 */
	public function set( $code, $value ) {
		if ( array_key_exists( $code, $this->preferences ) ) {
			$preference = $this->preferences[ $code ];
			$preference->setValue( $value );
			$this->em->persist( $preference );
			$this->em->flush();
		}
		else {
			return null;
		}
	}

	/**
	 * Ensure the preferences exist
	 * @param $items
	 * @param $parent_code
	 */
	public function ensurePreferenceExists( $items, $parent_code ) {

		// Iterate over the items
		foreach( $items as $item ) {
			// Get the item code
			$item_code = ( $parent_code ? $parent_code . "." : "" ) . $item[ 'code' ];

			// Find one by the item code
			$pref = $this->em->getRepository( $this->preferenceClassName )->findOneByCode( $item_code );

			// If the preference doesn't exist
			if ( $pref === null ) {
				// Create a new one
				$pref = new $this->preferenceClassName();
				// Fill the details
				$pref->setCode( $item_code )
					->setName( $item[ 'name' ] )
					->setPrefType( $item[ 'pref_type' ] )
					->setValue( $item[ 'value' ] );
			}
			else {
				// Fill the details
				$pref->setName( $item[ 'name' ] )
					->setPrefType( $item[ 'pref_type' ] );
			}
			// Set to persist
			$this->em->persist( $pref );

			// If there are children
			if ( array_key_exists( 'children', $item ) ) {
				// Create the child preferences
				$this->ensurePreferenceExists( $item[ 'children' ], $item_code );
			}
		}
	}

}
