<?php
/**
 * Parses a given input (text or file) using the c
 */
class Parser
{
    /**
     * The end point of the cTAKES API
     */
    const CTAKES_URL = "http://localhost:8080/NLPServlet";

    /**
     * The syntax relationships that define subjects
     */
    const SUBJECTS = array(
        'nsubj',
        'attr',
        'dobj',
        'pobj',
        'root'
    );

    /**
     * The SNOMED CT identifiers of excluded parents
     * 370136006 |Namespace concept (namespace concept)
     */
    const EXCLUDED_PARENTS = array(
        '370136006'
    );

    /**
     * The SNOMED CT identifier of the fully specified name concept
     * 900000000000003001 | Fully specified name (core metadata concept) |
     */
    const FSN = '900000000000003001';

    /**
     * @var string The filename
     */
    private $filename;

    /**
     * @var SimpleXMLElement The XML document retrieved from the cTAKES API
     */
    private $xml;

    /**
     * @var string The input text
     */
    private $input;

    /**
     * @var SimpleXMLElement[] The cTAKES syntax information retrieved from the cTAKES API
     */
    private $syntax;

    /**
     * @var SimpleXMLElement[] The cTAKES semantic information retrieved from the cTAKES API
     */
    private $semantic;

    /**
     * @var array The semantic information stored in SemanticItem objects
     */
    private $semanticItems;

    /**
     * @var array The syntax information stored in SemanticItem objects
     */
    private $syntaxItems;

    /**
     * @var array The UMLS/SNOMED CT concepts found in the free-text
     */
    private $concepts;

    /**
     * @var array The UMLS/SNOMED CT concepts found in the free-text using cTAKES that do not overlap with another concept
     */
    private $conceptsNoOverlap;

    /**
     * @var array The unique UMLS/SNOMED CT concepts found in the free-text using cTAKES that do not overlap with another concept
     */
    private $conceptsUnique;

    /**
     * @var array The unique UMLS/SNOMED CT concepts found in the free-text using cTAKES and post-processing algorithms that do not overlap with another concept
     */
    private $conceptsPostProcessing;

    /**
     * @var array The log of the detection of overlapping concepts
     */
    private $logOverlap;

    /**
     * @var array The log of the detection of inactive concepts and their substitutes
     */
    private $logInactive;

    /**
     * @var array The information of all separate characters (semantic and syntax)
     */
    private $characters;

    /**
     * @var Relationships The relationships found in the free-text
     */
    private $relationships;

    /**
     * @var array The concepts that are selected to focus on
     */
    private $focusConcepts;

    /**
     * @var array The concepts, their attributes and relation to another concept that are selected to focus on
     */
    private $focusAttributes;

    /**
     * @var array The results of the concepts and attributes to focus on
     */
    private $focusResults;

    /**
     * @var array The parent concepts that are included
     */
    private $includedParents;

    /**
     * @var array The duration of various processes of the parsing
     */
    private $time;

    /**
     * @var SNOMEDSearch The search object to find SNOMED CT concepts
     */
    private $snomed;

