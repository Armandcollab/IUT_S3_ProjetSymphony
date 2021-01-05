<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Entity\Country;
=======
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
use App\Entity\User;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Season;
use App\Entity\Series;
use App\Form\RatingFormType;
use App\Form\SearchBarFormType;
use Doctrine\ORM\EntityManager;
<<<<<<< HEAD
=======
use App\Form\CategoriesFormType;
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
use Doctrine\ORM\EntityRepository;
use App\Repository\SearchRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


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
<<<<<<< HEAD
        // Get genre list
=======

>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
        $genres = $this->getDoctrine()
            ->getRepository(Genre::class)
            ->createQueryBuilder('genres')
            ->getQuery()
            ->execute();
<<<<<<< HEAD
        // Get country list
        $countries = $this->getDoctrine()
            ->getRepository(Country::class)
            ->createQueryBuilder('countries')
            ->getQuery()
            ->execute();
        // Build form from list of genre entites
        $i = 0;
        foreach ($genres as $genre) {
=======

        $i = 0;
        foreach ($genres as $genre) {
            /* @var $genre Genre */
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
            $formBuilder = $this->get('form.factory')->createNamedBuilder($i, FormType::class, $genres);
            $formBuilder
                ->add('genres', EntityType::class, [
                    'class' => 'App\Entity\Genre',
                    'required' => true,
<<<<<<< HEAD
=======
                    'label' => $genre->getName()
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
                ])
                ->add('submit', SubmitType::class, array(
                    'label' => 'Appliquer',
                ));
            $i++;
        }
<<<<<<< HEAD
        // Get categories form
        $categoriesform = $formBuilder->getForm();
        $i = 0;
        foreach ($countries as $country) {
            $formBuilder = $this->get('form.factory')->createNamedBuilder($i, FormType::class, $countries);
            $formBuilder
                ->add('countries', EntityType::class, [
                    'class' => 'App\Entity\Country',
                    'required' => true,
                ])
                ->add('submit', SubmitType::class, array(
                    'label' => 'Appliquer',
                ));
            $i++;
        }
        // Get countries form
        $countriesform = $formBuilder->getForm();

        // Forms : handle request
        $searchform = $this->createForm(SearchBarFormType::class);
        $categoriesform->handleRequest(Request::createFromGlobals());
        $countriesform->handleRequest(Request::createFromGlobals());
        $searchform->handleRequest(Request::createFromGlobals());
        // Define GET values
=======
        $categoriesform = $formBuilder
            ->getForm();
        //
        $searchform = $this->createForm(SearchBarFormType::class);
        $categoriesform->handleRequest(Request::createFromGlobals());
        $searchform->handleRequest(Request::createFromGlobals());
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
        $selectedgenre = false;
        $search = false;
        $note = null; //TODO checkbox bastien ;)
        $desc = null; //TODO checkbox
<<<<<<< HEAD

        // handle form if submitted
=======
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $search = $searchform->getData()->getTitle();

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

        if ($categoriesform->isSubmitted() && $categoriesform->isValid()) {
            $selectedgenre = new Genre();
            $selectedgenre = $categoriesform['genres']->getData();
            return $this->redirectToRoute($pages, array('id' => 0, 'selectedgenre' => $selectedgenre->getName(), 'note' => $note, 'desc' => $desc));
        } else if (isset($_GET['selectedgenre'])) {
            $selectedgenre = $_GET['selectedgenre'];
        }
        //

        // Verify if GET values are set to change final query according to desired values

        if ($search != false) {
            $query->andWhere('series.title LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        if ($selectedgenre != false) {
            $query->innerJoin('series.genre', 'genre')
                ->andwhere('genre.name LIKE :genreName')
                ->setParameter('genreName', $selectedgenre);
        }

        // Get number of elements outputed by query

        $size = count($query
            ->getQuery()
            ->execute());

<<<<<<< HEAD
        // Filters results for the layout of series (10 series per page)
=======
        if ($categoriesform->isSubmitted() && $categoriesform->isValid()) {
            $selectedgenre = new Genre();
            $selectedgenre = $categoriesform['genres']->getData();
            return $this->redirectToRoute($pages, array('id' => 0, 'selectedgenre' => $selectedgenre->getName(), 'note' => $note, 'desc' => $desc));
        }else if (isset($_GET['selectedgenre'])) {
            $selectedgenre = $_GET['selectedgenre'];
        }

       
        if ($selectedgenre != false) {
            $query->innerJoin('series.genre', 'genre')
                ->andwhere('genre.name LIKE :genreName')
                ->setParameter('genreName', $selectedgenre );
        }

>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
        $series = $query
            ->setMaxResults(10)
            ->setFirstResult($id * 10)
            ->getQuery()
            ->execute();

<<<<<<< HEAD
=======

>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'size' => $size,
            'id' => $id,
            'page' => $pages,
            'searchform' => $searchform->createView(),
            'categoriesform' => $categoriesform->createView(),
<<<<<<< HEAD
            'countriesform' => $countriesform->createView(),
=======
>>>>>>> 635f4673bd34839c1a2bce77f4dc44b6564d485a
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
        $ratingform = $this->createForm(RatingFormType::class, [
            'serie_show' => $series,
            'user_show' => $user,
        ]);
        if ($user != null) {
            $suivie = in_array($series, $user->getSeries()->toArray());
            $ratingform->handleRequest($request);
            if ($ratingform->isSubmitted() && $ratingform->isValid()) {
                $value = $ratingform['value']->getData();
                $comment = $ratingform['comment']->getData();
                $date = $ratingform['date']->getData();

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
            'ratingform' => $ratingform->createView()
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
