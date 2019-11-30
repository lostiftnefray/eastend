<?php
namespace App\Repository;


use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // /**  Zwraca tablicę produktów spełniających podane parametry cech
    //  * @return array
    //  */

    public function findByProperties($conditions = [])
    {
        if (empty($conditions)) {
            return false;
        }
        //rozpoczynamy zapytanie podstawowe o produkty
        $base_query = $this->createQueryBuilder('p')->select('p');

        //jeśli w parametrach wyszukiwania mamy wykluczenia to tworzymy podzapytanie dla wykluczonych
        //podzapytanie przygotowuję jako proste zapytanie bez querybuilder ( chciałem go użyć, ale nie wiem jak tu
        //uzyckać dostęp do 'ProductProperty' w tym pliku i framework twierdzi, że nie ma takiej klasy ani tabeli product_property
        //a nie ma potrzeby podzapytania łączyć z tabelą products, bo nie interesują nas szczegóły wykluczanych produktów
        if(!empty($conditions[0])){
            $params=[];
            $conn = $this->getEntityManager()->getConnection();
            $sub_query = 'SELECT id_product FROM product_property spp WHERE';
            $sub_query_where_a = [];
        }

        $i = 0;
        foreach ($conditions AS $incl => $name_value_a) {

            foreach ($name_value_a AS $name => $value) {
                $alias = 'pp' . $i;

                //dodajemy warunki dla szukanych produktów
                if ($incl) {
                    $base_query->innerJoin('p.productProperties', $alias)
                        ->andWhere($alias . '.name = :name' . $i)
                        ->andWhere($alias . '.value = :value' . $i)
                        ->setParameter('name' . $i, $name)
                        ->setParameter('value' . $i, $value);
                }
                //dodajemy warunki dla wykluczonych produktów
                else {
                    $sub_query_where_a[] = '(spp.name = :name' . $i .' AND spp.value = :value' . $i .')';
                    $params['name'.$i] = $name;
                    $params['value'.$i] = $value;
                }
                $i++;
            }
        }

        //wykluczanie produktów z wyszukiwania
        if(!empty($sub_query)){

            //dodajemy zebrane warunki do zapytania
            $sub_query .= implode(' OR ', $sub_query_where_a);
            $db = $conn->prepare($sub_query);

            //wprowadzamy wartości parametrów
            $db->execute($params);
            $excl_product_id_a = $db->fetchAll(7);

            //dodajemy wykluczenie do zapytania podstawowego
            $base_query->andWhere('p.id NOT IN(:excl_product_id_a)')
                ->setParameter(':excl_product_id_a', $excl_product_id_a);
        }

        //zwracamy tablicę szukanych produktów
        return $base_query->getQuery()->getResult();///getArrayResult();
    }

    /**
     * usuwa wszystkie produkty i ich cechy
     */
    public function clearAllProductData(){
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = 'TRUNCATE product_property;';
            $q .= 'ALTER TABLE product_property AUTO_INCREMENT = 1;';
            $q .= 'TRUNCATE product;';
            $q .= 'ALTER TABLE product AUTO_INCREMENT = 1;';
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        }
        catch (\Exception $e) {
            $connection->rollback();
        }
    }

}
