<?php

// src/Controller/RunController.php
namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductProperty;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;


class RunController extends AbstractController
{
    /**
     * @Route("/run/find")
     */
    public function find()
    {
        //typ = trampki i kolor = niebieski i materiał != skóra
        $repository = $this->getDoctrine()->getRepository(Product::class);

        //indeks "true" oznacza, że wynik ma pasować do parametrów, "false" - nie może pasować
        $conditions = [
            true => ['typ' => 'trampki', 'kolor' => 'niebieski'],
            false => ['materiał' => 'skóra']
        ];
        $products   = $repository->findByProperties($conditions);

        return $this->render('run/find.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/run/migrate")
     */
    public function migrate(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate'
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();

        $repository = $this->getDoctrine()->getRepository(Product::class);
        //usuwamy dane
        $repository->clearAllProductData();

        $txt = '
         Buty Puma
        kolor: niebieski
        typ: trampki
        materiał: skóra

         Trampki Converse
        kolor: niebieski
        typ: trampki
        materiał: płótno

         Trampki Vans
        kolor: niebieski
        typ: trampki
        materiał: płótno

         Trampki Fila
        kolor: czerwony
        typ: sukienka
        materiał: bawełna';

        $em = $this->getDoctrine()->getManager();

        $txt_array = explode("\n", $txt);
        foreach ($txt_array AS $line) {

            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            //jeśli linia nie zawiera ":" traktujemy ją jako produkt
            if (strpos($line, ':') === false) {
                $product = new Product();
                $product->setName($line);
                $em->persist($product);
                $em->flush();
                echo '<hr/>Dodano produkt: ' . $line . '<br/>';

                //linia zawiera ":" więc dodajemy ją jako cechę do ostatniego produktu ( tylko jeśli istnieje )
            } elseif (isset($product)) {
                $prop_a   = explode(':', $line);
                $property = new ProductProperty();
                $property->setName(trim($prop_a[0]));
                $property->setValue(trim($prop_a[1]));

                $product->addProductProperty($property);
                $em->persist($product);
                $em->persist($property);
                $em->flush();
                echo ' -dodano cechę: ' . $line . '<br/>';
            }
        }
        echo '<hr/>';
        return new Response($content);
    }


}
