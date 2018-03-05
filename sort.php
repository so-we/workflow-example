<?php
/**
 * Group and sort by state - example provided by Sclable - https://sclable.com/
 * Groups workflow issues by their state and sorts the states by given transitions.
 * Tested with PHP 7.1.13
 * 
 * Assumptions:
 * - The workflow may not have a state that goes back to the first state.
 * - The order of states that are equal (eg. ON HOLD and DOING) depends on their order in the initialization.
 * 
 * @author Sonja Weghuber <sonja@weghuber.at>
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

const DISPLAY_CLI = true;
const LINE_BREAK = DISPLAY_CLI? PHP_EOL : "<br/>";

/* CHECK PHP VERSION */
version_compare(PHP_VERSION, '7.0.0') >= 0 or die( 'Please use at least PHP 7.0.0, your version: ' . PHP_VERSION);

echo "WELCOME - here are the issues grouped and sorted by state:". LINE_BREAK;
echo "Assumptions:". LINE_BREAK;
echo "- The workflow may not have a state that goes back to the first state.". LINE_BREAK;
echo "- The order of states that are equal (eg. ON HOLD and DOING) and the order of the issues depend on their order in the initialization.". LINE_BREAK;
echo "==========================================================================================================". LINE_BREAK;

/* call workflow */
$engine = new WorkflowEngine();
$engine->printIssuesAndStates();

class WorkflowEngine {
    
    /* STATES */
    const ON_HOLD = "ON HOLD";
    const DOING = "DOING";
    const TO_DO = "TO DO";
    const DONE = "DONE";
    const FAILED = "FAILED";
        
    private $issues;
    private $transitions;
    private $sortedStates;
    
    /**
     * Init the issues and transitions with the constants and sort them.
     */
    public function __construct() {
        
        $this->issues  = array(
            new Issue("Get new coffee machine", self::DONE),
            new Issue("(Re)fill beans", self::DOING),
            new Issue("Fill water tank", self::TO_DO),
            new Issue("Make coffee", self::ON_HOLD),
            new Issue("Make more coffee", self::TO_DO),
            new Issue("Turn old coffee machine off and on again", self::FAILED),
            new Issue("Repair old coffee machine", self::FAILED)
        );
          
        // as we need the fromState as index, we could actually get rid of the class Transition and just use the array
        $this->transitions = array(
            self::ON_HOLD => new Transition(self::ON_HOLD, array(self::DOING)), 
            self::TO_DO => new Transition(self::TO_DO, array(self::ON_HOLD, self::DOING)),
            self::DOING => new Transition(self::DOING, array(self::DONE, self::FAILED, self::ON_HOLD))
        );
        
        $this->sortStates();
    }
    
    /**
     * Prints the issues and states.
     */
    public function printIssuesAndStates() {
        
        foreach ($this->sortedStates as $state) {
            echo $state . LINE_BREAK;
            foreach ($this->issues as $issue) {
                if (strcmp($issue->getState(), $state) == 0) {
                    echo " - " . $issue->getTitle() . LINE_BREAK; 
                }
            }
        }
    }
    
    /**
     * Sorts the states depending on the given transitions.
     */
    private function sortStates() {
        
        $this->sortedStates = array();
        $first = $this->getFirstState();
        
        if ($first != NULL) {
            $this->sortedStates[$first] = $first;
            $this->appendChildStates($this->sortedStates, $first); // no need to pass states
        } else {
            echo "INITIALIZATION ERROR: Please check your workflow - no starting point defined.";
        }

    }
    
    /**
     * Returns the first status that has no previous state.
     * 
     * @return STATE or NULL
     */
    private function getFirstState() {
        
        // go through each transitions
        foreach ($this->transitions as $from => $to) {
            // is the checked state successor of another state?
            foreach ($this->transitions as $key => $toArr) {
                // if true, it's not the first - continue with next state
                if (in_array($from, $toArr->getToStates())) {
                    continue 2;
                }
            }
            return $from;
        }
        return NULL;
    }
    
    /**
     * Recursive function - appends the children of a state.
     * 
     * @param $sorted The array the children are added to.
     * @param $parent The state whose children will be appended.
     * @return The sorted states array containing the children of the parent node
     */
    private function appendChildStates(array &$sorted, $parent) {

        $noChildStep = ! array_key_exists($parent, $this->transitions);
        if ($noChildStep) {
            return $sorted;
        }
        
        // go through the children of the state
        foreach ($this->transitions[$parent]->getToStates() as $child) {
            // add them if not already added
            if (! in_array($child, $sorted)) {
                $sorted = $this->array_insert_after($parent, $sorted, $child, $child);
                $sorted = $this->appendChildStates($sorted, $child);
            }
        }
        return $sorted;
    }
    
    /**
     * Inserts a new key/value after the key in the array.
     *
     * @param $key
     *   The key to insert after.
     * @param $array
     *   An array to insert in to.
     * @param $new_key
     *   The key to insert.
     * @param $new_value
     *   An value to insert.
     *
     * @return
     *   The new array if the key exists, FALSE otherwise.
     *
     * @author: Brad Erickson http://eosrei.net/comment/287
     */
    public function array_insert_after($key, array &$array, $new_key, $new_value) {
        if (array_key_exists($key, $array)) {
            $new = array();
            foreach ($array as $k => $value) {
                $new[$k] = $value;
                if ($k === $key) {
                    $new[$new_key] = $new_value;
                }
            }
            return $new;
        }
        return FALSE;
    }

}




/**
 * Workflow issue
 */
class Issue {
    
    private $title;
    private $state;
    
    public function __construct($title, $state) {
        $this->title = $title;
        $this->state = $state;
    }
    
    public function getState() {
        return $this->state;
    }
    
    public function getTitle() {
        return $this->title;
    }
}

/**
 * Workflow transitions
 */
class Transition {
    
     private $fromState;
     private $toStates;
     
     public function __construct($fromState, $toStates) {
         $this->fromState = $fromState;
         $this->toStates = $toStates;
     }
     
     public function getFromState() {
         return $this->fromState;
     }
     
     public function getToStates() {
         return $this->toStates;
     }
}
?>