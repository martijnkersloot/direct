<?php
/**
 * Represents a relationship between two concepts with an attribute
 */
class RelationshipAttribute
{
    /**
     * @var Concept The origin concept
     */
    private $origin;

    /**
     * @var Concept The destination concept
     */
    private $destination;

    /**
     * @var Concept The attribute concept
     */
    private $attribute;

    /**
     * Create a RelationshipAttribute object
     * @param $cOrigin Concept The origin concept
     * @param $cDestination Concept The destination concept
     * @param $cAttribute Concept The attribute concept
     */
    public function __construct($cOrigin, $cDestination, $cAttribute)
    {
        $this->origin = $cOrigin;
        $this->destination = $cDestination;
        $this->attribute = $cAttribute;
    }

    /**
     * Get the origin concept
     * @return Concept Origin concept
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Get the destination concept
     * @return Concept Destination concept
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Get the attribute concept
     * @return Concept Attribute concept
     */
    public function getAttribute()
    {
        return $this->attribute;
    }


}