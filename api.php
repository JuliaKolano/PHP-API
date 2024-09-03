<?php
    class MessageAPI {
        // private variables
        private $dataBase = null;
        private $sql = null;
        private $status = 500;


        // construct function
        function __construct() {

            // connect to database
            $this -> dataBase = mysqli_connect('localhost', 'jk911_user', 'FoEx-[-JTk8U', 'jk911_assignment2');

            // check if database connects successfully
            if (mysqli_connect_errno()) {
                $this -> status = 500;
                http_response_code($this -> status);
                exit();
            }
        }


        // destruct function
        function __destruct() {

            // disconnect from the database
            $this -> dataBase -> close();
        }


        // public method
        public function handleRequest() {

            // check request method 'POST'
            if (!strcmp($_SERVER['REQUEST_METHOD'], 'POST')) {
                $this -> Post();

            // check request method 'GET'
            } elseif (!strcmp($_SERVER['REQUEST_METHOD'], 'GET')) {
                $this -> Get();

            // any other request method and non-existing methods
            } else {
                $this -> status = 405;
                http_response_code($this -> status);
            }
        }


        // the 'POST' method
        private function Post() {

            //check if the right parameters are being passed
            if (isset($_POST['source']) && isset($_POST['target']) && isset($_POST['message'])) {
                
                $source = $_POST['source'];
                $target = $_POST['target'];
                $message = $_POST['message'];
    
                // do data validation
                if ((preg_match("/^[a-zA-Z0-9]{4,32}+$/", $source)) && (preg_match("/^[a-zA-Z0-9]{4,32}+$/", $target))) {
    
                    // do security checks
                    $source = $this -> dataBase -> real_escape_string($source);
                    $target = $this -> dataBase -> real_escape_string($target);
                    $message = $this -> dataBase -> real_escape_string($message);
    
                    // build and run the query
                    $this -> sql = "INSERT INTO users (source, target, message) VALUES ('$source', '$target', '$message')";
                    $result = $this -> dataBase -> query($this -> sql);
    
                    // if sucessfull add to database
                    if ($result !== false) {
                        $this -> status = 201;
                        $id = $this -> dataBase -> insert_id;

                        // generate response code
                        http_response_code($this -> status);
                        $postArray = array('id' => $id);
                        $jsonPostArray = json_encode($postArray, JSON_PRETTY_PRINT);
                        echo $jsonPostArray;

                    // if data was not added to database
                    } else {
                        $this -> status = 500;
                    }
                // if data validation fails
                } else {
                    $this -> status = 400;
                }
            // if wrong parameters passed
            } else {
                $this -> status = 400;
            }

            // generate response code
            http_response_code($this -> status);
        }


        // the 'GET' method
        private function Get() {

            // check if the right parameters are being passed
            if (isset($_GET['source']) && isset($_GET['target'])) {
                $source = $_GET['source'];
                $target = $_GET['target'];

                // do data validation
                if ((preg_match("/^[a-zA-Z0-9]{4,32}+$/", $source)) && (preg_match("/^[a-zA-Z0-9]{4,32}+$/", $target))) {

                    // do security checks
                    $source = $this -> dataBase -> real_escape_string($source);
                    $target = $this -> dataBase -> real_escape_string($target);

                    // check if the source or target exist in the database
                    $sqlCheckSource = "SELECT * FROM users WHERE source = '$source'";
                    $resultCheckSource = $this -> dataBase -> query($sqlCheckSource);
                    $sqlCheckTarget = "SELECT * FROM users WHERE target = '$target'";
                    $resultCheckTarget = $this -> dataBase -> query($sqlCheckTarget);

                    if ($resultCheckSource -> num_rows == 0 || $resultCheckTarget -> num_rows == 0) {
                        $this -> status = 404;

                    // if both of the users exist
                    } else {
                        // build and run the query
                        $this -> sql = "SELECT * FROM users WHERE source = '$source' AND target = '$target'";
                        $this -> generateResponse();
                    }
                // if data validation fails
                } else {
                    $this -> status = 400;
                }

            // check if the right parameters are being passed
            } elseif (isset($_GET['source'])) {
                $source = $_GET['source'];

                // do data validation
                if (preg_match("/^[a-zA-Z0-9]{4,32}+$/", $source)) {

                    // do security checks
                    $source = $this -> dataBase -> real_escape_string($source);
                    
                    // check if the source exists in the database
                    $sqlCheckSource = "SELECT * FROM users WHERE source = '$source'";
                    $resultCheckSource = $this -> dataBase -> query($sqlCheckSource);

                    if ($resultCheckSource -> num_rows == 0) {
                        $this -> status = 404;

                    // if the source exists
                    } else {
                        // build and run the query
                        $this -> sql = "SELECT * FROM users WHERE source = '$source'";
                        $this -> generateResponse();
                    }

                // if data validation fails
                } else {
                    $this -> status = 400;
                }

            // check if the right parameters are being passed
            } else if (isset($_GET['target'])) {
                $target = $_GET['target'];

                // do data validation
                if (preg_match("/^[a-zA-Z0-9]{4,32}+$/", $target)) {

                    // do security checks
                    $target = $this -> dataBase -> real_escape_string($target);

                    // check if the source exists in the database
                    $sqlCheckTarget = "SELECT * FROM users WHERE target = '$target'";
                    $resultCheckTarget = $this -> dataBase -> query($sqlCheckTarget);

                    if ($resultCheckTarget -> num_rows == 0) {
                        $this -> status = 404;

                    // if the target exists
                    } else {
                        // build and run the query
                        $this -> sql = "SELECT * FROM users WHERE target = '$target'";
                        $this -> generateResponse();
                    }
                
                // if data validation fails
                } else {
                    $this -> status = 400;
                }
            // if wrong parameters passed
            } else {
                $this -> status = 400; 
            }

            // generate response code
            http_response_code($this -> status);
        }


        // generate response json function
        private function generateResponse() {
            // run the query
            $result = $this -> dataBase -> query($this -> sql);

            // populate the arrays with all entries
            $messagesArray = array();
            while ($row = $result -> fetch_array(MYSQLI_ASSOC)) {
                $messageArray = array('id' => $row['id'], 'sent' => $row['sent'], 
                'source' => $row['source'], 'target' => $row['target'], 'message' => $row['message']);
                array_push($messagesArray, $messageArray);
            }
            // check if the array was populated
            if (!empty($messagesArray)) {

                // create json response
                $getArray = array('messages' => $messagesArray);
                $jsonGetArray = json_encode($getArray, JSON_PRETTY_PRINT);
                echo $jsonGetArray;
                $this -> status = 200;

            // if there are no messages between the users
            } else {
                $this -> status = 204;
            }
        }
    }


    // create instance of the API class and call the request method
    $messageAPI = new MessageAPI();
    $messageAPI -> handleRequest();
?>