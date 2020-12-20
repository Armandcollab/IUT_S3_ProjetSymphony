<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
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
     * @Route("/page/{id}", name="series_pages", methods={"GET"})
     */
    public function page(Int $id): Response
    {
        $series = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findBy([],[],10,$id*10);

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'id' => $id
        ]);
    }

    /**
     * @Route("/{id}", name="series_show", methods={"GET"})
     */
    public function show(Series $series): Response
    {
        $em = $this->getDoctrine()->getManager();

        $id = $series->getId();

        $query = $em->createQuery("SELECT s 
        FROM App:Season s
        INNER JOIN App:Series ss WITH s.series = ss.id
        WHERE s.series = $id
        ORDER BY s.number");

        $seasons = $query->getResult();

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons
        ]);
    }

    /**
     * @Route("/poster/{id}", name="series_poster", methods={"GET"})
     */
    public function getPoster(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()),200,['content-type' => 'image/jpeg']);
    }
}
