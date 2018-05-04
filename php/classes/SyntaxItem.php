<?php
/**
 * Represents a syntax information element
 */
class SyntaxItem
{
    /**
     * @var string The unique ID of the syntax information
     */
    private $id;

    /**
     * @var string The type of semantic information (e.g. NP)
     */
    private $type;

    /**
     * @var int The begin position of the text where the semantic information relates to
     */
    private $begin;

    /**
     * @var int The end position of the text where the semantic information relates to
     */
    private $end;

    /**
     * @var string The color hex code associated with this syntax information element (for visualization)
     */
    private $color;

    /**
     * SyntaxItem constructor.
     * @param $sId string The unique ID of the syntax information
     * @param $sType string The type of syntax information
     * @param $sBegin int The begin position of the text where the syntax information relates to
     * @param $sEnd int The end position of the text where the syntax information relates to
     */
    public function __construct($sId, $sType, $sBegin, $sEnd)
    {
        $this->id = $sId;
        $this->type = $sType;
        $this->begin = $sBegin;
        $this->end = $sEnd;
    }

    /**
     * Get the unique ID of the syntax information
     * @return string Unique ID of the syntax information
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the color hex code associated with this syntax information element
     * @return string Color hex code associated with this syntax information element
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the type of syntax information (e.g. NP)
     * @return string Type of syntax information
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the begin position of the text where the syntax information relates to
     * @return int Begin position of the text where the syntax information relates to
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Get the end position of the text where the syntax information relates to
     * @return int End position of the text where the syntax information relates to
     */
    public function getEnd()
    {
        return $this->end;
    }
}
