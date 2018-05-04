<?php
/**
 * Fetches all SNOMED CT 'is a' relationships from the MySQL database and generates a transitive closure table from it
 */
class ClosureGenerator
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
     * @var array All SNOMED CT concepts
     */
    private $all;

    /**
     * Create a new ClosureGenerator object
     * Fetches all SNOMED CT 'is a' relationships from the MySQL database and generates a transitive closure table from it
     */
    public function __construct()
    {
        global $mysqli;

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $stmt = $mysqli->prepare("SELECT sourceid, destinationid, moduleid FROM snomedct.relationship_s 
                                  WHERE active = 1 AND typeid = " . $this::IS_A);
        $stmt->execute();
        $stmt->bind_result($source, $destination, $module);

        while ($stmt->fetch()) {
            $this->all[$destination][$source] = $module;
        }

        $transitive = array();

        foreach ($this->all as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $this->generateTransitive($transitive, $key, $key2);
            }
        }

        if (!empty($transitive)) $mysqli->query("TRUNCATE TABLE snomedct.transitiveclosure");


        foreach ($transitive as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $stmt = $mysqli->prepare("INSERT INTO snomedct.transitiveclosure (subtype, supertype) VALUES (?, ?)");
                $stmt->bind_param("ss", $key2, $key);
                $stmt->execute();
            }
        }
    }

    /**
     * @param $transitive array The array where the 'is a' relationships between concepts are stored in
     * @param $concept The current concept in the SNOMED CT hierarchy
     * @param $parent The parent of the current concept in the SNOMED CT hierarchy
     */
    private function generateTransitive(&$transitive, $concept, $parent)
    {
        if (isset($transitive[$parent])) {
            $transitive[$concept][$parent] = true;
            foreach ($transitive[$parent] as $key => $value) {
                $transitive[$concept][$key] = true;
            }
        } else if (isset($this->all[$parent])) {
            foreach ($this->all[$parent] as $key => $value) {
                $transitive[$parent][$key] = true;
                $this->generateTransitive($transitive, $parent, $key);
            }
            $this->generateTransitive($transitive, $concept, $parent);
        } else {
            $transitive[$concept][$parent] = true;
        }
    }
}