<?php

	namespace App\Controller;

	use App\Entity\Product;
	use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
	use Symfony\Component\Form\Extension\Core\Type\SubmitType;
	use Symfony\Component\Form\Extension\Core\Type\TextareaType;
	use Symfony\Component\Form\Extension\Core\Type\TextType;
	use Symfony\Component\Form\Extension\Core\Type\IntegerType;
	use Symfony\Component\Form\Extension\Core\Type\FileType;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\Routing\Annotation\Route;

	use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;

	class ProductController extends AbstractController
	{
		/**
     * @Route("/products", name="index_products")
     */
    public function index()
    {
			$products = $this->getAllProducts();
      return $this->render('product/index.html.twig', [
          'products' => $products
      ]);
    }

		/**
     * @Route("/product/new", name="new_product")
     * */
    public function new(Request $request)
    {
    	$form = $this->generateFormProduct();

	    $form->handleRequest($request);

	    if ($form->isSubmitted() && $form->isValid()) {
	      $product = $form->getData();

	      $entityManager = $this->getDoctrine()->getManager();

		    $normalizer = new DataUriNormalizer();
		    $product->setImage($normalizer->normalize(new \SplFileObject($product->getImage())));

		    $entityManager->persist($product);

		    $entityManager->flush();

	      return $this->redirectToRoute('index_products');
	    }

	    return $this->render('product/new.html.twig', [
	    	'form' => $form->createView()
	    ]);
    }

    //Private functions

		private function getAllProducts(){
			return $this->getDoctrine()->getRepository(Product::class)->findAll();
		}

		private function generateFormProduct(){
			$product = new Product();

			$form = $this->createFormBuilder($product)
				->add('title',TextType::class, [
					'required' => true,
					'label' => 'Titulo: ',
					'attr' => ['minlength' => 6, 'id' => 'title_product']
				])
				->add('description', TextareaType::class,[
					'required' => false,
					'label' => 'Descrição: ',
					'attr' => ['maxlength' => 4000, 'id' => 'description_product']
				])
				->add('image', FileType::class, [
					'required' => true,
					'attr' => ['accept' => 'image/jpeg, image/png, image/gif'],
					'label' => 'Imagem do produto(JPG, PNG ou GIF): ',
					'help' => 'A imagem deve ter um peso maximo de 5 MBs.'
				])
				->add('stock', IntegerType::class, [
					'required' => true,
					'label' => 'Quantidade em estoque: '
				])
				->add('save', SubmitType::class, [
					'label' => 'Criar Produto',
					'attr' => ['class' => 'btn btn-success']
				])
				->getForm();

			return $form;
		}
	}
