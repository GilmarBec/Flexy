<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/product", name="product")
     */
    public function index()
    {
    	  $repository = $this->getDoctrine()->getRepository(Product::class);
				$products = $repository->findAll();
        return $this->render('product/index.html.twig', [
	          'products' => $products
        ]);
    }
}
