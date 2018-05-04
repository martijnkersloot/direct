<?php
/**
 * Represents the relationship between words and concepts
 */
class Relationship
{
    /**
     * @var string The unique relationship ID
     */
    private $id;

    /**
     * @var string The ID of the relationship where this relationship is dependent on
     */
    private $dependentId;

    /**
     * @var string The text where the relation relates to
     */
    private $text;

    /**
     * @var int The begin position of the origin text
     */
    private $originBegin;

    /**
     * @var int The end position of the origin text
     */
    private $originEnd;

    /**
     * @var int The begin position of the destination text
     */
    private $destinationBegin;

    /**
     * @var int The end position of the destination text
     */
    private $destinationEnd;

    /**
     * @var array The concepts relating to the origin text
     */
    private $originConcepts;

    /**
     * @var array The concepts relating to the destination text
     */
    private $destinationConcepts;

    /**
     * @var array The attribute concepts that could define the relationship between the origin and destination
     */
    private $attributeConcepts;

    /**
     * @var string The relation between this relationship and its parent
     */
    private $relation;

    /**
     * @var array The relations that are dependent on this relationship
     */
    private $dependencies;

    /**
     * Create a new Relationship object
     * @param $rId string The unique relationship ID
     * @param $dId string The ID of the relationship where this relationship is dependent on
     * @param $oBegin int The begin position of the origin text
     * @param $oEnd int The end position of the origin text
     * @param $dBegin int The begin position of the destination text
     * @param $dEnd int The end position of the destination text
     * @param $rRelation string The relation between this relationship and its parent
     * @param $rText string The text where the relation relates to
     */
    public function __construct($rId, $dId, $oBegin, $oEnd, $dBegin, $dEnd, $rRelation, $rText)
    {
        $this->id = $rId;
        $this->dependentId = $dId;

        $this->originBegin = $oBegin;
        $this->originEnd = $oEnd;
        $this->destinationBegin = $dBegin;
        $this->destinationEnd = $dEnd;

        $this->relation = $rRelation;
        $this->text = $rText;

        $this->originConcepts = array();
        $this->destinationConcepts = array();
        $this->attributeConcepts = array();

        $this->dependencies = array();
    }

    /**
     * Get the unique relationship ID
     * @return string Unique relationship ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the ID of the relationship where this relationship is dependent on
     * @return string ID of the relationship where this relationship is dependent on
     */
    public function getDependentId()
    {
        return $this->dependentId;
    }

    /**
     * Add a dependent relationship to this relationship
     * @param $dependent Relationship Dependent relationship
     */
    public function addDependency($dependent)
    {
        $this->dependencies[] = $dependent;
    }

    /**
     * Get the relations that are dependent on this relationship
     * @return array Relations that are dependent on this relationship
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Get the text where the relation relates to
     * @return string Text where the relation relates to
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get the begin position of the origin text
     * @return int Begin position of the origin text
     */
    public function getOriginBegin()
    {
        return $this->originBegin;
    }

    /**
     * Get the end position of the origin text
     * @return int End position of the origin text
     */
    public function getOriginEnd()
    {
        return $this->originEnd;
    }

    /**
     * Get the length (end - begin) of the origin text
     * @return int Length of the origin text
     */
    public function getOriginLength()
    {
        return $this->originEnd - $this->originBegin;
    }

    /**
     * Get the begin position of the destination text
     * @return int Begin position of the destination text
     */
    public function getDestinationBegin()
    {
        return $this->destinationBegin;
    }

    /**
     * Get the end position of the destination text
     * @return int End position of the destination text
     */
    public function getDestinationEnd()
    {
        return $this->destinationEnd;
    }

    /**
     * Get the length (end - begin) of the destination text
     * @return int Length of the destination text
     */
    public function getDestinationLength()
    {
        return $this->destinationEnd - $this->destinationBegin;
    }

    /**
     * Get the relation between this relationship and its parent
     * @return string Relation between this relationship and its parent
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Get the concepts relating to the origin text
     * @return array Concepts relating to the origin text
     */
    public function getOriginConcepts()
    {
        return $this->originConcepts;
    }

    /**
     * Get the concepts relating to the destination text
     * @return array Concepts relating to the destination text
     */
    public function getDestinationConcepts()
    {
        return $this->destinationConcepts;
    }

    /**
     * Add a concept to the relationship
     * @param $position int Position of the text where the concept relates to
     * @param $concept Concept Concept object
     */
    public function addConcept($position, $concept)
    {
        if ($this->originBegin <= $position
            && $this->originEnd >= $position
        ) {
            $this->addOriginConcept($concept);
        }
        if ($this->destinationBegin <= $position
            && $this->destinationEnd >= $position
        ) {
            $this->addDestinationConcept($concept);
        }
    }

    /**
     * Add a concept to the origin of the relationship
     * @param $originConcept Concept Concept object
     */
    public function addOriginConcept($originConcept)
    {
        if (!in_array($originConcept, $this->originConcepts)) {
            $this->originConcepts[] = $originConcept;
        }
    }

    /**
     * Add a concept to the destination of the relationship
     * @param $destinationConcept Concept Concept object
     */
    public function addDestinationConcept($destinationConcept)
    {
        if (!in_array($destinationConcept, $this->destinationConcepts)) {
            $this->destinationConcepts[] = $destinationConcept;
        }
    }

    /**
     * Clear all concept arrays
     */
    public function clearConcepts()
    {
        $this->originConcepts = array();
        $this->destinationConcepts = array();
    }

    /**
     * Check if the relationship has concepts that relate to the origin
     * @return bool True if the relationship has concepts that relate to the origin
     */
    public function hasOriginConcepts()
    {
        return count($this->originConcepts) > 0;
    }

    /**
     * Check if the relationship has concepts that relate to the destination
     * @return bool True if the relationship has concepts that relate to the destination
     */
    public function hasDestinationConcepts()
    {
        return count($this->destinationConcepts) > 0;
    }

    /**
     * Get all SNOMED CT attributes that could define this relationship and return them in an array
     * @return array SNOMED CT attributes that could define this relationship
     */
    public function getAttributeConcepts()
    {
        global $mysqli;

        foreach ($this->originConcepts as $origin) {
            foreach ($this->destinationConcepts as $destination) {
                $originConcept = $origin->getOntologyCode();
                $destinationConcept = $destination->getOntologyCode();

                $stmt = $mysqli->prepare(
                    "SELECT d.referencedComponentId FROM transitiveclosure t1
                         JOIN attribute_domain d ON d.domainId = t1.SuperTypeId
                         JOIN attribute_range r ON r.referencedComponentId = d.referencedComponentId
                         JOIN transitiveclosure t2 ON r.attributeRuleConcept = t2.SuperTypeId
                         WHERE t1.SubTypeId = ?
                         AND t2.SubTypeId = ?"
                );

                echo $mysqli->error;
                $stmt->bind_param("ss", $destinationConcept, $originConcept);
                echo $mysqli->error;
                $stmt->execute();
                $stmt->bind_result($attribute);

                $attributes = array();

                while ($stmt->fetch()) {
                    $attributes[] = $attribute;
                }

                $stmt->close();

                foreach ($attributes as $attribute) {
                    $concept = new Concept(
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        "SNOMEDCT",
                        $attribute,
                        null,
                        0,
                        $origin->getIncludedParents()
                    );

                    $this->attributeConcepts[] = new RelationshipAttribute(
                        $origin,
                        $destination,
                        $concept
                    );
                }
            }
        }

        return $this->attributeConcepts;
    }
}