<?php

include_once "html.php";
include_once "config.php";

function getConn($dbName = DB_NAME)
{
    try {
        // Kapcsolódás az adatbázishoz
        $mysqli = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, $dbName);

        // Ellenőrizzük a csatlakozás sikerességét
        if (!$mysqli) {
            throw new Exception("Kapcsolódási hiba az adatbázishoz: " . mysqli_connect_error());
        }

        return $mysqli;
    } catch (Exception $e) {
        // Hibaüzenet megjelenítése a felhasználónak
        echo $e->getMessage();

        // Hibanaplózás
        error_log($e->getMessage());

        // Hibás csatlakozás esetén `null`-t ad vissza
        return null;
    }
}

function databaseExists($dbName = DB_NAME)
{
    $database = getConn("information_schema");
    $result = $database->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$dbName';");
    $database->close();
    return $result->fetch_assoc() != null;
}

function dropDatabase($dbName = DB_NAME)
{
    $database = getConn($dbName);
    $database->query("DROP DATABASE $dbName;");
    $database->close();
}

function createDatabase($dbName = DB_NAME)
{
    $database = getConn("mysql");
    $database->query("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8 COLLATE utf8_hungarian_ci;");
    $database->close();
}

function createTable($tableBody, $tableName, $dbName = DB_NAME)
{
    $sql = "CREATE TABLE IF NOT EXISTS $dbName.$tableName
    ($tableBody)
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8
    COLLATE = utf8_hungarian_ci;";

    $database = getConn("mysql");
    $database->query($sql);
    $database->close();
}

function createTableSubjects($dbName = DB_NAME)
{
    $tableBody = "
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    ";
    createTable($tableBody, 'subjects', $dbName);
}

function createTableClasses($dbName = DB_NAME)
{
    $tableBody = "
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        year VARCHAR(100) NOT NULL
    ";
    createTable($tableBody, "classes", $dbName);
}

function createTableMarks($dbName = DB_NAME)
{
    $tableBody = "
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        mark INT NOT NULL,
        date VARCHAR(100) NOT NULL
    ";
    createTable($tableBody, "marks", $dbName);
}

function createTableStudents($dbName = DB_NAME)
{
    $tableBody = "
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        class_id INT NOT NULL
    ";
    createTable($tableBody, "students", $dbName);
}

function insertIntoTable($tableName, $header, $values, $dbName = DB_NAME)
{
    $database = getConn($dbName);
    $database->query("INSERT INTO $tableName($header) VALUES($values)");
    $database->close();
}

function fillTables()
{
    // classes table (year change)
    foreach (CLASSES as $class) {
        $year = str_split($class, 2)[0] == 11 ? "2023/2024" : "2024/2025";
        insertIntoTable("classes", "name, year", "'$class', '$year'");
    }

    // subjects table
    foreach (SUBJECTS as $subject) {
        insertIntoTable("subjects", "name", "'$subject'");
    }

    $class_id = 0;
    $student_id = 0;
    foreach (CLASSES as $class) {
        $class_id++;
        $classCount = rand(MIN_CLASS_COUNT, MAX_CLASS_COUNT);
        for ($i = 0; $i < $classCount; $i++) {
            $student_id++;
            $lastName = NAMES["lastnames"][rand(0, count(NAMES["lastnames"]) - 1)];
            $gender = rand(1, 2) == 1 ? "men" : "women";
            $firstName = NAMES["firstnames"][$gender][rand(0, count(NAMES["firstnames"][$gender]) - 1)];
            $year = str_split($class, 2)[0] == 11 ? "2023/2024" : "2024/2025";
            // students table
            insertIntoTable("students", "name, class_id", "'$lastName $firstName', $class_id");
            for ($k = 0; $k < count(SUBJECTS); $k++){
                $gradeCount = rand(MIN_MARKS_COUNT, MAX_MARKS_COUNT);
                for ($j = 0; $j < $gradeCount; $j++) {
                    $mark = rand(1, 5);
                    $date = date("Y-m-d", rand(strtotime("1 September " . explode("/", $year)[0]), strtotime("15 June " . explode("/", $year)[1]))); // (date change)
                    // marks table
                    insertIntoTable("marks", "student_id, subject_id, mark, date", "$student_id," .  $k+1 . ", $mark, '$date'");
                }
            }
        }
    }
}

function Joins(){
    return 'JOIN students st ON st.id = m.student_id
        JOIN subjects sub ON sub.id = m.subject_id
        JOIN classes c ON c.id = st.class_id';
}

function getGrades($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT DISTINCT year FROM $dbName.classes ORDER BY year;")->fetch_all();
    $database->close();
    return $result;
}

