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
	use Symfony\Component\HttpFoundation\ResponseHeaderBag;
	use Symfony\Component\Routing\Annotation\Route;
	use Symfony\Component\HttpFoundation\File\UploadedFile;
	use Symfony\Component\HttpFoundation\File\Exception\FileException;

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

//      return $this->file($file, $product->getTitle(), ResponseHeaderBag::DISPOSITION_INLINE);

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

		    $image = new UploadedFile($form->getData()->getImage(), "");
		    if ($this->validateFormProduct($product, $image, $image->guessExtension())){
			    $imageName = $this->generateUniqueImageName().'.'.$image->guessExtension();

			    try {
				    $image->move(
					    $this->getParameter('images_directory'),
					    $imageName
				    );

				    $product->setImage($this->getParameter('images_directory')."/".$imageName);
				    $this->createProduct($product);

				    $this->addFlash(
					    'success',
					    'Produto cadastrado com sucesso!'
				    );

				    return $this->redirectToRoute('index_products');
			    } catch (FileException $e) {
				    $this->addFlash(
					    'success',
					    'Falha no upload da imagem!'
				    );
			    }
		    }
	    }

	    return $this->render('product/new.html.twig', [
	    	'form' => $form->createView()
	    ]);
    }

		// Private functions

		// Create products
		private function createProduct(Product $product) {
	    $entityManager = $this->getDoctrine()->getManager();

	    $entityManager->persist($product);
	    $entityManager->flush();
    }


		// Functions to get product from DB
		private function getAllProducts() {
			return $this->getDoctrine()->getRepository(Product::class)->findAll();
		}

		private function getProduct($id){
    	return $this->getDoctrine()->getRepository(Product::class)->find($id);
		}


		// Generators
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

		private function generateUniqueImageName(){
    	return md5(uniqid());
		}


		// Functions validations
		private function validateFormProduct(Product $form, $img_file, $img_extension) {
    	if ($this->invalidTitle($form->getTitle()) || $this->invalidDescription($form->getDescription()."")){
		    $this->addFlash(
			    'danger',
			    'Nossa! Você é um verdadeiro haker ehm?!'
		    );

		    return false;
	    }

    	if ($this->invalidImageType($img_extension)){
				$this->addFlash(
					'warning',
					'Tipo de imagem invalido!'
				);
		    return false;
	    }

    	if ($this->invalidImageSize($img_file)){
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

		private function invalidImageType($extension) {
			$permittedTypes = array("jpg", "jpeg", "png", "gif");
			return !in_array($extension, $permittedTypes);
		}

		private function invalidImageSize($img) {
			return $img->getClientSize() > 5000000;
		}
	}

