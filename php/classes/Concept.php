<?php
/**
 * Represents a UMLS or SNOMED CT concept
 */
class Concept
{
    /**
     * @var string The unique concept ID
     */
    private $id;

    /**
     * @var string The type of the concept
     * e.g. DiseaseDisorderMention
     */
    private $type;

    /**
     * @var int The begin position of the text where the concept relates to
     */
    private $begin;

    /**
     * @var int The end position of the text where the concept relates to
     */
    private $end;

    /**
     * @var string The text where the concept relates to
     */
    private $text;

    /**
     * @var int The negation identifier of the concept
     * -1: negative, 0: neutral, 1: positive
     */
    private $polarity;

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
     * @var string The ontology system where the concept is from
     * e.g. SNOMED
     */
    private $ontologySystem;

    /**
     * @var string The ontology code (identifier) of the concept
     */
    private $ontologyCode;

    /**
     * @var string The UMLS identifier of the concept
     */
    private $cui;

    /**
     * @var array The active descriptions of the concept in the ontology
     */
    private $activeOntologyDescriptions;

    /**
     * @var array The inactive descriptions of the concept in the ontology
     */
    private $inactiveOntologyDescriptions;

    /**
     * @var string The fully specified name of the concept in the ontology
     */
    private $ontologyFSN;

    /**
     * @var bool The status of the concept in the ontology
     * true: concept is active
     */
    private $active;

    /**
     * @var bool The exclusion status of the concept based on the excluded parents from Parser
     * true: concept is excluded
     */
    private $excluded;

    /**
     * @var bool The inclusion status of the concept based on the included parents array
     * true: concept is included
     */
    private $included;

    /**
     * @var array The parents of the concept
     */
    private $parents;

    /**
     * @var bool The overlap status of the concept based on the position in the text and other concepts in that position
     * true: concept has overlap with another concept
     */
    private $overlap;

    /**
     * @var array The substitute concepts for the concept, found using the UMLS MRCONSO table or the SNOMED associationrefset_s table
     */
    private $substitutes;

    /**
     * @var int The score defining how the concept was found
     * 0: cTAKES, 1: cTAKES (full text match), 3: Post-processing algorithms
     */
    private $score;

    /**
     * @var float The percentage of similarity between the text where the concept relates to and the concept description
     */
    private $scoreMatch;

    /**
     * @var array The parent concepts that are included
     */
    private $includedParents;

    /**
     * Create a new Concept object and fetch the information of the concept
     * @param $sType string The type of the concept
     * @param $sBegin int The begin position of the text where the concept relates to
     * @param $sEnd int The end position of the text where the concept relates to
     * @param $sText string The text where the concept relates to
     * @param $sPolarity int The negation identifier of the concept
     * @param $sSubject string The subject where the concept relates to
     * @param $sHistoryOf int The history identifier of the concept
     * @param $oSystem string The ontology system where the concept is from
     * @param $oCode string The ontology code (identifier) of the concept
     * @param $oCui string The UMLS identifier of the concept
     * @param $sScore int The score defining how the concept was found
     * @param $sInclude array The parent concepts that are included
     */
    public function __construct($sType, $sBegin, $sEnd, $sText, $sPolarity, $sSubject, $sHistoryOf, $oSystem, $oCode, $oCui, $sScore, $sInclude)
    {
        $this->id = uniqid('c', true);

        $this->type = $sType;
        $this->begin = $sBegin;
        $this->end = $sEnd;
        $this->text = $sText;
        $this->polarity = $sPolarity;
        $this->subject = $sSubject;
        $this->historyOf = $sHistoryOf;
        $this->ontologySystem = $oSystem;
        $this->ontologyCode = $oCode;
        $this->cui = $oCui;
        $this->score = $sScore;
        $this->includedParents = $sInclude;

        $this->overlap = false;
        $this->excluded = false;
        $this->included = false;
        $this->active = false;

        $this->substitutes = array();
        $this->parents = array();
        $this->activeOntologyDescriptions = array();
        $this->inactiveOntologyDescriptions = array();

        // Custom dictionaries
        if ($this->ontologySystem == "cancer") $this->UMLSparse();

        // SNOMED CT dictionary
        if ($this->ontologySystem == "SNOMEDCT") $this->parse();
    }

