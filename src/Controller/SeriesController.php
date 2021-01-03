<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Rating;
use App\Entity\Genre;
use App\Form\SearchBarFormType;
use App\Form\RatingFormType;
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
use Symfony\Component\Security\Core\Security;


/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{

    /**
     * inject securty service
     * @var Security
     */
    private $security;

    /**
     * @Route("/page/0", name="series_index")
     */
    public function index(): Response
    {
        return $this->page(0);
    }

    public function __construct(Security $security)
    {
        $this->security = $security;
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
            $note = null; //TODO checkbox bastien ;)
            $desc = null; //TODO checkbox
            return $this->redirectToRoute($pages, array('id' => 0, 'search' => $search, 'note' => $note, 'desc' => $desc));
        } else if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        if (isset($_GET['note'])) {
            $query->innerJoin(Rating::class, 'r', 'WITH', 'r.series = series.id');
            if (isset($_GET['desc'])) {
                $query->orderBy('r.value', 'DESC');
            } else {
                $query->orderBy('r.value', 'ASC');
            }
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

        $genres = $this->getDoctrine()
            ->getRepository(Genre::class)
            ->createQueryBuilder('genres')
            ->getQuery()
            ->execute();

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'size' => $size,
            'id' => $id,
            'page' => $pages,
            'form' => $form->createView(),
            'search' => $search,
            'genres' => $genres
        ]);
    }



    /**
     * @Route("/{id}", name="series_show", methods={"GET","POST"})
     */
    public function show(Series $series): Response
    {
        $em = $this->getDoctrine()->getManager();
        // get user from security service
        $user = $this->security->getUser();

        $id = $series->getId();
        $suivie = null;

        $request = Request::createFromGlobals();


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
        $form = $this->createForm(RatingFormType::class, [
            'serie_show' => $series,
            'user_show' => $user,
        ]);
        if ($user != null) {
            $suivie = in_array($series, $user->getSeries()->toArray());
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $value = $form['value']->getData();
                $comment = $form['comment']->getData();
                $date = $form['date']->getData();

                $rating = new Rating();
                $rating->setValue($value);
                $rating->setComment($comment);
                $rating->setDate($date);
                $rating->setUser($user);
                $rating->setSeries($series);

                $em = $this->getDoctrine()->getManager();
                $em->persist($rating);
                $em->flush();
                return $this->redirect($request->getUri());
            }
        }

        $ytbcode = substr($series->getYoutubeTrailer(), strpos($series->getYoutubeTrailer(), "=") + 1);
        $imdbcode = $series->getImdb();
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'suivie' => $suivie,
            'ytbcode' => $ytbcode,
            'imdbcode' => $imdbcode,
            'ratingform' => $form->createView()
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

        return $this->show($serie);
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

        return $this->show($serie);
    }
}
