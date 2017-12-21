<?php


namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\SoftMain;
use Symfony\Component\Yaml\Yaml;
use AppBundle\Repository\SoftMainRepository;
use AppBundle\Entity\SoftInfo;
use AppBundle\Entity\SoftSupport;
use AppBundle\Entity\Tag;




class AwesomeSearch
{



    private $em;

    private $searchYml;

    private $resultFinal;


    const BOOLPOINT = 1;


    /**
     * AwesomeSearch constructor.
     * @param $em
     */
    public function __construct(EntityManagerInterface $em, $rootDir)
    {
        $this->em = $em;
        $this->resultFinal = array();
        $this->searchYml = Yaml::parse(file_get_contents($rootDir . "/config/awesomeSearch.yml"));

    }


    public function search($query)
    {

        // Here, we get a query, return an array with results sorts

        $words = $this->cleanQuery($query);
        $finalResult = [];


        // foreach words, look if it's in title, or drescription, or bool

        foreach ($words as $word) {


            $softmainNameResults = $this->em->getRepository(SoftMain::class)->searchInSoftmainName($word);
            $softmainDescriptionResults = $this->em->getRepository(SoftMain::class)->searchInSoftmainDescription($word);
            $commentResults = $this->em->getRepository(SoftMain::class)->searchInComment($word);
            $advantagesResults = $this->em->getRepository(SoftMain::class)->searchInAdvantages($word);
            $drawbacksResults = $this->em->getRepository(SoftMain::class)->searchInDrawbacks($word);
            $typeResults = $this->em->getRepository(SoftMain::class)->searchInType($word);
            $customersResults = $this->em->getRepository(SoftInfo::class)->searchInCustomers($word);
            $hostingCountryResults = $this->em->getRepository(SoftInfo::class)->searchInHostingCountry($word);
            $creationDateResults = $this->em->getRepository(SoftInfo::class)->searchInCreationDate($word);
            $webSiteResults = $this->em->getRepository(SoftInfo::class)->searchInWebSite($word);
            $knowledgeBaseLanguageResults = $this->em->getRepository(SoftSupport::class)->searchInKnowledgeBaseLanguage($word);
            $tagNameResults = $this->em->getRepository(Tag::class)->searchInTagName($word);
            $tagDescriptionResults = $this->em->getRepository(Tag::class)->searchInTagDescription($word);


            $boolResults = $this->searchInYml($word);
        }
        return $finalResult;
    }

    private function cleanQuery($query)
    {

        // Receive  a dirty query, give a clean array of words to explore

        $arrayOfWords = preg_split("/[\s,+\"'&%().]+/", $query);
        $goodQuery = [];
        $emptyWords = $this->getSearchYml()["EmptyWords"];

            foreach ($arrayOfWords as $word) {
                $isDirtyOrNot = in_array($word, $emptyWords);
                if ($isDirtyOrNot === false AND strlen($word)) {
                    $goodQuery[] .= $word;
                }
            }

        return $goodQuery;
    }
    public function searchInYml($word)
    {
        $resultTable = [];
        $j = 0;
        $entityKeys = array_keys($this->getSearchYml()['Booleans']);
        foreach ($this->getSearchYml()['Booleans'] as $table) {
            $i=0;
            $booleanKeys = array_keys($table);
            foreach ($table as $synonym) {
                if (stristr($synonym, $word) != FALSE) {
                    $resultTable = $this->em->getRepository(SoftMain::class)->getSoftByAnyBool($booleanKeys[$i], $entityKeys[$j]);
                    //Version finale: ajouter cette methode pour ajouter chaque resultat à la proprieté finale
                    //$this->addToFinalResult($resultTable);
                }
                $i++;
            }
            $j++;
        }
        //Cette function DOIT return uniquement true, mais elle ajoute à final result tous les tableaux trouvés par la query. À LA VERSION FINAL: remplacer par return true
        return $resultTable;
    }

// cette fonction prend en argument un array et parcourt le resultFinal, lajoute chaque ligne de l'array si elle n'existe pas ou alors ajoute à l'id deja existant
    public function addToFinalResult($array)
    {
        foreach ($array as $result) {
            $i = 0;
            foreach ($this->resultFinal as $resultFinalLign) {
                if ($result['soft'] === $resultFinalLign['soft']) {
                    $this->resultFinal[$i]['points'] += $result['points'];
                    $result['points'] = 0;
                }
                $i++;
            }
            if($result['points'] != 0){
                array_push($this->resultFinal,$result);
            }
        }
        return true;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEm()
    {
        return $this->em;
    }
    /**
     * @return mixed
     */
    public function getSearchYml()
    {
        return $this->searchYml;
    }
    /**
     * @return array
     */
    public function getResultFinal()
    {
        return $this->resultFinal;
    }
}