<?php

	namespace App\Controller;

	use App\Entity\Product;
	use phpDocumentor\Reflection\Types\Resource_;
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
    public function index() {
			$products = $this->getAllProducts();

      return $this->render('product/index.html.twig', [
          'products' => $products
      ]);
    }

		/**
		 * @Route("/product/{id}", name="show_product"))
		 */
		public function show($id){
			$product = $this->getProduct($id);

//			$product->setImage($this->decryptImage($product->getImage()));

			return $this->render('product/show.html.twig', [
				'product' => $product
			]);
		}

		/**
     * @Route("/products/new", name="new_product")
     */
    public function new(Request $request) {

	    $product = new Product();
    	$form = $this->generateFormProduct($product);

    	$form->handleRequest($request);

	    if ($form->isSubmitted() && $form->isValid()) {
	      $product = $form->getData();

	      if ($this->validateFormProduct($product)){
		      $this->createProduct($product);

		      $this->addFlash(
			      'success',
			      'Produto cadastrado com sucesso!'
		      );

		      return $this->redirectToRoute('index_products');
	      }
	    }

	    return $this->render('product/new.html.twig', [
	    	'form' => $form->createView()
	    ]);
    }

    private function createProduct(Product $product) {
	    $entityManager = $this->getDoctrine()->getManager();

	    $product->setImage($this->encryptImage($product->getImage()));

	    $entityManager->persist($product);
	    $entityManager->flush();
    }

    // Private functions

		// Functions to get product from DB
		private function getAllProducts() {
			return $this->getDoctrine()->getRepository(Product::class)->findAll();
		}

		private function getProduct($id){
    	return $this->getDoctrine()->getRepository(Product::class)->find($id);
		}


		// Generate Form Products
		private function generateFormProduct(Product $product) {

			$form = $this->createFormBuilder($product)
				->add('title',TextType::class, [
					'required' => true,
					'label' => 'Titulo: ',
					'attr' => ['minlength' => 6, 'id' => 'title_product'],
				])
				->add('description', TextareaType::class,[
					'required' => false,
					'label' => 'Descrição: ',
					'attr' => ['maxlength' => 4000, 'id' => 'description_product'],
				])
				->add('image', FileType::class, [
					'required' => true,
					'attr' => ['accept' => 'image/jpeg, image/png, image/gif', 'maxlength' => 5000000],
					'label' => 'Imagem do produto(JPG, PNG ou GIF): ',
					'help' => 'A imagem deve ter um peso maximo de 5 MBs.',
				])
				->add('stock', IntegerType::class, [
					'required' => true,
					'label' => 'Quantidade em estoque: ',
				])
				->add('save', SubmitType::class, [
					'label' => 'Criar Produto',
					'attr' => ['class' => 'btn btn-success']
				])
				->getForm();

			return $form;
		}


		// Functions validations
		private function validateFormProduct(Product $form) {
    	if ($this->invalidTitle($form->getTitle()) || $this->invalidDescription($form->getDescription()."")){
		    $this->addFlash(
			    'danger',
			    'Nossa! Você é um verdadeiro haker ehm?!'
		    );

		    return false;
	    }

    	if ($this->invalidImageType($form->getImage())){
				$this->addFlash(
					'warning',
					'Tipo de imagem invalido!'
				);
		    return false;
	    }

    	if ($this->invalidImageSize($form->getImage())){
				$this->addFlash(
					'warning',
					'Este arquivo excede o tamanho maximo de 5mb!'
				);
		    return false;
	    }

    	return true;
		}

		private function invalidTitle(string $title) {
			return $title === "" || strlen($title) < 6;
		}

		private function invalidDescription(string $description) {
    	return strlen($description) > 4000;
		}

		private function invalidImageType($img) {
			$permitedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
			$detectedType= exif_imagetype($img);
			return !in_array($detectedType, $permitedTypes);
		}

		private function invalidImageSize($img) {
			return filesize($img) > 5000000;
		}


		private function encryptImage($img) {
			$normalizer = new DataUriNormalizer();
			return $normalizer->normalize(new \SplFileObject($img));
		}

		private function decryptImage($img_code) {
			$normalizer = new DataUriNormalizer();
			return $normalizer->denormalize($img_code, 'SplFileObject');
		}
	}