    /**
     * Fetch all SNOMED CT concepts where the UMLS CUI relates to and add them as substitutes
     */
    private function UMLSparse()
    {
        global $mysqli;
        $code = $this->cui;

        $stmt = $mysqli->prepare("SELECT SCUI FROM umls.MRCONSO WHERE CUI = ? AND SAB = 'SNOMEDCT_US' GROUP BY SCUI");
        echo $mysqli->error;
        $stmt->bind_param("s", $code);
        echo $mysqli->error;
        $stmt->execute();
        $stmt->bind_result($scui);

        $substitutes = array();

        while ($stmt->fetch()) {
            $substitutes[] = $scui;
        }

        $stmt->close();

        foreach ($substitutes as $substitute) {
            $this->substitutes[] = new Concept(
                $this->type,
                $this->begin,
                $this->end,
                $this->text,
                $this->polarity,
                $this->subject,
                $this->historyOf,
                "SNOMEDCT",
                $substitute,
                $this->cui,
                0,
                $this->includedParents
            );
        }
    }

    /**
     * Fetch all information from the SNOMED CT concept
     */
    private function parse()
    {
        global $mysqli;
        $code = $this->ontologyCode;

        // Check if the concept is active
        $stmt = $mysqli->prepare("SELECT active FROM snomedct.concept_s WHERE id = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->bind_result($active);

        while ($stmt->fetch()) {
            $this->active = ($active == "1");
        }

        $stmt->close();

        // Get parents of the concept
        $stmt = $mysqli->prepare("SELECT SuperTypeId FROM snomedct.transitiveclosure WHERE SubTypeId = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->bind_result($supertype);

        while ($stmt->fetch()) {
            $this->parents[] = $supertype;

            // Check if the concept is excluded or included based on the parent concept
            if (in_array($supertype, Parser::EXCLUDED_PARENTS)) $this->excluded = true;
            if (in_array($supertype, $this->includedParents)) $this->included = true;
        }

        $stmt->close();

        if (!$this->active) {
            // THe concept is not active, find substitute concepts
            $substitutes = array();
            $stmt = $mysqli->prepare("SELECT targetcomponentid FROM snomedct.associationrefset_s 
                                      WHERE referencedcomponentid = ? AND active = 1
                                      ORDER BY effectivetime DESC LIMIT 1");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $stmt->bind_result($targetcomponentid);

            while ($stmt->fetch()) {
                $substitutes[] = $targetcomponentid;
            }

            $stmt->close();

            foreach ($substitutes as $substitute) {
                $this->substitutes[] = new Concept(
                    $this->type,
                    $this->begin,
                    $this->end,
                    $this->text,
                    $this->polarity,
                    $this->subject,
                    $this->historyOf,
                    $this->ontologySystem,
                    $substitute,
                    null,
                    2,
                    $this->includedParents
                );
            }
        }

        // Get descriptions of the concept
        $stmt = $mysqli->prepare("SELECT active, term, typeid FROM snomedct.description_s WHERE conceptid = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->bind_result($active, $term, $type);

        while ($stmt->fetch()) {
            $active = ($active == "1");

            if ($active && $type == Parser::FSN) {
                // The found description is the fully specified name
                $this->ontologyFSN = $term;
            } else if ($active) {
                $this->activeOntologyDescriptions[] = $term;
                if (strcasecmp($term, $this->text) == 0 && $this->score <= 1) {
                    // The found description is a full text match with the text where the concept relates to
                    $this->score = 1;
                    $this->scoreMatch = $term;
                }
            } else {
                $this->inactiveOntologyDescriptions[] = $term;
            }
        }

        $stmt->close();
    }

    /**
     * @return string Get the ontology system where the concept is from
     */
    public function getOntologySystem()
    {
        return $this->ontologySystem;
    }

    /**
     * Get the ontology code (identifier) of the concept
     * @return string Ontology code (identifier) of the concept
     */
    public function getOntologyCode()
    {
        return $this->ontologyCode;
    }

    /**
     * Get the fully specified name of the concept in the ontology
     * @return string FSN of the concept in the ontology
     */
    public function getOntologyFSN()
    {
        return $this->ontologyFSN;
    }

    /**
     * Get the active descriptions of the concept in the ontology
     * @return array Array with active descriptions of the concept in the ontology
     */
    public function getActiveOntologyDescriptions()
    {
        return $this->activeOntologyDescriptions;
    }

    /**
     * Get the type of the concept
     * @return string Type of the concept
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the negation identifier of the concept
     * -1: negative, 0: neutral, 1: positive
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
     * 1: concept relates to subject's history, 0: concept does not relate to subject's history
     * @return int History identifier of the concept
     */
    public function getHistoryOf()
    {
        return $this->historyOf;
    }

    /**
     * Get the begin position of the text where the concept relates to
     * @return int Begin position of the text where the concept relates to
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Get the end position of the text where the concept relates to
     * @return int End position of the text where the concept relates to
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the length (end - begin) of the text where the concept relates to
     * @return int Length of the text where the concept relates to
     */
    public function getLength()
    {
        return $this->end - $this->begin;
    }

    /**
     * Get the position ('begin - end') of the text where the concept relates to
     * @return string Position of the text where the concept relates to
     */
    public function getPosition()
    {
        return $this->begin . '-' . $this->end;
    }

    /**
     * Get the substitute concepts for the concept
     * @return array Substitute concepts for the concept
     */
    public function getSubstitutes()
    {
        return $this->substitutes;
    }

    /**
     * Get the score defining how the concept was found
     * 0: cTAKES, 1: cTAKES (full text match), 3: Post-processing algorithms
     * @return int Score defining how the concept was found
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get the percentage of similarity between the text where the concept relates to and the concept description
     * @return float Percentage of similarity between the text where the concept relates to and the concept description
     */
    public function getScoreMatch()
    {
        return $this->scoreMatch;
    }

    /**
     * Set the percentage of similarity between the text where the concept relates to and the concept description
     * @param $score float Percentage of similarity
     */
    public function setScoreMatch($score)
    {
        $this->scoreMatch = $score;
    }

    /**
     * Check if the concept has active ontology descriptions
     * @return bool True if the concept has active ontology descriptions
     */
    public function hasActiveOntologyDescriptions()
    {
        return count($this->activeOntologyDescriptions) > 0;
    }

    /**
     * Check if the concept has a FSN
     * @return bool True if the concept has a FSN
     */
    public function hasOntologyFSN()
    {
        return $this->ontologyFSN != null;
    }

    /**
     * Check if the concept has overlap with another concept
     * @return bool True if the concept has overlap with another concept
     */
    public function hasOverlap()
    {
        return $this->overlap;
    }

    /**
     * Check if the specified concept is a parent of the concept
     * @param $parent Concept Possible parent Concept object
     * @return bool True if the specified concept is the parent of the concept
     */
    public function hasParent($parent)
    {
        return in_array($parent->getOntologyCode(), $this->parents);
        // changed fsn to code
    }

    /**
     * Check if the specified concept is a parent of the concept
     * @param $parent Concept Possible parent code
     * @return bool True if the specified concept is the parent of the concept
     */
    public function hasParentByCode($parent)
    {
        return in_array($parent, $this->parents);
    }

    /**
     * Check if the concept is active
     * @return bool True if the concept is active
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Check if the concept is excluded
     * @return bool True if the concept is excluded
     */
    public function isExcluded()
    {
        return $this->excluded;
    }

    /**
     * Check if the concept is included
     * @return bool True if the concept is excluded
     */
    public function isIncluded()
    {
        return $this->included;
    }

    /**
     * Set the overlap of this concept
     * @param $sOverlap bool True if concept has overlap with another concept
     */
    public function setOverlap($sOverlap)
    {
        $this->overlap = $sOverlap;
    }

    /**
     * Get the information of the concept in an array
     * @return array Array with ontology code, ontology system, FSN and descriptions
     */
    public function toArray()
    {
        return array(
            'code' => $this->ontologyCode,
            'system' => $this->ontologySystem,
            'fsn' => $this->ontologyFSN,
            'description' => $this->activeOntologyDescriptions
        );
    }

    /**
     * Get the unique concept ID
     * @return string Unique concept ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Calculate the percentage of similarity between the text where the concept relates to and the concept description
     * @return float Percentage of similarity between the text where the concept relates to and the concept description
     */
    public function getSimilarity()
    {
        $sim = 0;

        foreach ($this->activeOntologyDescriptions as $description) {

            $text1 = preg_replace("/[^A-Za-z0-9]/", '', strtolower($this->text));
            $text2 = preg_replace("/[^A-Za-z0-9]/", '', strtolower($description));

            similar_text($text1, $text2, $percentage);
            $percentage = $percentage / 100;

            $sim = max($sim, $percentage);
        }

        return $sim;
    }

    /**
     * Get the parent concepts that are included
     * @return array Parent concepts that are included
     */
    public function getIncludedParents()
    {
        return $this->includedParents;
    }
}
