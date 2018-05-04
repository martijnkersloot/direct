<?php
/**
 * Represents a semantic information element
 */
class SemanticItem
{
    /**
     * @var string The unique ID of the semantic information
     */
    private $id;

    /**
     * @var string The type of semantic information (e.g. DiseaseDisorderMention)
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
     * @var array The concepts that relate to this semantic information element
     */
    private $umlsConcepts;

    /**
     * @var int The negation identifier of the concept
     * -1: negative, 0: neutral, 1: positive
     */
    private $polarity;

    /**
     * @var string The color hex code associated with this semantic information element (for visualization)
     */
    private $color;

    /**
     * @var string The subject where the concept relates to
     * e.g. patient
     */
    private $subject;

    /**
     * @var int The history identifier of the concept
     * 1: concept relates to subject's history, 0: concept does not relate to subject's history
     */
    private $historyOf;

    /**
     * Creates a SemanticItem object
     * @param $sId string The unique ID of the semantic information
     * @param $sType string The type of semantic information
     * @param $sBegin int The begin position of the text where the semantic information relates to
     * @param $sEnd int The end position of the text where the semantic information relates to
     * @param $sUmlsConcepts array The concepts that relate to this semantic information element
     * @param $sPolarity int The negation identifier of the concept
     * @param $sSubject string The subject where the concept relates to
     * @param $sHistoryOf int The history identifier of the concept
     */
    public function __construct($sId, $sType, $sBegin, $sEnd, $sUmlsConcepts, $sPolarity, $sSubject, $sHistoryOf)
    {
        $this->id = $sId;
        $this->type = $sType;
        $this->begin = $sBegin;
        $this->end = $sEnd;
        $this->umlsConcepts = $sUmlsConcepts;
        $this->polarity = $sPolarity;
        $this->subject = $sSubject;
        $this->historyOf = $sHistoryOf;
    }

    /**
     * Get the unique ID of the semantic information
     * @return string Unique ID of the semantic information
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the color hex code associated with this semantic information element
     * @return string Color hex code associated with this semantic information element
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the type of semantic information (e.g. DiseaseDisorderMention)
     * @return string Type of semantic information
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the negation identifier of the concept
     * @return int Negation identifier of the concept
     */
    public function getPolarity()
    {
        return $this->polarity;
    }

    /**
     * Get the subject where the concept relates to
     * @return string Subject where the concept relates to
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get the history identifier of the concept
     * @return int The history identifier of the concept
     */
    public function getHistoryOf()
    {
        return $this->historyOf;
    }

    /**
     * Get the concepts that relate to this semantic information element
     * @return array Array with concepts that relate to this semantic information element
     */
    public function getUmlsConcepts()
    {
        return $this->umlsConcepts;
    }

    /**
     * Get the begin position of the text where the semantic information relates to
     * @return int Begin position of the text where the semantic information relates to
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Get the end position of the text where the semantic information relates to
     * @return int End position of the text where the semantic information relates to
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the length (end - begin) of the text where the semantic information relates to
     * @return int Length of the text where the concept relates to
     */
    public function getLength()
    {
        return $this->end - $this->begin;
    }

    /**
     * Check if there are concepts that relate to the semantic information
     * @return bool True if there are concepts that relate to the semantic information
     */
    public function hasConcepts()
    {
        return count($this->getActiveUmlsConcepts()) > 0;
    }

    /**
     * Get the active concepts that relate to the semantic information
     * @return array Array with active concepts that relate to the semantic information
     */
    public function getActiveUmlsConcepts()
    {
        $concepts = array();
        foreach ($this->umlsConcepts as $concept) {
            if ($concept->isActive()) {
                $concepts[] = $concept->toArray();
            }
        }
        return $concepts;
    }
}
