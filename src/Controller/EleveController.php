<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;
use App\Entity\Eleve;
use App\Repository\EleveRepository;
use App\Validator\EleveRequirementsNomOrPrenom as EleveRequirementsNomOrPrenom;
use App\Entity\Note;
use DateTime;

class EleveController extends AbstractController
{
    /**
     * @Route("/eleve", name="eleve")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/EleveController.php',
        ]);
    }

   /**
     * @Route("/eleve/new", name="createEleve")
     */
    public function new(Request $request, ValidatorInterface $validator, LoggerInterface $logger): Response
    {

        $data = json_decode($request->getContent(), true);

        $eleveRequirements = new EleveRequirementsNomOrPrenom();

        // use the validator to validate the value
        $errorsNom = $validator->validate(
            isset($data["nom"])?$data["nom"]:null,
            $eleveRequirements
        );
        $errorsPrenom =$validator->validate(
            isset($data["prenom"])?$data["prenom"]:null,
            $eleveRequirements
        );
        
        $errorsString = null;
        if (count($errorsNom)> 0) {
            $logger->error("validation nom echouee");
            $errorsString = (string) $errorsNom    ;
        }

        if (count($errorsPrenom)> 0) {
            $logger->error("validation prenom echouee");
            $errorsString = $errorsString . (string) $errorsPrenom    ;
        }

        if (DateTime::createFromFormat('j-m-Y', isset($data["dateNaissance"])?$data["dateNaissance"]:null) == false) {
            $logger->error("validation date echouee");
            $errorsString = $errorsString . "la date de naissance est invalide";
        }

        if (isset($errorsString)) {
            return new Response($errorsString);
        }

        $eleve = new Eleve();

        $eleve->setNom($data["nom"]);
        $eleve->setPrenom($data["prenom"]);
        $date = DateTime::createFromFormat('j-m-Y', $data["dateNaissance"]);
        $eleve->setDateNaissance($date);

        $errors = $validator->validate($eleve);

        $em = $this->getDoctrine()->getManager();
        $em->persist($eleve);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'message' => 'Creation nouvel eleve',
            'idCreated' => $eleve->getId(),
            'nom' => $data["nom"],
            'prenom' => $data["prenom"],
            'dateNaissance' => $data["dateNaissance"],
        ]);
    }
 
   /**
     * @Route("/eleve/delete", name="deleteEleve")
     */
    public function delete(Request $request): Response
    {

        $data = json_decode($request->getContent(), true);
        $idEleve = $data["id"];

        $repository = $this->getDoctrine()->getRepository(Eleve::class);
        $eleve = $repository->find($idEleve);

        $idEleve = $eleve->getId();

        $em = $this->getDoctrine()->getManager();
        $em->remove($eleve);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'message' => 'supression eleve',
            'idDeleted' => $idEleve,
        ]);
    }

   /**
     * @Route("/eleve/update", name="updateEleve")
     */
    public function update(Request $request, LoggerInterface $logger): Response
    {

        $data = json_decode($request->getContent(), true);
        $idEleve = $data["id"];
        $nom = $data["nom"];

        $repository = $this->getDoctrine()->getRepository(Eleve::class);

        try {
            $eleve = $repository->find($idEleve);
            if ($eleve == null) {
                return $this->json([
                    'status' => 'not_found',
                    'message' => 'failed to find existing customer',
                    'id' => $idEleve
                ]);
            }
        } catch (Exception $e) {
            $logger.error($e);
            return $this->json([
                'status' => 'error',
                'message' => 'failed to find existing customer',
                'exception' => $e->getMessage(),
                'id' => $idEleve
            ]);
        }

        $eleve->setNom($data["nom"]);
        $eleve->setPrenom($data["prenom"]);
        $date = DateTime::createFromFormat('j-m-Y', $data["dateNaissance"]);
        $eleve->setDateNaissance($date);

        $em = $this->getDoctrine()->getManager();
        $em->persist($eleve);
        $em->flush();


        return $this->json([
            'status' => 'ok',
            'message' => 'update nouvel eleve : fait',
            'id' => $eleve->getId(),
            'nom' => $data["nom"],
            'prenom' => $data["prenom"],
            'dateNaissance' => $data["dateNaissance"],
        ]);
    }


   /**
     * @Route("/eleve/addNote", name="addNote")
     */
    public function addNote(Request $request, LoggerInterface $logger): Response
    {

        $data = json_decode($request->getContent(), true);
        $idEleve = $data["id"];
        $valeur = $data["valeur"];
        $matiere = $data["matiere"];

        $logger->error("idEleve =" . $idEleve);
        $logger->error("valeur =" . $valeur);
        $logger->error("matiere =" . $matiere);

        $repository = $this->getDoctrine()->getRepository(Eleve::class);

        try {
            $eleve = $repository->find($idEleve);
            if ($eleve == null) {
                return $this->json([
                    'status' => 'not_found',
                    'message' => 'failed to find existing customer',
                    'id' => $idEleve
                ]);
            }
        } catch (Exception $e) {
            $logger.error($e);
            return $this->json([
                'status' => 'error',
                'message' => 'failed to find existing customer',
                'exception' => $e->getMessage(),
                'id' => $idEleve
            ]);
        }


        $note = new Note();

        $note->setValeur($valeur);
        $note->setMatiere($matiere);
        $note->setIdEleve($idEleve);

        $em = $this->getDoctrine()->getManager();
        $em->persist($note);
        $em->flush();


        return $this->json([
            'status' => 'ok',
            'message' => 'creation nouvelle note : fait',
            'idEleve' => $eleve->getId(),
            'idNote' => $note->getId(),
        ]);
    }


    /**
     * @Route("/eleve/moyenneEleve", name="moyenneEleve")
     */
    public function moyenneEleve(Request $request, LoggerInterface $logger): Response
    {

        $data = json_decode($request->getContent(), true);
        $idEleve = $data["id"];

        $logger->error("idEleve =" . $idEleve);

        $repository = $this->getDoctrine()->getRepository(Eleve::class);

        try {
            $eleve = $repository->find($idEleve);
            if ($eleve == null) {
                return $this->json([
                    'status' => 'not_found',
                    'message' => 'failed to find existing customer',
                    'id' => $idEleve
                ]);
            }
        } catch (Exception $e) {
            $logger.error($e);
            return $this->json([
                'status' => 'error',
                'message' => 'failed to find existing customer',
                'exception' => $e->getMessage(),
                'id' => $idEleve
            ]);
        }

        $noteRepository = $this->getDoctrine()->getRepository(Note::class);

        $moyenne = $noteRepository->moyenneEleve($idEleve);

        return $this->json([
            'status' => 'ok',
            'message' => 'moyenne eleve : fait',
            'idEleve' => $eleve->getId(),
            'moyenne' => $moyenne,
        ]);
    }

    /**
     * @Route("/eleve/moyenneGenerale", name="moyenneGenerale")
     */
    public function moyenneGenerale(Request $request, LoggerInterface $logger): Response
    {
        $noteRepository = $this->getDoctrine()->getRepository(Note::class);
        $moyenne = $noteRepository->moyenneGenerale();

        return $this->json([
            'status' => 'ok',
            'message' => 'moyenne generale : fait',
            'moyenneGenerale' => $moyenne,
        ]);
    }



    /**
     * @Route("/eleve/list", name="listEleves")
     */
    public function list(Request $request, SerializerInterface $serializer): Response
    {
        $repository = $this->getDoctrine()->getRepository(Eleve::class);
        $eleves = $repository->findAll();
        return $this->json($eleves);
    }
 
}