    /**
     * Create a new Parser object and parse the given file or text
     * @param $content string The filename or text
     * @param $isFile bool If true, the content parameter will be used as the filename
     * @param $concepts array The concepts to focus on
     * @param $attributes array The attributes to focus on
     * @param $include array The parent concepts that are included
     */
    public function __construct($content, $isFile, $concepts, $attributes, $include)
    {
        set_time_limit(200);

        if ($isFile) {
            $this->filename = $content;

            $cFile = curl_file_create($this->filename);
            $post = array('file' => $cFile);
        } else {
            $this->filename = "";
            $post = array('text' => $content);
        }

        $this->focusConcepts = $concepts;
        $this->focusAttributes = $attributes;
        $this->includedParents = $include;

        $this->concepts = array();
        $this->conceptsUnique = array();
        $this->conceptsPostProcessing = array();

        $start = microtime(true);

        // CURL Request to cTAKES Java API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::CTAKES_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        $this->xml = simplexml_load_string($result);

        $this->input = html_entity_decode((string)$this->xml->Input);
        $this->syntax = $this->xml->Syntax;
        $this->semantic = $this->xml->Semantic;

        $this->time = array(
            'environment' => (float)$this->xml->Duration->Environment,
            'existed' => ($this->xml->Duration->Environment->attributes()->existed == "true") ? true : false,
            'parsing' => (float)$this->xml->Duration->Parsing
        );

        $this->time['load'] = microtime(true) - $start;

        $start = microtime(true);
        $textArray = preg_split('//u', $this->input, null, PREG_SPLIT_NO_EMPTY);
        $this->characters = array();

        foreach ($textArray as $position => $character) {
            $this->characters[$position] = array(
                'number' => null,
                'char' => $character,
                'syntax' => array(),
                'semantic' => array(),
                'text_semantic' => array()
            );
        }

        $this->time['split'] = microtime(true) - $start;

        $start = microtime(true);
        $this->parseSyntaxItems();
        $this->time['syntax'] = microtime(true) - $start;

        $start = microtime(true);
        $this->parseSemanticItems();
        $this->time['semantic'] = microtime(true) - $start;
        /*
                COMMENT FOR 1 and 2

                $start = microtime(true);
                $this->parseConcepts();
                $this->time['concepts'] = microtime(true) - $start;
        */
        /*
                COMMENT FOR 1, 2 and 3

                $this->snomed = new SNOMEDSearch();

                $start = microtime(true);
                $this->time['relationshipconcepts'] = 0;
                $this->parseRelationships();
                $this->time['relationships'] = microtime(true) - $start;

        */
        /*
            COMMENT FOR 4
 */

        $this->conceptsPostProcessing = $this->concepts;
        $this->time['concepts'] = 0;
        $this->time['relationships'] = 0;

        $start = microtime(true);
        $this->parseFocus();
        $this->time['focus'] = microtime(true) - $start;


        $this->time['total'] = $this->time['load'] + $this->time['split'] + $this->time['syntax'] +
            $this->time['semantic'] + $this->time['concepts'] + $this->time['relationships'] +
            $this->time['focus'];

    }

    /**
     * Parse the Syntax element in the cTAKES XML and save all information in SyntaxItem elements
     */
    private function parseSyntaxItems()
    {

        $this->syntaxItems = array();
        $this->relationships = new Relationships();
        foreach ($this->syntax->children() as $mentionType) {
            foreach ($mentionType->children() as $mention) {
                $type = $mention->getName();
                $tokenNumber = (string)$mention->attributes()->token;
                $item = new SyntaxItem(
                    uniqid(),
                    $type,
                    (string)$mention->attributes()->begin,
                    (string)$mention->attributes()->end
                );
                $this->syntaxItems[] = $item;

                if ($type == "ConllDependencyNode" && $mention->attributes()->dependentText != null) {
                    $this->relationships->addRelationShip(
                        (string)$mention->attributes()->id,
                        (string)$mention->attributes()->dependentId,
                        (string)$mention->attributes()->begin,
                        (string)$mention->attributes()->end,
                        (string)$mention->attributes()->dependentBegin,
                        (string)$mention->attributes()->dependentEnd,
                        (string)$mention->attributes()->relation,
                        mb_substr(
                            $this->input,
                            (int)$mention->attributes()->begin,
                            (int)$mention->attributes()->end - (int)$mention->attributes()->begin,
                            "utf-8"
                        )
                    );
                }

                for ($position = $item->getBegin(); $position < $item->getEnd(); $position++) {
                    $this->characters[$position]['syntax'][] = $item;
                    if ($tokenNumber != null) $this->characters[$position]['number'][] = $tokenNumber;
                }
            }
        }
    }

    /**
     * Parse the Semantic element in the cTAKES XML and save all information in SemanticItem elements
     */
    private function parseSemanticItems()
    {
        $this->semanticItems = array();
        foreach ($this->semantic->children() as $mentionType) {
            foreach ($mentionType->children() as $mention) {
                $type = $mention->getName();
                $mentionConcepts = array();
                foreach ($mention->concept as $concept) {
                    $concept = new Concept(
                        $type,
                        (int)$mention->attributes()->begin,
                        (int)$mention->attributes()->end,
                        mb_substr(
                            $this->input,
                            (int)$mention->attributes()->begin,
                            (int)$mention->attributes()->end - (int)$mention->attributes()->begin,
                            "utf-8"
                        ),
                        (int)$mention->attributes()->polarity,
                        (string)$mention->attributes()->subject,
                        (int)$mention->attributes()->historyOf,
                        (string)$concept['system'],
                        (string)$concept['code'],
                        (string)$concept['cui'],
                        0,
                        $this->includedParents
                    );

                    $mentionConcepts[] = $concept;
                    $this->concepts[] = $concept;
                }
                $this->semanticItems[] = new SemanticItem(
                    uniqid(),
                    $type,
                    (int)$mention->attributes()->begin,
                    (int)$mention->attributes()->end,
                    $mentionConcepts,
                    (int)$mention->attributes()->polarity,
                    (string)$mention->attributes()->subject,
                    (int)$mention->attributes()->historyOf
                );
            }
        }
    }

