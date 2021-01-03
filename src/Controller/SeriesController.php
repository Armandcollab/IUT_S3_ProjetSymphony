<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Form\SearchBarFormType;
use Doctrine\Persistence\ObjectManager;
use App\Repository\SearchRepository;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{

    /**
     * @Route("/page/0", name="series_index")
     */
    public function index(): Response
    {
        return $this->page(0);
    }

    /**
     * @Route("/page/{id}", name="series_pages", methods={"GET"})
     */
    public function page(Int $id): Response
    {
        $series = $this->getDoctrine()
            ->getRepository(Series::class)->createQueryBuilder('series');

        return $this->series($series, $id, 'series_pages');
    }

    /**
     * @Route("/followed/page/0", name="series_followed")
     */
    public function index_followed(UserInterface $user): Response
    {
        return $this->pageFollow(0, $user);
    }
    /**
     * @Route("/followed/page/{id}", name="series_page_followed", methods={"GET"})
     */
    public function pageFollow(Int $id, UserInterface $user): Response
    {
        $userID = $user->getId();

        $em = $this->getDoctrine()->getManager();

        $query = $this->getDoctrine()
            ->getRepository(Series::class)
            ->createQueryBuilder('series')
            ->leftJoin('series.user', 'user')
            ->where('user.id = :userId')
            ->setParameters(array(':userId' => $userID));

        return $this->series($query, $id, 'series_page_followed');
    }

    public function series($query, Int $id, $pages): Response
    {
        $form = $this->createForm(SearchBarFormType::class);
        $form->handleRequest(Request::createFromGlobals());
        $search = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData()->getTitle();
            return $this->redirectToRoute($pages, array('id' => 0,'search' => $search));
        } else if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        if ($search != false) {
            $query->andWhere('series.title LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        $size = count($query
            ->getQuery()
            ->execute());

        $series = $query
            ->setMaxResults(10)
            ->setFirstResult($id * 10)
            ->getQuery()
            ->execute();

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'size' => $size,
            'id' => $id,
            'page' => $pages,
            'form' => $form->createView(),
            'search' => $search
        ]);
    }

    /**
     * @Route("/{id}", name="series_show", methods={"GET"})
     */
    public function show(Series $series, UserInterface $user): Response
    {
        $em = $this->getDoctrine()->getManager();

        $id = $series->getId();

        $query = $em->createQuery("SELECT s
        FROM App:Season s
        INNER JOIN App:Series ss WITH s.series = ss.id
        WHERE s.series = $id
        ORDER BY s.number");

        $seasonss = $query->getResult();

        foreach ($seasonss as $season) {
            $seasonId = $season->getId();
            $query = $em->createQuery("SELECT e.title
            FROM App:Episode e
            INNER JOIN App:Season ss WITH e.season = ss.id
            WHERE ss.id = $seasonId
            ORDER BY e.number");

            $seasons[$season->getnumber()] = $query->getResult();
        }



        $suivie = in_array($series, $user->getSeries()->toArray());


        $ytbcode = substr($series->getYoutubeTrailer(), strpos($series->getYoutubeTrailer(), "=") + 1);
        $imdbcode = $series->getImdb();
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'suivie' => $suivie,
            'ytbcode' => $ytbcode,
            'imdbcode' => $imdbcode
        ]);
    }

    /**
     * @Route("/poster/{id}", name="series_poster", methods={"GET"})
     */
    public function getPoster(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()), 200, ['content-type' => 'image/jpeg']);
    }

    /**
     * @Route("/follow/{id}", name="series_follow", methods={"GET"})
     */
    public function follow(Series $serie, UserInterface $user): Response
    {
        $em = $this->getDoctrine()->getManager();
        $userBD = $em->getRepository(User::class)->find($user->getId());
        $userBD->addSeries($serie);
        $em->flush();

        return $this->show($serie, $user);
    }


    /**
     * @Route("/unfollow/{id}", name="series_unfollow", methods={"GET"})
     */
    public function unfollow(Series $serie, UserInterface $user): Response
    {
        $em = $this->getDoctrine()->getManager();
        $userBD = $em->getRepository(User::class)->find($user->getId());
        $userBD->removeSeries($serie);
        $em->flush();

        return $this->show($serie, $user);
    }
}
