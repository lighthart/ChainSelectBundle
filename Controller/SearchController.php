<?php

namespace Lighthart\SelectizeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller
{
    public function searchAction( $class, $criteria = null ) {

        $classPath = str_replace( '_', '\\', $class );
        $urlPath   = $class ;
        $class     = substr( strrchr( $class, '_' ), 1 );
        $em        = $this->getDoctrine()->getManager();
        $em->getMetadataFactory()->getAllMetadata();

        if ( !$em->getMetadataFactory()->hasMetadatafor( $classPath ) ) {
            throw $this->createNotFoundException( 'No metadata for class: '.$classPath );
        }

        $metadata = $em->getMetadataFactory()->getMetadataFor( $classPath );
        $associations = array_map(
            function( $e ) {
                return $e['fieldName'];
            },
            array_filter(
                $metadata->getAssociationMappings(),
                function( $e ) {
                    return $e['type']<=2;}
                    )
            );

        $criteria = array_filter( explode( '__', $criteria ) );

        foreach ( $criteria as $key => $value ) {
            unset( $criteria[$key] );
            $newKey   = strstr( $value, '_', true );
            $newValue = substr( strstr( $value, '_', false ), 1 );
            if ( in_array( $newKey, $associations ) ) {
                $criteria[$newKey] = $newValue;
            }
        }

        $rep = $this->getDoctrine()->getRepository($classPath);
        $qb = $rep->createQueryBuilder('root');

        print_r($qb->getQuery()->getDql());
        print_r("<br>");
        foreach ($criteria as $field => $values){
            $qb->andWhere($qb->expr()->in('root.'.$field, ':'.$field));
            $qb->setParameter(':'.$field, explode(',', $values));
            print_r($qb->getQuery()->getDql());
            print_r("<br>");
        }

        $results = $qb->getQuery()->getResult();
        var_dump($results);

        // $legalCriteria = [

        // 'filter'   => null,

        // // searches is an array of fields to search different than default
        // 'searches'=> null,

        // 'limit'  => null,
        // 'page'   => null,
        // 'order'  => null,
        // 'count'  => null,
        // ],

        var_dump( $class );
        var_dump( $urlPath );
        var_dump( $classPath );
        var_dump( $criteria );
        var_dump( $associations );
        die;
        return $this->render( 'LighthartSelectizeBundle:Default:index.html.twig', array( 'name' => $name ) );
    }
}