    /**
     * Parse all concepts:
     * - Detect overlap
     * - Find substitutes for inactive concepts
     * - Save most specific concept into array
     */
    private function parseConcepts()
    {
        /* COMMENT FOR 1, 2 and 4
        foreach ($this->concepts as $c1 => $concept1) {
            if ($concept1->getOntologySystem() == "cancer") {
                $substitutes = $concept1->getSubstitutes();
                $this->concepts = array_merge($this->concepts, $substitutes);
            }
        }
        */
        /* COMMENT FOR 1, 2 and 3 */

        // Loop over all concepts for active, overlap, and substitution detection
        foreach ($this->concepts as $c1 => $concept1) {
            if (!$concept1->hasOverlap()) {
                if ($concept1->isActive()) {
                    // This concept is active, detect if there is overlap
                    foreach ($this->concepts as $c2 => $concept2) {
                        if (!$concept2->hasOverlap()) {
                            if (
                                ($concept1->getBegin() > $concept2->getBegin() && $concept1->getEnd() < $concept2->getEnd()) ||
                                ($concept1->getBegin() == $concept2->getBegin() && $concept1->getEnd() < $concept2->getEnd()) ||
                                ($concept1->getBegin() > $concept2->getBegin() && $concept1->getEnd() == $concept2->getEnd())
                            ) {
                                // Overlap detected
                                $this->concepts[$c1]->setOverlap(true);

                                $this->logOverlap[] = array(
                                    'text1' => mb_substr($this->input, $concept1->getBegin(), $concept1->getLength(), "utf-8"),
                                    'code1' => $concept1->getOntologyCode(),
                                    'fsn1' => $concept1->getOntologyFSN(),
                                    'text2' => mb_substr($this->input, $concept2->getBegin(), $concept2->getLength(), "utf-8"),
                                    'code2' => $concept2->getOntologyCode(),
                                    'fsn2' => $concept2->getOntologyFSN()
                                );
                            }
                        }
                    }
                } else {
                    // This concept is inactive, get the substitute concept
                    $substitutes = $concept1->getSubstitutes();
                    $this->concepts = array_merge($this->concepts, $substitutes);

                    foreach ($substitutes as $substitute) {
                        $this->logInactive[] = array(
                            'text' => mb_substr($this->input, $concept1->getBegin(), $concept1->getLength(), "utf-8"),
                            'old_code' => $concept1->getOntologyCode(),
                            'old_fsn' => $concept1->getOntologyFSN(),
                            'new_code' => $substitute->getOntologyCode(),
                            'new_fsn' => $substitute->getOntologyFSN()
                        );
                    }
                }
            }
        }

        $seen = array();

        // Loop over all concepts to get the unique concepts
        foreach ($this->concepts as $concept) {
            $position = $concept->getPosition();

            // Check if concept is active and is not a namespace
            if ($concept->isActive() && !$concept->isExcluded()) {
                // Only check unique concepts
                if (!isset($seen[$position][$concept->getOntologyFSN()])) {
                    $seen[$position][$concept->getOntologyFSN()] = true;
                    $this->conceptsNoOverlap[] = $concept;

                    $add = false;
                    if (isset($this->conceptsUnique[$position])) {
                        // There is already a concept listed for this position
                        $skip = false;

                        foreach ($this->conceptsUnique[$position] as $uniqueConcept) {
                            if ($uniqueConcept->hasParent($concept)) $skip = true;
                        }

                        if (!$skip) {
                            $this->conceptsUnique[$position][$concept->getOntologyCode()] = $concept;
                            $add = true;
                        }
                    } else {
                        // There is no concept listed for this position, add it to the array
                        $this->conceptsUnique[$position] = array($concept->getOntologyCode() => $concept);
                        $add = true;
                    }

                    if ($add) {
                        $this->conceptsPostProcessing[] = $concept;
                        for ($position = $concept->getBegin(); $position < $concept->getEnd(); $position++) {
                            $this->relationships->addConcept($position, $concept);
                        }
                    }
                }
            }
        }

    }