function getClasses($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT DISTINCT name FROM $dbName.classes WHERE year = '" . $_SESSION["grade"] . "' ORDER BY name;")->fetch_all();
    $database->close();
    return $result;
}

function getSubjectNames($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT name FROM subjects ORDER BY name;")->fetch_all();
    $database->close();
    return $result;
}

function getStudents($dbName = DB_NAME)
{
    $database = getConn($dbName);
    $result = $database->query("SELECT s.name FROM students s JOIN classes c ON c.id = s.class_id WHERE c.name = '" . $_SESSION["class"] . "' AND c.year = '" . $_SESSION["grade"] . "' ORDER BY s.name;")->fetch_all();
    $database->close();
    return $result;
}

function getStudentsSubjectAverage($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT st.name AS student_name, ROUND(AVG(m.mark),2) AS average_mark
        FROM marks m " . Joins() . "
        WHERE c.year = '" . $_SESSION["grade"] . "' AND c.name = '" . $_SESSION["class"] . "'
        GROUP BY st.id, sub.id
        ORDER BY st.name, sub.name;")->fetch_all();
    $database->close();
    return $result;
}

function getStudentsAverage($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT ROUND(AVG(m.mark),2)
        FROM marks m " . Joins() . "
        WHERE c.year = '" . $_SESSION["grade"] . "' AND c.name = '" . $_SESSION["class"] . "'
        GROUP BY st.id
        ORDER BY st.name;")->fetch_all();
    $database->close();
    return $result;
}

function getClassSubjectAverages($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT ROUND(AVG(m.mark),2)
        FROM marks m " . Joins() . "
        WHERE c.year = '" . $_SESSION["grade"] . "' AND c.name = '" . $_SESSION["class"] . "'
        GROUP BY sub.name
        ORDER BY sub.name;")->fetch_all();
    $database->close();
    return $result;
}

function getClassAverage($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT ROUND(AVG(m.mark),2)
        FROM marks m " . Joins() . "
        WHERE c.year = '" . $_SESSION["grade"] . "' AND c.name = '" . $_SESSION["class"] . "'
        GROUP BY c.id;")->fetch_all();
    $database->close();
    return $result;
}

function get10BestStudentsByGrade($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT c.name, st.name, ROUND(AVG(m.mark),2) AS average
        FROM marks m " . Joins() . "
        WHERE c.year = '" . $_SESSION["grade"] . "'
        GROUP BY st.id
        ORDER BY average DESC
        LIMIT 10;")->fetch_all();
    $database->close();
    return $result;
}

function getBestClass($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT c.year, c.name, ROUND(AVG(m.mark),2) AS average
        FROM marks m " . Joins() . "
        GROUP BY c.id
        ORDER BY average DESC
        LIMIT 1;")->fetch_all();
    $database->close();
    return $result;
}

function get10BestStudents($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT c.year, c.name, st.name, ROUND(AVG(m.mark),2) AS average
        FROM marks m " . Joins() . "
        GROUP BY st.id
        ORDER BY average DESC
        LIMIT 10;")->fetch_all();
    $database->close();
    return $result;
}

function getSubjectsTable($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT * FROM subjects ORDER BY id;")->fetch_all(MYSQLI_ASSOC);
    $database->close();
    return $result;
}

function getMarksTable($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT * FROM marks ORDER BY id;")->fetch_all(MYSQLI_ASSOC);
    $database->close();
    return $result;
}

function getStudentsTable($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT * FROM students ORDER BY id;")->fetch_all(MYSQLI_ASSOC);
    $database->close();
    return $result;
}

function getClassesTable($dbName = DB_NAME){
    $database = getConn($dbName);
    $result = $database->query("SELECT * FROM classes ORDER BY id;")->fetch_all(MYSQLI_ASSOC);
    $database->close();
    return $result;
}

function adminDelete($id, $dbName = DB_NAME){
    $database = getConn($dbName);
    $database->query("DELETE FROM " . $_SESSION["selectedTable"] . " WHERE id=" . $id . ";");
    $database->close();
}

function adminInsert($fieldNames, $values, $dbName = DB_NAME){
    $database = getConn($dbName);
    $database->query("INSERT INTO " . $_SESSION["selectedTable"] . "(" . $fieldNames . ") VALUES(" . $values . ")");
    $database->close();
}

function adminUpdate($id, $fieldValuePairs, $dbName = DB_NAME){
    $database = getConn($dbName);
    $database->query("UPDATE ". $_SESSION["selectedTable"] . " SET " . $fieldValuePairs . " WHERE id=" . $id . "");
    $database->close();
}