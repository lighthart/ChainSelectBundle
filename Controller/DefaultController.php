<?php

namespace Lighthart\SelectizeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('LighthartSelectizeBundle:Default:index.html.twig', array('name' => $name));
    }
}