    /**
     * Parse all detected relationships between concepts and detect new concepts
     * - Parse relationships between words
     * - Get all relations between words and find concepts that match the text
     * - Detect overlap between the detected concepts and the existing concepts
     * - Save most specific concept into array
     */
    private function parseRelationships()
    {
        // Parse all relationships between words
        $this->relationships->parseRelationships();

        // Find new concepts
        foreach ($this->relationships->getAllRelationships() as $relationship) {
            if (in_array($relationship->getRelation(), self::SUBJECTS)) {
                $info = $this->relationships->parseDependencies($relationship, null);
                $text = $info['text'];
                $polarity = $info['polarity'];
                if (count($text) > 1) {
                    $concepts = $this->parseRelationshipConcepts($text, $polarity);
                    $this->conceptsPostProcessing = array_merge($this->conceptsPostProcessing, $concepts);
                }
            }
        }

        $log = array();

        // Detect overlap
        foreach ($this->conceptsPostProcessing as $c1 => $concept1) {
            if (!$concept1->hasOverlap()) {
                // This concept is active, detect if there is overlap
                foreach ($this->conceptsPostProcessing as $c2 => $concept2) {
                    if (!$concept2->hasOverlap()) {
                        if (
                            ($concept1->getBegin() > $concept2->getBegin() && $concept1->getEnd() < $concept2->getEnd()) ||
                            ($concept1->getBegin() == $concept2->getBegin() && $concept1->getEnd() < $concept2->getEnd()) ||
                            ($concept1->getBegin() > $concept2->getBegin() && $concept1->getEnd() == $concept2->getEnd())
                        ) {
                            if ($concept2->getScoreMatch() >= $concept1->getScoreMatch()) {
                                // Overlap detected
                                $this->conceptsPostProcessing[$c1]->setOverlap(true);

                                $this->logOverlap[] = array(
                                    'text1' => mb_substr($this->input, $concept1->getBegin(), $concept1->getLength(), "utf-8"),
                                    'code1' => $concept1->getOntologyCode(),
                                    'fsn1' => $concept1->getOntologyFSN(),
                                    'text2' => mb_substr($this->input, $concept2->getBegin(), $concept2->getLength(), "utf-8"),
                                    'code2' => $concept2->getOntologyCode(),
                                    'fsn2' => $concept2->getOntologyFSN()
                                );
                                $log[] = array(
                                    'text1' => mb_substr($this->input, $concept1->getBegin(), $concept1->getLength(), "utf-8"),
                                    'code1' => $concept1->getOntologyCode(),
                                    'fsn1' => $concept1->getOntologyFSN(),
                                    'text2' => mb_substr($this->input, $concept2->getBegin(), $concept2->getLength(), "utf-8"),
                                    'code2' => $concept2->getOntologyCode(),
                                    'fsn2' => $concept2->getOntologyFSN()
                                );
                            }
                        } else if ($concept1->getBegin() == $concept2->getBegin() && $concept1->getEnd() == $concept2->getEnd()
                            && $concept1 != $concept2
                        ) {
                            if ($concept1->getOntologyCode() == $concept2->getOntologyCode()) {
                                $this->conceptsPostProcessing[$c1]->setOverlap(true);
                            }
                        }
                    }
                }
            }
        }

        $this->relationships->clearConcepts();

        // Add concepts to array
        foreach ($this->conceptsPostProcessing as $concept) {
            if (!$concept->hasOverlap()) {
                for ($position = $concept->getBegin(); $position < $concept->getEnd(); $position++) {
                    $this->characters[$position]['semantic'][$concept->getBegin() . '-' . (1000 - $concept->getEnd() + $concept->getBegin()) . '-' . $concept->getEnd()][] = $concept;
                    ksort($this->characters[$position]['semantic']);

                    $this->relationships->addConcept($position, $concept);
                }
            }
        }
    }

