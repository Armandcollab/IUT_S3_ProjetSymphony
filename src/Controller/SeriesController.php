<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\User;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Episode;
use App\Entity\Series;
use App\Form\RatingFormType;
use App\Form\SearchBarFormType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\Repository\SearchRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilder;
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

    /** Calles in series() */

    public function getCategoriesForm($genres): FormBuilder
    {
        $i = 0;
        foreach ($genres as $genre) {
            $formBuilder = $this->get('form.factory')->createNamedBuilder($i, FormType::class, $genres);
            $formBuilder
                ->add('genres', EntityType::class, [
                    'class' => 'App\Entity\Genre',
                    'required' => true,
                ])
                ->add('submit', SubmitType::class, array(
                    'label' => 'Appliquer',
                ));
            $i++;
        }

        return $formBuilder;
    }

    public function getCountriesForm($countries): FormBuilder
    {
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

        return $formBuilder;
    }

    public function reload($pages, $id, $selectedgenre, $selectedcountry, $search, $note, $desc)
    {
        $nameCountry = (isset($selectedcountry) ? $selectedcountry->getName() : null);
        $nameGenre = (isset($selectedgenre) ? $selectedgenre->getName() : null);
        return $this->redirectToRoute($pages, array('id' => $id, 'selectedcountry' => $nameCountry, 'selectedgenre' => $nameGenre, 'search' => $search, 'note' => $note, 'desc' => $desc));
    }

    public function getElements(String $nameQueryBuilder, $class): array
    {
        return $this->getDoctrine()
            ->getRepository($class)
            ->createQueryBuilder($nameQueryBuilder)
            ->getQuery()
            ->execute();
    }

    public function modifieQuery($selected, $query, String $entity)
    {
        if ($selected != false) {
            $series = 'series.' . $entity;
            $name = $entity . '.name';
            $query->innerJoin($series, $entity)
                ->andwhere($name . ' LIKE :Name')
                ->setParameter('Name', $selected);
        }
    }



    public function series($query, Int $id, $pages): Response
    {
        // Get genre list
        $genres = $this->getElements("genres", Genre::class);
        // Get country list
        $countries = $this->getElements("countries", Country::class);

        // Get categories form
        $formBuilder = $this->getCategoriesForm($genres);
        $categoriesform = $formBuilder->getForm();

        // Get countries form getCountriesForm
        $formBuilder = $this->getCountriesForm($countries);
        $countriesform = $formBuilder->getForm();

        // Forms : handle request
        $searchform = $this->createForm(SearchBarFormType::class);
        $categoriesform->handleRequest(Request::createFromGlobals());
        $countriesform->handleRequest(Request::createFromGlobals());
        $searchform->handleRequest(Request::createFromGlobals());
        // Define GET values
        $selectedgenre = false;
        $selectedcountry = false;
        $search = false;
        $note = null;
        $desc = null;

        // handle form if submitted
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $search = $searchform['title']->getData();
            $note = ($searchform['note']->getData() ? true : null);
            $desc = ($searchform['decroissant']->getData() ? true : null);

            return $this->reload($pages, 0, null, null, $search, $note, $desc);
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

            return $this->reload($pages, 0, $selectedgenre, null, null, $note, $desc);
        } else if (isset($_GET['selectedgenre'])) {
            $selectedgenre = $_GET['selectedgenre'];
        }

        //Countries
        if ($countriesform->isSubmitted() && $countriesform->isValid()) {
            $selectedcountry = new Country();
            $selectedcountry = $countriesform['countries']->getData();
            return $this->reload($pages, 0, null, $selectedcountry, null, $note, $desc);
        } else if (isset($_GET['selectedcountry'])) {
            $selectedcountry = $_GET['selectedcountry'];
        }

        // Verify if GET values are set to change final query according to desired values

        if ($search != false) {
            $query->andWhere('series.title LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        $this->modifieQuery($selectedgenre, $query, "genre");
        $this->modifieQuery($selectedcountry, $query, "country");


        // Get number of elements outputed by query

        $size = count($query
            ->getQuery()
            ->execute());

        // Filters results for the layout of series (10 series per page)
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
            'searchform' => $searchform->createView(),
            'categoriesform' => $categoriesform->createView(),
            'countriesform' => $countriesform->createView(),
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
        // get ratings corresponding for this serie
        $ratings = $this->getDoctrine()
            ->getRepository(rating::class)
            ->createQueryBuilder('ratings')
            ->innerJoin('ratings.series', 'series')
            ->andwhere('series.id LIKE :serieID')
            ->setParameter('serieID', $id)
            ->getQuery()
            ->execute();

        // Try to recover already existing rating

        $userrating = true;
        if (null != $user) {
            $userrating = $this->getDoctrine()
                ->getRepository(Rating::class)
                ->createQueryBuilder('rating')
                ->innerJoin('rating.series', 'series')
                ->innerJoin('rating.user', 'user')
                ->andwhere('series.id LIKE :serieID')
                ->setParameter('serieID', $id)
                ->andwhere('user.id LIKE :userID')
                ->setParameter('userID', $user->getId())
                ->getQuery()
                ->execute();
        }
        $request = Request::createFromGlobals();


        $query = $em->createQuery("SELECT s
        FROM App:Season s
        INNER JOIN App:Series ss WITH s.series = ss.id
        WHERE s.series = $id
        ORDER BY s.number");

        $seasonss = $query->getResult();

        foreach ($seasonss as $season) {
            $seasonId = $season->getId();
            $query = $this->getDoctrine()
                ->getRepository(Episode::class)
                ->createQueryBuilder('e')
                //->select('e','u')
                ->innerJoin('e.season', 'ss')
                //->leftJoin('e.user', 'u')
                ->andwhere('e.season = ss.id')
                //->andwhere('u.id = :userID')
                //->orwhere('u.id is NULL')
                ->andwhere('ss.id = :seasonID')
                ->orderby('e.number')
                ->setParameter('seasonID', $seasonId)
                //->setParameter('userID', $user->getID())
                ->getQuery()
                ->execute();

            $seasons[$season->getnumber()] = $query;
            dump($query);
        }
        $ratingform = $this->createForm(RatingFormType::class, [
            'serie_show' => $series,
            'user_show' => $user,
        ]);
        if ($user != null && $userrating == null) {
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
            'ratingform' => $ratingform->createView(),
            'ratings' => $ratings,
            'userrating' => $userrating,
            'user' => $user
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
     * @Route("/follow/season/{id}", name="series_follow", methods={"GET","POST"})
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
     * @Route("/unfollow/season/{id}", name="series_unfollow", methods={"GET"})
     */
    public function unfollow(Series $serie, UserInterface $user): Response
    {
        $em = $this->getDoctrine()->getManager();
        $userBD = $em->getRepository(User::class)->find($user->getId());
        $userBD->removeSeries($serie);
        $em->flush();

        return $this->show($serie);
    }



    /**
     * @Route("/follow/episode/{id}", name="episode_unfollow", methods={"GET","POST"})
     */
    public function follow_episode(Episode $episode, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
        $userBD = $em->getRepository(User::class)->find($user->getId());
        $userBD->removeEpisode($episode);
        $em->flush();

        
        return $this->show($episode->getSeason()->getSeries());
    }


    /**
     * @Route("/unfollow/episode/{id}", name="episode_follow", methods={"GET"})
     */
    public function unfollow_episode(Episode $episode, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
        $userBD = $em->getRepository(User::class)->find($user->getId());
        $userBD->addEpisode($episode);
        $em->flush();
        
        return $this->show($episode->getSeason()->getSeries());
    }
}
