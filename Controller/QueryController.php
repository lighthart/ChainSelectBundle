<?php

namespace Lighthart\ChainSelectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class QueryController extends Controller
{
    private $legalCriteria =
        [
    'limit'  => null,
    'page'   => null,
    // 'count'  => null,
    'qbOnly' => null,
    ];

    public function jsonAction(Request $request, $class, $method=null, $criteria = null)
    {

        if ($method) {
        } else {
            throw $this->createNotFoundException('Method for display not Specified');
        }

        $classPath = str_replace('_', '\\', $class);
        $urlPath   = $class;
        $class     = substr(strrchr($class, '_'), 1);
        $em        = $this->getDoctrine()->getManager();
        $em->getMetadataFactory()->getAllMetadata();

        if (!$em->getMetadataFactory()->hasMetadatafor($classPath)) {
            throw $this->createNotFoundException('No metadata for class: ' . $classPath);
        }

        $metadata = $em->getMetadataFactory()->getMetadataFor($classPath);
        $associations = array_map(
            function ($e) {
                return $e['fieldName'];
            },
            array_filter(
                $metadata->getAssociationMappings(),
                function ($e) {
                    return $e['type'];}
            )
        );


        $criteria = array_filter(explode('__', $criteria));
        $options  = [];

        foreach ($criteria as $key => $value) {
            unset($criteria[$key]);
            $newKey   = strstr($value, '_', true);
            $newValue = substr(strstr($value, '_', false), 1);
            if (in_array($newKey, $associations)) {
                $criteria[$newKey] = $newValue;
            }

            if (in_array($newKey, array_keys($this->legalCriteria))) {
                $options[$newKey] = $newValue;
            }
        }

        $count = ('count' == $method);

        $rep = $this->getDoctrine()->getRepository($classPath);
        $qb  = $rep->createQueryBuilder('root');

        foreach ($criteria as $field => $values) {
            $qb->join('root.' . $field, $field);
            $qb->andWhere($qb->expr()->in($field . '.id', ':' . $field));

            $inValues = explode(',', $values);
            // added so doctrine does not parse an empty list for WHERE/IN clause
            $inValues[] = 0;
            $qb->setParameter(':' . $field, $inValues);
        }


        foreach ($options as $field => $value) {
            if ($count) {
                // ignore paging if count is set
                // we are getting a total count in that case
            } elseif (isset($options['limit']) && preg_match('/\d+/', $options['limit'])) {
                $qb->setMaxResults($options['limit']);
                if (isset($options['page']) && preg_match('/\d+/', $options['page'])) {
                    // implicitly limit to 10 results unless otherwise specified
                    $qb->setFirstResult(($options['page']-1) * ($options['limit'] ?: 10));
                }
            }
        }

        $callback = $request->query->get( 'callback' );

        if ($count) {
            $qb->select($qb->expr()->count('DISTINCT root'));
            return $this->render('LighthartChainSelectBundle::count.html.twig', [ 'count' => $qb->getQuery()->getSingleScalarResult() ]);

        } elseif (isset($options['qbOnly']) && $options['qbOnly'] && preg_match('/\d+/', $options['qbOnly'])) {
            return $qb;
        } else {
            $result = $qb->getQuery()->getResult();
            $results=[];
            array_map(function($res) use (&$results,$method) { $results[$res->getId()]=$res->$method(); }, $result);
            $qb->select($qb->expr()->count('DISTINCT root'));
            $total = $qb->getQuery()->getSingleScalarResult();
            return $this->render('LighthartChainSelectBundle::results.html.twig', [ 'results' => $results, 'total'=> $total, 'callback' => $callback ]);
        }
    }
}
