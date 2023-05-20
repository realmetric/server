<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function home()
    {
        return $this->json("Realmetric - data server for analytics. Try to login and access this data via site realmetric.github.io");
    }
}