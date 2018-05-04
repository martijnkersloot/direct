<?php
/**
 * Stores all relationships and parses the dependencies of the relationships
 */
class Relationships
{
    /**
     * The syntax relationships that define subjects
     */
    const SUBJECTS = array(
        'nsubj',
        'amod',
        'nmod',
        'advmod',
        'nn',
        'attr',
        'dobj',
        'pobj',
        'hmod'
    );

    /**
     * @var array All relationships
     */
    private $relationships;

    /**
     * Create a new Relationships object
     */
    public function __construct()
    {
        $this->relationships = array();
    }

    /**
     * Create a new relationship and add it to the array
     * @param $id string The unique relationship ID
     * @param $dependentId string The ID of the relationship where this relationship is dependent on
     * @param $oBegin int The begin position of the origin text
     * @param $oEnd int The end position of the origin text
     * @param $dBegin int The begin position of the destination text
     * @param $dEnd int The end position of the destination text
     * @param $relation string The relation between this relationship and its parent
     * @param $text string The text where the relation relates to
     */
    public function addRelationShip($id, $dependentId, $oBegin, $oEnd, $dBegin, $dEnd, $relation, $text)
    {
        $this->relationships[] = new Relationship($id, $dependentId, $oBegin, $oEnd, $dBegin, $dEnd, $relation, $text);
    }

    /**
     * Add a concept to relationships that cover the same position as the concept
     * @param $position int Position of the text where the concept relates to
     * @param $concept Concept Concept object
     */
    public function addConcept($position, $concept)
    {
        foreach ($this->relationships as $relationship) {
            $relationship->addConcept($position, $concept);
        }
    }

    /**
     * Clear all concept arrays
     */
    public function clearConcepts()
    {
        foreach ($this->relationships as $relationship) {
            $relationship->clearConcepts();
        }
    }

    /**
     * Get relationships that have origin and destination concepts and are not the root
     * @return array Relationships that have origin and destination concepts and are not the root
     */
    public function getRelationships()
    {
        $array = array();

        foreach ($this->relationships as $relationship) {
            if ($relationship->hasOriginConcepts()
                && $relationship->hasDestinationConcepts()
                && $relationship->getRelation() != "root"
            ) {
                $array[] = $relationship;
            }
        }

        return $array;
    }

    /**
     * Get all relationships
     * @return array All relationships
     */
    public function getAllRelationships()
    {
        return $this->relationships;
    }

    /**
     * Parse all relationships (add dependencies) that are defined as a subject
     */
    public function parseRelationships()
    {
        foreach ($this->relationships as $relationship) {
            if (in_array($relationship->getRelation(), $this::SUBJECTS))
            {
                $root = $this->getRelationship($relationship->getDependentId());
                $root->addDependency($relationship);
            }
        }
    }

    /**
     * Get a relationship by its ID
     * @param $id int Relationship ID
     * @return Relationship Relationship
     */
    public function getRelationship($id)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getId() == $id) return $relationship;
        }
        return null;
    }

    /**
     * Parse dependencies of a given relationship
     * @param $relationship Relationship Relationship to parse the dependencies from
     * @param $info Current information on this relationship
     * @return array Array with combined text and polarity
     */
    public function parseDependencies($relationship, $info)
    {
        if ($info == null) {
            $info = array('text' => array(), 'polarity' => 1);
        }

        $info['text'][$relationship->getOriginBegin() . '-' . $relationship->getOriginEnd()] = $relationship->getText();


        foreach ($relationship->getDependencies() as $dependency) {
            $info = $this->parseDependencies($dependency, $info);
        }

        ksort($info['text'], SORT_NUMERIC);
        return $info;
    }
}