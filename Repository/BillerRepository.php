<?php

namespace Mesd\OrmedBundle\Repository;

use Doctrine\ORM\EntityRepository;

class JSONRepository extends EntityRepository {

    // this probably needs to be a service

    public function addFilter( $qb, $filter, $searches ) {

        //  $filter is the explicit request from user
        //  $searches are the fields for while the filter should be searched

        $numbers = ( isset( $searches['numbers'] ) && $searches['numbers'] ) ? $searches['numbers'] : array();
        $dates   = ( isset( $searches['dates'] )   && $searches['dates'] )   ? $searches['dates']   : array();
        $strings = ( isset( $searches['strings'] ) && $searches['strings'] ) ? $searches['strings'] : array();

        if ( $numbers == array() && $dates ==array() && $strings ==array() ) {
            // just bail out if there are no fields to search in
            return $qb;
        }

        $filter = array_filter($filter, function($e) {return !!$e;});

        if ( array( '' ) == $filter || array() == $filter ) {
            // just bail out if there is nothing to search for
            return $qb;
        }

        foreach ( $filter as $key => $value ) {
            $value = trim( $value );
            $value = str_replace( "'", "''", $value );
            $value = str_replace( ",", "", $value );
            $cqb   = array();

            if ( $strings != array() ) {
                foreach ( $strings as $stringKeys => $stringValues ) {
                    $cqb[] = $qb->expr()->like( "LOWER(CONCAT($stringValues, ''))", "'%".strtolower( $value )."%'" );
                }
            }

            if ( $numbers != array() ) {
                foreach ( $numbers as $numberKeys => $numberValues ) {
                    $cqb[] = $qb->expr()->like( "CONCAT($numberValues, '')", "'%$value%'" );
                }
            }

            if ( $dates != array() ) {
                foreach ( $dates as $dateKeys => $dateValues ) {
                    $cqb[] = $qb->expr()->like( "LOWER(CONCAT($dateValues, ''))", "'%".strtolower( $value )."%'" );
                }
            }

            // below, if value is 2007, this makes a datetime object
            // for the current day at 8:07 pm, i.e. 20:07
            // Baffling.
            // commenting out for now, and parsing like other strings.
            // if ( $dates != array() ) {
            //     foreach ( $dates as $dateKeys => $dateValues ) {
            //         $value = str_replace( '-', '/', $value );
            //         try {
            //             $date    = new \DateTime( $value );
            //             $dateout = $date->format( 'Y-m-d' );
            //             $cqb[]   = $qb->expr()->like( "CONCAT($dateValues, '')", "'%$dateout%'" );
            //         } catch ( \Exception $ex ) {
            //             $value = preg_replace( '/^(\d\d\/\d\d).*$/', '$1', $value );
            //             $cqb[] = $qb->expr()->like( "CONCAT($dateValues, '')", str_replace( '/', '-', "'%$value%'" ) );
            //         }
            //     }
            // }
            $qb->andWhere( call_user_func_array( array( $qb->expr(), "orx" ), $cqb ) );
        }


        return $qb;
    }
}
