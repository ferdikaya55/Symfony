<?php

namespace App\Controller;


use App\Entity\Admin\Messages;
use App\Entity\User;
use App\Form\Admin\MessagesType;
use App\Form\User1Type;
use App\Form\UserType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\ProductRepository;
use App\Repository\Admin\SettingRepository;

use App\Repository\Admin\ImageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository, CategoryRepository $categoryRepository)
    {
        $data= $settingRepository->findAll();

        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE indirim = '50'  ORDER BY ID ASC LIMIT 6";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $headersliders=$statement->fetchAll();
      // dump($headersliders[0]);
       // die();
        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE yenisezon='True' ORDER BY ID DESC LIMIT 4";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $sliders=$statement->fetchAll();

        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE yenisezon='False' ORDER BY ID DESC LIMIT 4";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $sliders1=$statement->fetchAll();

        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE yenisezon='True' ORDER BY ID ASC LIMIT 4";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $sliders2=$statement->fetchAll();


        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE indirim = '40'  ORDER BY ID DESC LIMIT 4";
        $statement=$em->getConnection()->prepare($sql);
        $statement->execute();
        $sliders3=$statement->fetchAll();
        //dump($sliders);
        //die();

        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM product WHERE status='False' ORDER BY ID DESC LIMIT 6";
        $statement=$em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid',$parent);
        $statement->execute();
        $contents=$statement->fetchAll();

        $cats= $this->categorytree();
       //print_r($cats);
       // die();
        $cats[0]= '<ul id="menu-v">';

        return $this->render('home/index.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'headersliders' => $headersliders,
            'sliders' => $sliders,
            'sliders1' => $sliders1,
            'sliders2' => $sliders2,
            'sliders3' => $sliders3,
            'contents' => $contents,

        ]);
    }

    public function categoryTree($parent=0,$user_tree_array=''){
        if(!is_array($user_tree_array)){
            $user_tree_array=array();
        }
        $em=$this->getDoctrine()->getManager();
        $sql="SELECT * FROM category WHERE status='True' AND parentid=".$parent;
        $statement=$em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid',$parent);
        $statement->execute();
        $result=$statement->fetchAll();
         //dump($result);
        //die();
        if(count($result) > 0){
            $user_tree_array[]="<ul>";
            foreach ($result as $row){
                $user_tree_array[]="<li><a href='/category/".$row['id']."'>".$row['title']."</a>";
                $user_tree_array=$this->categoryTree($row['id'],$user_tree_array);
            }
            $user_tree_array[]="</li></ul>";
        }
        return $user_tree_array;
    }
    /**
     * @Route("/category/{catid}", name="category_products", methods="GET")
     */
    public function CategoryProducts($catid,CategoryRepository $categoryRepository)
    {
        $cats= $this->categorytree();
        $cats[0]= '<ul id="menu-v">';
        $data = $categoryRepository->findBy(
            ['id' => $catid]
        );
       // dump($data);
        //die();
        $em=$this->getDoctrine()->getManager();
        $sql= 'SELECT * FROM product WHERE status="True" AND category_id = :catid'; // güvenlik açığı olmasın diye parametreli sql cümlesi
        $statement=$em->getConnection()->prepare($sql);
        $statement->bindValue('catid', $catid); // parametre
        $statement->execute();
        $products = $statement->fetchAll();
       // dump($result);
        //die();


        return $this->render('home/products.html.twig', [
            'data' => $data,
            'products' => $products,
            'cats' => $cats,
            ]);
    }

    /**
     * @Route("/product/{id}", name="product_detail", methods="GET")
     */
    public function ProductDetail($id,ProductRepository $productRepository, ImageRepository $imageRepository)
    {

        $data = $productRepository->findBy(
            ['id' => $id]
        );
        // dump($data);
       // die();
        $images = $imageRepository->findBy(
            ['product_id' => $id]
        );
        $cats= $this->categoryTree();
        $cats[0]= '<ul id="menu-v">'; //Changing first line for menu css

        return $this->render('home/product_detail.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'images' => $images,

        ]);
    }


    /**
     * @Route("/hakkimizda", name="hakkimizda")
     */
    public function hakkimizda(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();

        return $this->render('home/hakkimizda.html.twig', [
            'data' => $data,

        ]);
    }

    /**
     * @Route("/referans", name="referans")
     */
    public function referans(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();

        return $this->render('home/referans.html.twig', [
            'data' => $data,

        ]);
    }

    /**
     * @Route("/iletisim", name="iletisim",methods="GET|POST")
     */
    public function iletisim(SettingRepository $settingRepository,Request $request)
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');
        if ($form->isSubmitted()) {

            if ($this->isCsrfTokenValid('form-message', $submittedToken)){
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();
                $this->addFlash('success','Mesajınız Başarıyla Gönderilmiştir');
                return $this->redirectToRoute('iletisim');
            }

        }

        $data = $settingRepository->findAll();

        return $this->render('home/iletisim.html.twig', [
            'data' => $data,
            'form' => $form->createView(),
            'message' => $message,

        ]);
    }


    /**
     * @Route("/newuser", name="new_user", methods="GET|POST")
     */
    public function newuser(Request $request,UserRepository $userRepository):Response
    {
        $user = new User();
        $form=$this->createForm(UserType::class,$user);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');

            if ($this->isCsrfTokenValid('user-form', $submittedToken)) {

                if ($form->isSubmitted()) {
                    $emaildata = $userRepository->findBy(
                        ['email' => $user->getEmail()]
                    );
                    if($emaildata==null){
                        $em = $this->getDoctrine()->getManager();
                        $user->setRoles("ROLE_USER");
                        $em->persist($user);
                        $em->flush();
                        $this->addFlash('success','Üye Kaydınız Başarıyla Gerçekleşmiştir. Giriş Yapabilirsiniz');
                        return $this->redirectToRoute('app_login');
                    }
                    else{
                        $this->addFlash('error',$user->getEmail()."Email Adresi Kullanılıyor.");
                    }


            }
        }
        return $this->render('home/newuser.html.twig',[
            'user' => $user,
            'form' => $form->createView(),

        ]);
    }

}
