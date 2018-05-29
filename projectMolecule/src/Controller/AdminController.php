<?php

namespace App\Controller;

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

class AdminController extends Controller
{
    /**
     * @Route("/admin/home", name="adminHome")
     */
    public function home (UserRepository $userRepository, MoleculeRepository $moleculeRepository)
    {
        $user = $userRepository->findAll();

        $lastMolecule = $moleculeRepository->getLastMolecule();

        $molecule = $moleculeRepository->findNb();

        $tab = array(
            'user'=>$user,
            'last'=>$lastMolecule,
            'nbMolecule'=>$molecule
        );

        return $this->render('admin/adminHome.html.twig', $tab);
    }

    /**
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

            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );

            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $molecule->getPath();
            $fileName = $this->generateUniqueFileName().'.'.$file->gue

            // moves the file to the directory where brochures are stored
            $file->move(
                $this->getParameter('molecule_directory'),
                $fileName
            );

            // updates the 'brochure' property to store the PDF file name
            // instead of its contents
            $molecule->setPath($fileName);

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
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    /**
     * Lists all post entities.
     *
     * @Route("/admin/list", name="list")
     * @Method("GET")
     */
    public function indexAction(Request $request, MoleculeRepository $moleculeRepository)
    {
        $moleculeList = $moleculeRepository->findAll();

        return $this->render('admin/moleculeList.html.twig', compact('moleculeList')
        );
    }



    /**
    * Displays a form to edit an existing post entity.
    *
    * @Route("admin/list/{id}/edit", name="list_edit")
    * @Method({"GET", "POST"})
    */
    public function editAction(Request $request, Molecule $molecule)
    {
        $deleteForm = $this->deleteEntryAction($molecule);
        $editForm = $this->createForm('src\Form\MoleculeFormType', $molecule);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('list_edit', array('id' => $molecule->getId()));
        }
        return $this->render('Blog/post/edit.html.twig', array(
            'post' => $molecule,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("/delete-entry/{entryId}", name="admin_delete_entry")
     *
     * @param $entryId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteEntryAction($entryId)
    {
        $blogPost = $this->blogPostRepository->findOneById($entryId);
        $author = $this->authorRepository->findOneByUsername($this->getUser()->getUserName());

        if (!$blogPost || $author !== $blogPost->getAuthor()) {
            $this->addFlash('error', 'Unable to remove entry!');

            return $this->redirectToRoute('admin_entries');
        }

        $this->entityManager->remove($blogPost);
        $this->entityManager->flush();

        $this->addFlash('success', 'Entry was deleted!');

        return $this->redirectToRoute('admin_entries');
    }

}