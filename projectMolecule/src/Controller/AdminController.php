<?php

namespace App\Controller;

use App\Entity\MoleculeFile;
use App\Form\MoleculeFileType;
use App\Form\MoleculeFormType;
use App\Form\UserType;
use App\Entity\Molecule;
use App\Entity\User;
use App\Repository\MoleculeRepository;
use App\Repository\UserRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AdminController extends Controller
{
    /**
     * Display the homepage
     * @Route("/admin/home", name="adminHome")
     */
    public function home (UserRepository $userRepository, MoleculeRepository $moleculeRepository)
    {
        $user = $userRepository->findAll();

        $lastMolecule = $moleculeRepository->getLastMolecule();
        $priorityMolecule = $moleculeRepository->getPriorityMolecule();

        $tab = array(
            'user'=>$user,
            'last'=>$lastMolecule,
            'priority'=>$priorityMolecule
        );

        return $this->render('admin/adminHome.html.twig', $tab);
    }


    /**
     * Add a molecule
     * @Route("/admin/add", name="add")
     */
    public function form(Request $request)
    {
        // 1) build the form
        $molecule = new Molecule();
        $form = $this->createForm(MoleculeFormType::class, $molecule);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Confirmation message
            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );

            $molecule->getFile()->preUpload();
            // 3) save the Molecule !
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($molecule);
            $entityManager->flush();

            return $this->redirectToRoute('add');
        }

        return $this->render('admin/addMolecule.html.twig', [
            'form'=>$form->createView()
        ]);
    }

    /**
     * Lists all molecules in the database
     * @Route("/admin/list", name="list")
     * @Method("GET")
     */
    public function indexAction(MoleculeRepository $moleculeRepository)
    {
        $moleculeList = $moleculeRepository->findAll();

        return $this->render('admin/moleculeList.html.twig', compact('moleculeList')
        );
    }

    /**
     * Edit a molecule
     * @Route("/admin/edit/{id}", name="edit")
     * Method({"GET", "POST"})
     */
    public function edit(Request $request, Molecule $molecule) {
        $form = $this->createFormBuilder($molecule)
            ->add('name', TextType::class, array('attr' => array('class' => 'form-control')))
            ->add('scientificName', TextType::class, array('required' => false, 'attr' => array('class' => 'form-control')
            ))
            ->add('description', TextareaType::class, array('attr' => array('class' => 'textArea')))
            ->add('submit', SubmitType::class, array('label' => 'Update','attr' => array('class' => 'btn btn-primary mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {

            $molecule->getFile()->upload();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            return $this->redirectToRoute('list');
        }
        return $this->render('admin/editMolecule.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * Delete a molecule
     * @Route("/admin/delete/{id}", requirements={"id": "\d+"}, name="delete")
     * @Method({"GET"})
     */
    public function delete(Request $request, Molecule $molecule, MoleculeFile $file): Response
    {
        $em = $this->getDoctrine()->getManager();
        $em ->remove($file);
        $em->remove($molecule);
        $em ->flush();
        $this->addFlash('success', 'post deleted');
        return $this->redirectToRoute('list');
    }
}
