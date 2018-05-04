<?php

/**
 * Class SNOMEDSearch
 */
class SNOMEDSearch
{
    /**
     * The SNOMED CT identifier for the 'is a' relationship between two concepts
     * 116680003 |Is a (attribute)|
     */
    const IS_A = "116680003";

    /**
     * The root of the SNOMED CT hierarchy
     * 138875005 | SNOMED CT Concept (SNOMED RT+CTV3) |
     */
    const ROOT = "138875005";

    /**
     * @var array Active SNOMED CT concepts
     */
    private $active;

    /**
     * @var mysqli_stmt MySQL search statement
     */
    private $search;

    /**
     * Creates a SNOMEDSearch object and prepares the queries
     */
    public function __construct()
    {
        global $mysqli;

        $this->search = $mysqli->prepare(
            'SELECT d1.conceptid, term_nonalpha FROM description_s d1
             WHERE (
              MATCH(d1.term_nonalpha) AGAINST (? IN BOOLEAN MODE) 
             )
             AND d1.active = 1
             ORDER BY LENGTH(d1.term)'
        );

        $this->active = array();
        $stmt = $mysqli->prepare('SELECT id FROM concept_s WHERE active = 1');

        $stmt->execute();
        $stmt->bind_result($id);
        while ($stmt->fetch()) {
            $this->active[$id] = true;
        }
    }

    /**
     * Find concepts by term and parent
     * @param $term string Term to search for
     * @param $parent string Parent concept
     * @return array Found concepts
     */
    public function findConcepts($term, $parent)
    {
        global $mysqli;
        $array = array();

        $active = array();

        if ($parent) {
            $stmt = $mysqli->prepare('SELECT SubTypeId FROM `transitiveclosure` WHERE SuperTypeId = ?');

            $stmt->bind_param("s", $parent);
            $stmt->execute();
            $stmt->bind_result($id);
            while ($stmt->fetch()) {
                $active[$id] = true;
            }
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM concept_s WHERE active = 1');

            $stmt->execute();
            $stmt->bind_result($id);
            while ($stmt->fetch()) {
                $active[$id] = true;
            }
        }

        $id = $term;
        $term = '%' . $term . '%';

        $stmt = $mysqli->prepare('SELECT d1.conceptid, d1.term, d2.term as fsn FROM description_s d1
                                      JOIN description_s d2 ON d2.conceptid = d1.conceptid AND d2.typeid = 900000000000003001 AND d2.active = 1
                                      WHERE (d1.term LIKE ? OR d1.conceptid = ?) AND d1.active = 1
                                      ORDER BY LENGTH(d1.term)');

        $stmt->bind_param("ss", $term, $id);
        $stmt->execute();
        $stmt->bind_result($conceptid, $term, $fsn);

        $i = 0;
        while ($stmt->fetch()) {
            if (isset($active[$conceptid])) {
                $array[] = array(
                    'id' => $conceptid,
                    'value' => $conceptid,
                    'description' => $term,
                    'displayName' => $conceptid . '|' . $fsn . '|',
                    'fsn' => $fsn
                );

                $i++;
            }
            if ($i == 10) break;
        }

        return $array;
    }

    /**
     * Find concepts by the combination of different words
     * @param $words array Array with all the words to combine
     * @return array Found concepts with a match over 90%
     */
    public function findConceptCombinations($words)
    {
        $param = implode(" ", $words);
        $combinations = array();

        foreach ($words as $position => $word) {
            $positionArray = explode('-', $position);
            $begin = $positionArray[0];
            $wordTemp = array();

            foreach ($words as $position2 => $word2) {
                $positionArray = explode('-', $position2);
                $begin2 = $positionArray[0];

                if ($begin2 >= $begin) {
                    $length2 = mb_strlen($word2, "utf-8");
                    $wordTemp[] = $word2;

                    $combinations[$begin . '-' . ($begin2 + $length2)] = implode(" ", $wordTemp);
                }
            }
        }

        $array = array();

        $this->search->bind_param("s", $param);
        $this->search->execute();
        $this->search->bind_result($conceptid, $term);

        while ($this->search->fetch()) {
            if (isset($this->active[$conceptid])) {
                foreach ($combinations as $position => $combination) {
                    $combinationnospace = str_replace(' ', '', $combination);
                    $text1 = preg_replace("/[^A-Za-z0-9]/", '', strtolower($term));
                    $text2 = preg_replace("/[^A-Za-z0-9]/", '', strtolower($combinationnospace));

                    similar_text($text1, $text2, $percentage);
                    $percentage = $percentage / 100;

                    if ($percentage > 0.9) {
                        if (!isset($array[$position])) $array[$position] = array();
                        $array[$position][$conceptid] = true;
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Find concept by SNOMED CT identifier
     * @param $id string SNOMED CT identifier
     * @return array Array with concept information
     */
    public function findConcept($id)
    {
        global $mysqli;
        $array = array();

        $stmt = $mysqli->prepare(
            'SELECT c.id, d.term, COUNT(t.SubTypeId) as children FROM concept_s c
             JOIN description_s d ON d.conceptid = c.id AND d.typeid = 900000000000003001 AND d.active = 1 
             LEFT JOIN transitiveclosure t ON t.SuperTypeId = c.id
             WHERE c.id = ?'
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt->bind_result($id, $term, $children);
        while ($stmt->fetch()) {
            $array = array(
                'id' => $id,
                'fsn' => $term,
                'displayName' => $id . '|' . $term . '|',
                'children' => $children,
                'hasChildren' => ($children > 0),
            );
        }

        $attributeIds = array();
        $attributes = array();
        $attributesSorted = array();

        $stmt = $mysqli->prepare(
            'SELECT ad.referencedComponentId, ar.attributeRuleConcept FROM transitiveclosure t 
            JOIN attribute_domain ad ON ad.domainId = t.SuperTypeId
            JOIN attribute_range ar ON ar.referencedComponentId = ad.referencedComponentId
            WHERE t.SubTypeId = ? AND ad.active = 1'
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt->bind_result($id, $concept);

        while ($stmt->fetch()) {
            $attributeIds[$id] = $concept;
        }

        foreach ($attributeIds as $id => $accepted) {
            $concept = new Concept(null, null, null, null, null, null, null, "SNOMEDCT", $id, null, 0, array());

            $fsn = $concept->getOntologyFSN();
            $attributes[$fsn] = array(
                'id' => $id,
                'fsn' => $fsn,
                'accepted' => $accepted
            );
        }

        ksort($attributes);

        foreach ($attributes as $attribute) {
            $attributesSorted[] = $attribute;
        }

        $array['attributes'] = $attributesSorted;

        return $array;
    }

    /**
     * Find all top level SNOMED CT concepts
     * @return array Top level SNOMED CT concepts
     */
    public function findTopLevel()
    {
        global $mysqli;
        $array = array();

        $stmt = $mysqli->prepare("SELECT r.sourceid, d.term FROM snomedct.relationship_s r  
                                  JOIN description_s d ON d.conceptid = r.sourceid AND d.typeid = 900000000000003001 AND d.active = 1 
                                  WHERE r.destinationid = " . $this::ROOT . " AND r.active = 1 AND r.typeid = " . $this::IS_A . "
                                  ORDER BY d.term");
        $stmt->execute();
        $stmt->bind_result($id, $term);
        while ($stmt->fetch()) {
            $array[] = array(
                'id' => $id,
                'fsn' => $term,
                'displayName' => $id . '|' . $term . '|'
            );
        }

        return $array;
    }
}