    /**
     * @param $words array Array of words that relate to each other
     * @param $polarity int The polarity of the text fragment
     * @return array Found relationships
     */
    private function parseRelationshipConcepts($words, $polarity)
    {
        $array = array();

        if (count($words)) {
            $start = microtime(true);

            // Find concepts using combinations between the provided words
            $foundConcepts = $this->snomed->findConceptCombinations($words);

            $time = microtime(true) - $start;
            $this->time['relationshipconcepts'] = $this->time['relationshipconcepts'] + $time;
            $this->time['relationshipconceptspecified'][] = $time;

            if (count($foundConcepts) > 0) {
                foreach ($foundConcepts as $pos => $concepts) {
                    foreach ($concepts as $conceptid => $boolean) {
                        $posArr = explode("-", $pos);
                        $length = $posArr[1] - $posArr[0];
                        $text = mb_substr($this->input, $posArr[0], $length, "utf-8");
                        $concept = new Concept("PostProcessing", $posArr[0], $posArr[1], $text, $polarity, null, null, "SNOMEDCT", $conceptid, null, 3, $this->includedParents);

                        // Calculate similarity between text fragment and concept description
                        $similarity = $concept->getSimilarity();
                        $concept->setScoreMatch($similarity);

                        // If more than 95% match, include concept
                        if ($similarity > 0.95 && !isset($this->conceptsUnique[$pos][$concept->getOntologyCode()])) {
                            $array[] = $concept;
                        }
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Check if the focus concepts and focus attributes are found
     */
    private function parseFocus()
    {
        $this->focusResults = array('concepts' => array(), 'attributes' => array());
        foreach ($this->conceptsPostProcessing as $concept) {
            //foreach ($conceptList as $concept) {
            if ($concept->getOntologySystem() == "SNOMEDCT"
                && $concept->isIncluded() && !$concept->hasOverlap()
            ) {
                foreach ($this->focusConcepts as $id => $focusConcept) {
                    if ($focusConcept['conceptSelf'] && $concept->getOntologyCode() == $focusConcept['conceptId']) {
                        $this->focusResults['concepts'][$id][] = array(
                            'self' => true,
                            'children' => false,
                            'concept' => $concept
                        );
                    }
                    if ($focusConcept['conceptChildren'] && $concept->hasParentByCode($focusConcept['conceptId'])) {
                        $this->focusResults['concepts'][$id][] = array(
                            'self' => false,
                            'children' => true,
                            'concept' => $concept
                        );
                    }
                }
            }
        }

        foreach ($this->getUniqueAttributes() as $id => $destination) {
            foreach ($destination as $attribute) {
                foreach ($attribute as $origin) {
                    if ($origin->getOrigin()->getOntologyCode() != $origin->getDestination()->getOntologyCode()) {
                        foreach ($this->focusAttributes as $id => $focusAttribute) {
                            if ($focusAttribute['conceptId1'] == $origin->getDestination()->getOntologyCode()
                                && $focusAttribute['conceptId2'] == $origin->getOrigin()->getOntologyCode()
                            ) {
                                $this->focusResults['attributes'][$id][] = array(
                                    'self' => false,
                                    'children' => true,
                                    'concept' => $origin
                                );
                            }
                            if ($focusAttribute['conceptChildren'] && $origin->getDestination()->hasParentByCode($focusAttribute['conceptId1'])
                                && $focusAttribute['conceptId2'] == $origin->getOrigin()->getOntologyCode()
                            ) {
                                $this->focusResults['attributes'][$id][] = array(
                                    'self' => false,
                                    'children' => true,
                                    'concept' => $origin
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get unique attribute relationships
     * @return array Unique attribute relationships
     */
    public function getUniqueAttributes()
    {
        $array = array();
        foreach ($this->relationships->getRelationships() as $relationship) {
            foreach ($relationship->getAttributeConcepts() as $attributeRelationship) {
                if ($attributeRelationship->getDestination()->getPosition() != $attributeRelationship->getOrigin()->getPosition()) {
                    $id = $attributeRelationship->getDestination()->getId();

                    $attribute = $attributeRelationship->getAttribute()->getOntologyFSN();
                    $origin = $attributeRelationship->getOrigin()->getOntologyFSN();
                    $array[$id][$attribute][$origin] = $attributeRelationship;
                }
            }
        }

        return $array;
    }

    /**
     * Get The input text
     * @return string Input text
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get the UMLS/SNOMED CT concepts found in the free-text
     * @return array UMLS/SNOMED CT concepts found in the free-text
     */
    public function getConcepts()
    {
        return $this->conceptsNoOverlap;
    }

    /**
     * Get The unique UMLS/SNOMED CT concepts found in the free-text using cTAKES that do not overlap with another concept
     * @return array Unique UMLS/SNOMED CT concepts found in the free-text using cTAKES that do not overlap with another concept
     */
    public function getUniqueConcepts()
    {
        return $this->conceptsUnique;
    }

    /**
     * Get the unique UMLS/SNOMED CT concepts found in the free-text using cTAKES and post-processing algorithms that do not overlap with another concept
     * @return array Unique UMLS/SNOMED CT concepts found in the free-text using cTAKES and post-processing algorithms that do not overlap with another concept
     */
    public function getPostProcessedConcepts()
    {
        return $this->conceptsPostProcessing;
    }

    /**
     * Get The log of the detection of overlapping concepts and inactive concepts and their substitutes
     * @return array Log of the detection of overlapping concepts and inactive concepts and their substitutes
     */
    public function getLog()
    {
        return array(
            'overlap' => $this->logOverlap,
            'inactive' => $this->logInactive
        );
    }

    /**
     * Get the information of the parser in an array
     * @return array Array with input, formatted input, focus results, concepts, relationships, attributes and time
     */
    public function toArray()
    {
        $focus = array();

        foreach ($this->focusConcepts as $id => $focusConcept) {
            $focus['concepts'][$id] = array('found' => false, 'polarity' => null, 'results' => array());
        }

        foreach ($this->focusAttributes as $id => $focusAttribute) {
            $focus['attributes'][$id] = array('found' => false, 'results' => array());
        }

        foreach ($this->focusResults['concepts'] as $resultId => $array) {
            foreach ($array as $result) {
                $concept = $result['concept'];
                $focus['concepts'][$resultId]['found'] = true;
                $focus['concepts'][$resultId]['results'][] = array(
                    'id' => $concept->getOntologyCode(),
                    'fsn' => $concept->getOntologyFSN(),
                    'text' => mb_substr($this->input, $concept->getBegin(), $concept->getLength(), "utf-8"),
                    'children' => $result['children'],
                    'self' => $result['self']
                );

                if ($concept->getPolarity() > 0 && $focus['concepts'][$resultId]['polarity'] == null) {
                    $focus['concepts'][$resultId]['polarity'] = true;
                } else if ($concept->getPolarity() < 0) {
                    $focus['concepts'][$resultId]['polarity'] = false;
                }
            }
        }

        foreach ($this->focusResults['attributes'] as $resultId => $array) {
            foreach ($array as $result) {
                $attributeRelationship = $result['concept'];
                $focus['attributes'][$resultId]['found'] = true;
                $focus['attributes'][$resultId]['results'][] = array(
                    'destination' => array(
                        'id' => $attributeRelationship->getDestination()->getOntologyCode(),
                        'fsn' => $attributeRelationship->getDestination()->getOntologyFSN()
                    ),
                    'attribute' => array(
                        'id' => $attributeRelationship->getAttribute()->getOntologyCode(),
                        'fsn' => $attributeRelationship->getAttribute()->getOntologyFSN()
                    ),
                    'origin' => array(
                        'id' => $attributeRelationship->getOrigin()->getOntologyCode(),
                        'fsn' => $attributeRelationship->getOrigin()->getOntologyFSN()
                    ),
                    'children' => $result['children'],
                    'self' => $result['self']
                );
            }
        }

        $concepts = array();

        foreach ($this->conceptsPostProcessing as $concept) {
            if ($concept->isIncluded() && !$concept->isExcluded()
                && !$concept->hasOverlap()
            ) {
                $concepts[] = array(
                    'id' => $concept->getOntologyCode(),
                    'fsn' => $concept->getOntologyFSN(),
                    'text' => mb_substr($this->input, $concept->getBegin(), $concept->getLength(), "utf-8"),
                    'score' => $concept->getScore(),
                    'match' => $concept->getScoreMatch(),
                    'polarity' => $concept->getPolarity(),
                    'subject' => $concept->getSubject(),
                    'history' => $concept->getHistoryOf(),
                    'begin' => $concept->getBegin(),
                    'end' => $concept->getEnd()
                );
            }
        }

        $relationships = array();

        foreach ($this->getRelationships() as $relationship) {
            foreach ($relationship->getOriginConcepts() as $origin) {
                foreach ($relationship->getDestinationConcepts() as $destination) {
                    if ($origin->getOntologyCode() != $destination->getOntologyCode()) {
                        $relationships[] = array(
                            'origin' => array(
                                'id' => $origin->getOntologyCode(),
                                'fsn' => $origin->getOntologyFSN(),
                                'text' => mb_substr($this->input, $relationship->getOriginBegin(), $relationship->getOriginLength(), "utf-8")
                            ),
                            'destination' => array(
                                'id' => $destination->getOntologyCode(),
                                'fsn' => $destination->getOntologyFSN(),
                                'text' => mb_substr($this->input, $relationship->getDestinationBegin(), $relationship->getDestinationLength(), "utf-8")
                            )
                        );
                    }
                }
            }
        }

        $attributes = array();

        foreach ($this->getRelationships() as $relationship) {
            foreach ($relationship->getAttributeConcepts() as $attributeRelationship) {
                if ($attributeRelationship->getOrigin()->getOntologyCode() != $attributeRelationship->getDestination()->getOntologyCode()) {
                    $attributes[] = array(
                        'destination' => array(
                            'id' => $attributeRelationship->getDestination()->getOntologyCode(),
                            'fsn' => $attributeRelationship->getDestination()->getOntologyFSN()
                        ),
                        'attribute' => array(
                            'id' => $attributeRelationship->getAttribute()->getOntologyCode(),
                            'fsn' => $attributeRelationship->getAttribute()->getOntologyFSN()
                        ),
                        'origin' => array(
                            'id' => $attributeRelationship->getOrigin()->getOntologyCode(),
                            'fsn' => $attributeRelationship->getOrigin()->getOntologyFSN()
                        )
                    );
                }
            }
        }

        return array(
            'input' => $this->input,
            'formatted' => $this->getFormattedInput(),
            'focus' => $focus,
            'concepts' => $concepts,
            'relationships' => $relationships,
            'attributes' => $attributes,
            'time' => $this->time
        );
    }

    /**
     * Get the found relationships
     * @return array Found relationships
     */
    public function getRelationships()
    {
        return $this->relationships->getRelationships();
    }

    /**
     * Format input and add found concepts to it
     * @return string Formatted input with found concepts
     */
    private function getFormattedInput()
    {
        $number = -1;
        $output = '';

        $syntaxCountArray = array();
        $this->semanticCountArray = array();

        // Add colors for the hover divs
        $this->colorArray = array();
        $this->colorArray["PostProcessing"] = RandomColor::one(
            array(
                'luminosity' => 'bright',
            )
        );

        // Loop through all characters to add syntax and semantic information
        foreach ($this->characters as $position => $character) {
            // Place all characters related to a token in a div
            $current = $character['number'][0];
            $hasNext = ($position + 1 < count($this->characters));

            if ($hasNext) $next = $this->characters[$position + 1]['number'][0];

            if ($number != $current) {
                $classes = array();
                if ($character['syntax']) {
                    $firstSyntax = $character['syntax'][0]->getType();
                    $classes[] = 'first_' . $firstSyntax;
                }
                if (strlen(trim($character['char'])) == 0) {
                    $classes[] = 'blank';
                }
                $output .= '<div class="token ' . implode(' ', $classes) . '">' . "\n";
                $number = $current;
            }

            // Add classes used for hovering / negation
            $classes = array();

            foreach ($character['syntax'] as $syntax) {
                $classes[] = 'syntax_' . $syntax->getType();
            }

            foreach ($character['semantic'] as $semanticArray) {
                foreach ($semanticArray as $semantic) {
                    if ($semantic->getPolarity() == -1) $classes[] = 'negation';
                }
            }


            $output .= '  <div id="' . $position . '" data-number="' . $current . '" class="character ' . implode(' ', array_unique($classes)) . '">' . "\n";
            $output .= '      <div class="content">' . $character['char'] . '</div>' . "\n";

            // Add syntax information
            $syntaxCount = 0;
            foreach ($character['syntax'] as $syntax) {
                if (isset($syntaxCountArray[$syntax->getType()])) $syntaxCountArray[$syntax->getType()]++;
                else {
                    $syntaxCountArray[$syntax->getType()] = 1;

                    $this->colorArray[$syntax->getType()] = RandomColor::one(
                        array(
                            'luminosity' => 'bright',
                        )
                    );
                }
                $output .= '<div class="syntax-info info-underline info-' . $syntax->getType() . '" data-id="' . $syntax->getID() . '" style="background-color: ' . $this->colorArray[$syntax->getType()] . '"></div>';
                $syntaxCount++;
            }

            // Add semantic information
            $semanticCount = 0;
            foreach ($character['semantic'] as $semanticArray) {
                foreach ($semanticArray as $semantic) {
                    if (isset($this->semanticCountArray[$semantic->getType()])) $this->semanticCountArray[$semantic->getType()]++;
                    else {
                        $this->semanticCountArray[$semantic->getType()] = 1;

                        $this->colorArray[$semantic->getType()] = RandomColor::one(
                            array(
                                'luminosity' => 'bright',
                            )
                        );
                    }
                    $output .= '<div class="semantic-info hoverable info-underline info-' . $semantic->getType() . '" data-id="' . $semantic->getOntologyCode() . '" style="background-color: ' . $this->colorArray[$semantic->getType()] . '">' . "\n";

                    // Add UMLS concept information
                    $output .= '<div class="umls-concept">' . "\n";
                    if ($semantic->hasOntologyFSN()) $output .= '<h4>' . $semantic->getOntologyFSN() . '</h4>';
                    $output .= '<h5>' . $semantic->getOntologyCode() . ' (' . $semantic->getOntologySystem() . ')</h5>';

                    if ($semantic->hasActiveOntologyDescriptions()) {
                        $output .= '<ul>' . "\n";
                        foreach ($semantic->getActiveOntologyDescriptions() as $description) {
                            $output .= '<li>' . $description . '</li>' . "\n";
                        }
                        $output .= '</ul>' . "\n";
                    }
                    $output .= '</div>';

                    $output .= '</div>';

                    $semanticCount++;
                }
            }
            $output .= '  </div>' . "\n";

            if (!$hasNext || $next != $current) {
                $output .= '</div>';
            }
        }

        return $output;
    }

    /**
     * Get the information of all separate characters (semantic and syntax)
     * @return array Information of all separate characters (semantic and syntax)
     */
    public function getCharacters()
    {
        return $this->characters;
    }

    /**
     * Get the duration of various processes of the parsing
     * @return array Duration of various processes of the parsing
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Get the concepts that are selected to focus on
     * @return array Concepts that are selected to focus on
     */
    public function getFocusConcepts()
    {
        return $this->focusConcepts;
    }

    /**
     * Get the concepts, their attributes and relation to another concept that are selected to focus on
     * @return array Concepts, their attributes and relation to another concept that are selected to focus on
     */
    public function getFocusAttributes()
    {
        return $this->focusAttributes;
    }

    /**
     * Get all semantic items
     * @return Semantic items and their position (begin/end)
     */
    private function getSemanticItems()
    {
        $output = array();

        foreach ($this->semanticItems as $semanticItem) {
            $item = array(
                'begin' => $semanticItem->getBegin(),
                'end' => $semanticItem->getEnd(),
                'polarity' => $semanticItem->getPolarity(),
                'subject' => $semanticItem->getSubject(),
                'historyOf' => $semanticItem->getHistoryOf(),
                'concepts' => $semanticItem->getActiveUmlsConcepts()
            );

            $output[$semanticItem->getType()][] = $item;
        }

        return $output;
    }

    /**
     * Get all syntax items
     * @return Syntax items and their position (begin/end)
     */
    private function getSyntaxItems()
    {
        $output = array();

        foreach ($this->syntaxItems as $syntaxItem) {
            $output[$syntaxItem->getType()][] = array(
                'begin' => $syntaxItem->getBegin(),
                'end' => $syntaxItem->getEnd()
            );
        }

        return $output;
    }
}