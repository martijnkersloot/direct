<?php
/**
 * API for the Parser class
 */
class ParserAPI extends API
{
    /**
     * Create a new ParserAPI with the provided request and origin parameters
     * @param $request array Request information
     * @param $origin string HTTP Origin
     */
    public function __construct($request, $origin) {
        parent::__construct($request);
    }

    /**
     * Check if the cTAKES API is running
     * @return array Running information
     */
    protected function check() {
        if ($this->method == 'GET') {
            $ch = curl_init(Parser::CTAKES_URL);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($httpCode>=200 && $httpCode<300){
                return array('running' => true);
            } else {
                return array('running' => false);
            }
        } else {
            return array('error' => 'Only accepts GET requests');
        }
    }

    /**
     * Parse the given input and return the results
     * @return array Results of the parsing of the input
     */
    protected function parse() {
        if ($this->method == 'POST') {
            $concepts = array();
            $attributes = array();
            $include = array();
            if(isset($this->request['concepts'])) $concepts = $this->request['concepts'];
            if(isset($this->request['attributes'])) $attributes = $this->request['attributes'];
            if(isset($this->request['include'])) $include = $this->request['include'];

            if(isset($this->request['fileId']))
            {
                $text = $_SESSION['files'][$this->request['fileId']]['contents'];
            }
            else $text = $this->request['text'];

            $parser = new Parser($text, false, $concepts, $attributes, $include);

            return $parser->toArray();
        } else {
            return array('error' => 'Only accepts POST requests');
        }
    }

    /**
     * Generate a transitive closure table
     * @return array Success
     */
    protected function transitive() {
        if ($this->method == 'GET') {
            $closure = new ClosureGenerator();
            return array('success' => true);
        } else {
            return array('error' => 'Only accepts GET requests');
        }
    }

    /**
     * Search for concepts by term and parent
     * @return array Concept information
     */
    protected function search()
    {
        if ($this->method == 'GET' && isset($this->request['term'])) {
            $snomedSearch = new SNOMEDSearch();
            if(isset($this->request['parent']))
            {
                $array = $snomedSearch->findConcepts($this->request['term'], $this->request['parent']);
            }
            else
            {
                $array = $snomedSearch->findConcepts($this->request['term'], null);
            }
            return $array;
        } else {
            return array('error' => 'Only accepts GET requests');
        }
    }

    /**
     * Fetch specific information of one concept
     * @return array Concept information
     */
    protected function concept()
    {
        if ($this->method == 'GET' && isset($this->request['term'])) {
            $snomedSearch = new SNOMEDSearch();

            return $snomedSearch->findConcept($this->request['term']);
        } else {
            return array('error' => 'Only accepts GET requests');
        }
    }

    /**
     * Get all top level SNOMED CT concepts
     * @return array Top level SNOMED CT concepts
     */
    protected function toplevel()
    {
        if ($this->method == 'GET') {
            $snomedSearch = new SNOMEDSearch();

            return $snomedSearch->findTopLevel();
        } else {
            return array('error' => 'Only accepts GET requests');
        }
    }

    /**
     * Upload a file and add it to the batch
     * @return array Success
     * @throws ErrorException
     */
    protected function upload()
    {
        if ($this->method == 'POST') {
            foreach($this->files['name'] as $id => $fileName)
            {
                if($this->files['error'][$id])
                {
                    throw new ErrorException("Could not upload file " . $fileName, E_NOTICE);
                }
                else {
                    $_SESSION['files'][] = array(
                        'name' => $fileName,
                        'contents' => file_get_contents($this->files['tmp_name'][$id])
                    );
                }
            }
            return array('success' => true);
        } else {
            return array('error' => 'Only accepts POST requests');
        }
    }

    /**
     * Get all uploaded files
     * @return array Uploaded files
     */
    protected function uploadedfiles()
    {
        if ($this->method == 'GET') {
            $array = array();

            if(!isset($_SESSION['files'])) return array();

            foreach ($_SESSION['files'] as $id => $file)
            {
                $array[] = array('id' => $id, 'file' => $file['name']);
            }

            return $array;
        }
        else if ($this->method == 'POST') {
            if(isset($this->request['task']))
            {
                $task = $this->request['task'];
                if($task == 'remove')
                {
                    $id = $this->request['id'];
                    unset($_SESSION['files'][$id]);
                    array_values($_SESSION['files']);

                    return array('success' => true);
                }
            }
        }
        else {
            return array('error' => 'Only accepts POST requests');
        }
    }
}