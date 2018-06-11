<!--William Cram
CSI 253 - Php
03/07/2018
Assignment 8-->

<?php
// Error Messages
$errorName = $errorPrice = $errorDesc = "";

// Custom ServerInfo class to hold server info, defined at the bottom
$server = new ServerInfo("localhost", "root", "", "bookstore");


if (isset($_POST['submit'])) {
    // Runs if all fields are properly filled
    if (validate($errorName, $errorDesc, $errorPrice)) {

        tableCreate($server);
        tableInsert($server);
    }
}


if (isset($_POST['report'])) {
    tableReport($server);
}

// Text field validation
function validate(&$errorName, &$errorDesc, &$errorPrice) {

    $successState = true;

    // Is Name field empty
    if (empty($_POST['bookTitle'])) {
        $errorName = "Enter a name.";
        $successState = false;
    }

    // Is Description Empty
    if (empty($_POST['description'])) {
        $errorDesc = "Enter a description.";
        $successState = false;
    }

    // Checks is value is a number and is positive
    if (!is_numeric($_POST['price']) || (floatval($_POST['price']) < 0)) {
        $errorPrice = "Enter a positive dollar amount.";
        $successState = false;
    }

    return $successState;
}

// Display report of all books in the table
function tableReport($server) {
    // Server fields
    $dns = $server->getDSN();
    $user = $server->get_userId();
    $pwd = $server->get_pwd();
    $opt = $server->get_opt();

    $stmt = null;
    $con = null;
    try {
        // Connects to the localhost mysql database called bookstore
        $pdo = new PDO($dns, $user, $pwd, $opt);

        //Query the database
        // Checks if table exists
        $tableExists = $pdo->query("SHOW TABLES LIKE 'books'")->rowCount() > 0;

        // Top of report table
        echo "<table>";
        echo "<tr>";
        echo "<th>Id</th>";
        echo "<th>Title</th>";
        echo "<th>Descripition</th>";
        echo "<th>Price</th>";
        echo "<th>Genre</th>";

        echo "</tr>";

        // If the table exists populate the html with data
        if ($tableExists) {
            $sql = "SELECT * FROM books";


            // PDO statement
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['description'] . "</td>";
                echo "<td>" . $row['price'] . "</td>";
                echo "<td>" . $row['category'] . "</td>";


                echo "</tr>";
            }
            // Else display no table
        } else {
            echo "<tr>";
            echo "<td col-span='3'>No Data</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } catch (Exception $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } finally {
        //Close the connection'
        $stmt = null;
        $con = null;
    }
}

