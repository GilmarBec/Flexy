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
	use Symfony\Component\HttpFoundation\File\UploadedFile;
	use Symfony\Component\HttpFoundation\File\Exception\FileException;
	use Symfony\Component\Routing\Annotation\Route;

	/**
	 * Class ProductController
	 * @package App\Controller
	 */
	class ProductController extends AbstractController {
		/**
     * @Route("/", name="index_products")
     */
    public function index() {
			$products = $this->getAllProducts();

      return $this->render('product/index.html.twig', [
          'products' => $products
      ]);
    }

		/**
		 * @Route("/show/{id}", name="show_product"))
		 */
		public function show($id){
			$product = $this->getProduct($id);
			if ($product) {
				$product->setImage($this->getParameter('view_images_directory').$product->getImage());
			}

			return $this->render('product/show.html.twig', [
				'product' => $product
			]);
		}

		/**
     * @Route("/new", name="new_product")
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

				    $product->setImage($imageName);
				    $this->createProduct($product);

				    $this->addFlash(
					    'success',
					    'Produto cadastrado com sucesso!'
				    );

				    return $this->redirectToRoute('index_products');
			    } catch (FileException $e) {
				    $this->addFlash(
					    'danger',
					    'Falha no upload da imagem!'
				    );
			    }
		    }
	    }

	    return $this->render('product/form.html.twig', [
	    	'form' => $form->createView()
	    ]);
    }

		/**
		 * @Route("/edit/{id}", name="edit_product")
		 */
		public function edit(Request $request, $id){
			$product = $this->getProduct($id);
			if($product){
				$image = $product->getImage();
				$product->setImage(null);

				$form = $this->generateFormProduct($product);
				$form->handleRequest($request);
				if ($form->isSubmitted() && $form->isValid()) {
					$product = $form->getData();

					$image = new UploadedFile($form->getData()->getImage(), "");
					if ($this->validateFormProduct($product, $image, $image->guessExtension())) {
						$imageName = $this->generateUniqueImageName() . '.' . $image->guessExtension();

						try {
							$image->move(
								$this->getParameter('images_directory'),
								$imageName
							);

							$product->setImage($imageName);
							$this->updateProduct($product);

							$this->addFlash(
								'success',
								'Produto atualizado com sucesso!'
							);
							return $this->redirectToRoute('index_products');
						} catch (FileException $e) {
							$this->addFlash(
								'danger',
								'Falha no upload da imagem!'
							);
						}
					}
				}
				return $this->render('product/form.html.twig', [
					'form' => $form->createView(),
					'image' => $image
				]);
			}
		}

		/**
		 * @Route("/delete/{id}", name="delete_product")
		 */
    public function delete($id){
	    $product = $this->getProduct($id);
	    $product->setDelete_at(new \DateTime());
    	$this->updateProduct($product);
	    return $this->redirectToRoute('index_products');
    }

		// Private functions

		// Create products
		private function createProduct(Product $product) {
	    $entityManager = $this->getDoctrine()->getManager();

	    $entityManager->persist($product);
	    $entityManager->flush();
    }

    private function updateProduct($product){
	    $entityManager = $this->getDoctrine()->getManager();

	    $entityManager->persist($product);
	    $entityManager->flush();
    }


		// Functions to get product from DB
		private function getAllProducts() {
    	$produtos = $this->getDoctrine()->getRepository(Product::class)->findBy(Array('delete_at'=>null));
			return $produtos;
		}

		private function getProduct($id){
    	return $this->getDoctrine()->getRepository(Product::class)->findOneBy(Array('id'=>$id, 'delete_at'=>null));
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
					'attr' => ['class' => 'btn btn-success alygn']
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