// Inserts a book into the table
function tableInsert($server) {

    // Access to globals to clear input values after insert
    global $nameStick, $descStick, $priceStick;

    $dns = $server->getDSN();
    $user = $server->get_userId();
    $pwd = $server->get_pwd();
    $opt = $server->get_opt();

    $stmt = null;
    $con = null;

    try {
        $name = $_POST['bookTitle'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $cata = $_POST['category'];

        // Connects to the localhost mysql database called bookstore
        $pdo = new PDO($dns, $user, $pwd, $opt);

        // INSERT string
        $sql = "INSERT INTO books (id, title, description, price, category) VALUES (NULL, '$name', '$desc', $price, '$cata')";


        // PDO statement
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // Summary
        summary();

        $_POST['bookTitle'] = "";
        $_POST['description'] = "";
        $_POST['price'] = "";
        $_POST['category'] = "";

        // Used to clear out the fields after an insert
        $nameStick = $descStick = $priceStick = "";
    } catch (PDOException $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } catch (Exception $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } finally {
        //Close the connection'
        $stmt = null;
        $con = null;
    }
}

// Displays a summery of the information just placed into the table
function summary() {
    echo "<table>";
    echo "<tr colspan='2'>";
    echo "<th>Added to database</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Title: </td>";
    echo "<td>" . $_POST['bookTitle'] . "</td>";
    echo"</tr>";
    echo "<tr>";
    echo "<td>Description: </td>";
    echo "<td>" . $_POST['description'] . "</td>";
    echo"</tr>";
    echo "<tr>";
    echo "<td>Price: </td>";
    echo "<td>" . $_POST['price'] . "</td>";
    echo"</tr>";
    echo "<tr>";
    echo "<td>Category: </td>";
    echo "<td>" . $_POST['category'] . "</td>";
    echo"</tr>";
    echo "</table>";
    echo "<br /><br />";
}

// Creates a table named books if one doesn't exist
function tableCreate($server) {

    $dns = $server->getDSN();
    $user = $server->get_userId();
    $pwd = $server->get_pwd();
    $opt = $server->get_opt();

    try {
        // Connects to the localhost mysql database called bookstore
        $pdo = new PDO($dns, $user, $pwd, $opt);

        // Boolean that checks for the existance of a table called books
        $tableExists = $pdo->query("SHOW TABLES LIKE 'books'")->rowCount() > 0;

        // If the table doesn't exist it creates it
        if (!$tableExists) {
            $createTable = "CREATE TABLE books("
                    . "id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                    . "title VARCHAR(80) NOT NULL,"
                    . "description VARCHAR(240) NOT NULL,"
                    . "price DECIMAL(8,2) UNSIGNED NOT NULL,"
                    . "category varchar(20) NOT NULL"
                    . ")";


            $pdo->exec($createTable);
        }
    } catch (PDOException $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } catch (Exception $ex) {
        die("something went wrong here:" . $ex->getMessage());
    } finally {
        //Close the connection'
        $pdo = null;
    }
}

// Server class
class ServerInfo {

    private $_host;
    private $_userId;
    private $_pwd;
    private $_dbName;
    private $_opt = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    );

    function __construct($_host, $_userId, $_pwd, $_dbName) {
        $this->_host = $_host;
        $this->_userId = $_userId;
        $this->_pwd = $_pwd;
        $this->_dbName = $_dbName;
    }

    function get_host() {
        return $this->_host;
    }

    function get_userId() {
        return $this->_userId;
    }

    function get_pwd() {
        return $this->_pwd;
    }

    function get_dbName() {
        return $this->_dbName;
    }

    function set_host($_host) {
        $this->_host = $_host;
    }

    function set_userId($_userId) {
        $this->_userId = $_userId;
    }

    function set_pwd($_pwd) {
        $this->_pwd = $_pwd;
    }

    function set_dbName($_dbName) {
        $this->_dbName = $_dbName;
    }

    function get_opt() {
        return $this->_opt;
    }

    function set_opt($_opt) {
        $this->_opt = $_opt;
    }

    // Returns a dsn
    function getDSN() {
        return "mysql:host=$this->_host;dbname=$this->_dbName;charset:utf8mb4";
    }

}
?>

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>William Cram - Assignment 8</title>
        <style>

            * {
                font-family: sans-serif;
                padding: 0;
                margin: 0;
            }

            table {
                border: 1px solid black;
                margin: 0 auto;
            }

            td {
                padding: 5px;
            }

            .error {
                color: red;
            }

            form {
                margin-top: 10px;
            }


        </style>
    </head>
    <body>

        <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <table>
                <tr>
                    <td>
                        <button name="report">Report</button>
                    </td>
                </tr>
                <tr>
                    <td>Book Name: </td>
                    <td><input type='text' name='bookTitle' value="<?php if (isset($_POST['bookTitle'])) echo $_POST['bookTitle'] ?>"></td>
                    <td><span class="error"><?php echo $errorName ?></span></td>
                </tr>
                <tr>
                    <td>Description: </td>
                    <td><input type='text' name='description'  value="<?php if (isset($_POST['description'])) echo $_POST['description'] ?>"></td>
                    <td><span class="error"><?php echo $errorDesc ?></span></td>

                </tr>
                <tr>
                    <td>Price: </td>
                    <td><input type="text" name='price'  value="<?php if (isset($_POST['price'])) echo $_POST['price'] ?>" /></td>
                    <td><span class="error"><?php echo $errorPrice ?></span></td>

                </tr>
                <tr>
                    <td>Category: </td>
                    <td>
                        <select name="category">
                            <option value="Horror">
                                Horror
                            </option>
                            <option value="Comedy">
                                Comedy
                            </option>
                            <option value="Romance">
                                Romance
                            </option>
                            <option value="SciFi">
                                SciFi
                            </option>
                            <option value="Autobiography">
                                Autobiography
                            </option>
                            <option value="Mystery">
                                Mystery
                            </option>
                        </select>

                    </td>
                </tr>
                <tr>
                    <td colspan="3"><input type="submit" name="submit" value="Add To Database"/></td>
                </tr>
            </table>
        </form>
        <br /><br />

    </body>
</html>

<!--
1. Create a form that allows the user to enter a book title, description, and price, 
and select a category from a drop-down list. All fields are required. Price must be a 
positive number. The form should post to itself. When all data is valid, display a summary.

2. Your PHP script should check for a table, and if it doesn't exist, create it.

3. Create a report that displays all books.

4. At the top of the data entry form, add a link to display the report.

5. Name your database: bookstore, and your table should be called books

6. Turn in your PHP code to the submit box..-->