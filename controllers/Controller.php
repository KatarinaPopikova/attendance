<?php
require_once "helpers/Database.php";
require_once "models/Lecture.php";

class Controller
{
    private PDO $conn;
    private array $response;
    /**
     * Controller constructor.
     * @param $conn
     */
    public function __construct(){
        $database = new Database();
        $this->conn = $database->getConn();
    }

    public function getAttendanceContent(): array{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://api.github.com/repos/apps4webte/curldata2021/contents");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
        $str = curl_exec($curl);
        curl_close($curl);
        $this->addClass(json_decode($str));
        $this->setResponse(array(
            "status" => "success",
            "error" => false,
        ));
        return $this->getResponse();
    }

    private function addClass($classes){
        $classesNo = array();
        foreach ($classes as $class){
            $classNo = $class->name;
            $date =  substr($classNo,0,4)."-".substr($classNo,4,2)."-".substr($classNo,6,2);
            array_push($classesNo,$date);
            if($this->getClass($date) == false){
                $classId = $this->addClassDb($date);
                $this->addAttendance($classId,$class->download_url);
            }
        }
        foreach (array_diff($this->getAllClasses(),$classesNo) as $index => $deleteClass)
            $this->deleteClass($deleteClass);

    }
    private function addClassDb($date){
        $stmt = $this->conn->prepare("INSERT INTO PREDNASKA (datum)
                                                                VALUES (:datum)");
        $stmt->bindValue(':datum', $date, PDO::PARAM_STR);
        try {
            $stmt->execute();
            return $this->conn->lastInsertId();
        }
        catch (PDOException $e){
            throw $e;
        }
    }

    public function getClass($date){
        $stmt = $this->conn->prepare("SELECT id FROM PREDNASKA WHERE datum = :datum");
        $stmt->bindValue(":datum", $date, PDO::PARAM_STR);

        $stmt->execute();
        return  $stmt->fetchColumn();
    }

    private function getAllClasses(){
        $stmt = $this->conn->prepare("SELECT datum FROM PREDNASKA");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        return $stmt->fetchAll(PDO::FETCH_COLUMN,0);
    }
    private function deleteClass($date){
        $stmt = $this->conn->prepare("DELETE FROM PREDNASKA WHERE datum = :date");
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function addAttendance($classId, $downloadLink){
        $lines = explode(PHP_EOL,$this->getAttendanceCSV($downloadLink));
        foreach ($lines as $index => $line){
            $lineArray = str_getcsv($line, "\t");

            if ($index > 0 && ($lineArray[0])){
                $name = $lineArray[0];
                $user_id = $this->getStudentId($name);
                if ($user_id == false)
                    $user_id = $this->addStudentDb($name);
                $action = $lineArray[1];
                if (str_contains($lineArray[2],"AM"))
                    $timestamp = date('Y-m-d H:i:s',date_create_from_format('m/d/Y, H:i:s A',$lineArray[2])->getTimestamp());
                else
                    $timestamp = date('Y-m-d H:i:s',date_create_from_format('d/m/Y, H:i:s',$lineArray[2])->getTimestamp());
                $this->addAttendanceDb($classId,$user_id,$action,$timestamp);
            }
        }
    }

    private function addAttendanceDb($classId, $userId, $action, $timestamp){
        $stmt = $this->conn->prepare("INSERT INTO DOCHADZKA (prednaska_id, student_id,akcia, cas)
                                                                VALUES (:prednaska_id, :student_id, :akcia, :timestamp)");
        $stmt->bindValue(':prednaska_id', $classId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':akcia', $action, PDO::PARAM_STR);
        $stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
        try {
            $stmt->execute();
            return $this->conn->lastInsertId();
        }
        catch (PDOException $e){
            throw $e;
        }
    }
    private function addStudentDb($name){
        $stmt = $this->conn->prepare("INSERT INTO STUDENT (meno)
                                                                VALUES (:name)");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        try {
            $stmt->execute();
            return $this->conn->lastInsertId();
        }
        catch (PDOException $PDOException){
            throw $PDOException;
        }
    }
    private function getStudentId($name){
        $stmt = $this->conn->prepare("SELECT id FROM STUDENT WHERE meno = :meno");
        $stmt->bindValue(':meno', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function getStudentName($id){
        $stmt = $this->conn->prepare("SELECT meno FROM STUDENT WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function getAttendanceCSV($downloadLink){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$downloadLink);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        $csvAttendance = curl_exec($curl);
        $csvAttendance = mb_convert_encoding($csvAttendance,'UTF-8','UTF-16LE');
        curl_close($curl);
        return $csvAttendance;
    }

    private function getAllNames(){
        $stmt = $this->conn->prepare("SELECT id, meno FROM STUDENT ORDER BY id");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }
    public function getDataOfStudentOnXClass($student_id, $class_id ){
        $stmt = $this->conn->prepare("SELECT akcia, cas FROM `DOCHADZKA` WHERE student_id = :student_id AND prednaska_id = :prednaska_id ORDER BY cas ");
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':prednaska_id', $class_id, PDO::PARAM_INT);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();

    }

    private function lastTimeOfClass($id_class){
        $stmt = $this->conn->prepare("SELECT MAX(cas) FROM `DOCHADZKA` WHERE prednaska_id = :prednaska_id AND akcia = 'left'");
        $stmt->bindValue(':prednaska_id', $id_class, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();

    }

    private function getCountStudents(){
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT student_id) FROM `DOCHADZKA` GROUP BY prednaska_id ORDER BY prednaska_id");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        return $stmt->fetchAll(PDO::FETCH_COLUMN,0);
    }

    public function getAllClassesNames(){
        $this->setResponse(array(
            "status" => "success",
            "error" => false,
            "classesNames"=> $this->getAllClasses(),
            "countStudents" => $this->getCountStudents()
        ));
        return $this->getResponse();
    }

    private function getTimeOnClass($names){
        $userTimeOnClasses = array();
        foreach ($names as $name){
            $timeOnClass = array();
            array_push($timeOnClass,$name["id"]);
            $allMinutes = 0;
            $attendanceCount = 0;
            foreach ($this->getAllClasses() as $classDate){
                $classId = $this->getClass($classDate);
                $info = $this->getDataOfStudentOnXClass($name["id"],$classId);
                $lecture = $this->getTimeFromClass($info,$classId);
                if ($lecture->getMinutes()!=0)
                    $attendanceCount++;
                $allMinutes += $lecture->getMinutes();
                array_push($timeOnClass,$lecture);
            }

            array_push($timeOnClass,$attendanceCount);
            array_push($timeOnClass,round($allMinutes,2));
            array_push($userTimeOnClasses,$timeOnClass);
        }
        return $userTimeOnClasses;
    }

    private function getTimeFromClass($info, $classId): Lecture{
        $lecture = new Lecture($classId);
        $timeOnClasses = 0;
        if(!empty($info)){
            for ($i = 0; $i < count($info)-1; $i+=2) {
                $timeOnClasses+= strtotime($info[$i+1]["cas"]) - strtotime($info[$i]["cas"]);
            }
            if(count($info)%2 != 0){
                $lecture->setDontLeft(true);
                $lastLeft = strtotime($this->lastTimeOfClass($classId));
                $lastJoin = strtotime($info[count($info)-1]["cas"]);
                if( $lastLeft >  $lastJoin)
                    $timeOnClasses += ($lastLeft-$lastJoin);
            }
            $lecture->setMinutes( round($timeOnClasses/60,2));
        }
        else
            $lecture->setMinutes( 0);

        return $lecture;

    }



    public function getAllStudentsNames(){

        $names = $this->getAllNames();
        $timeOnClass = $this->getTimeOnClass($names);
        $this->setResponse(array(
            "status" => "success",
            "error" => false,
            "names"=> $names,
            "lectureTime"=>$timeOnClass,

        ));
        return $this->getResponse();
    }

    public function detailOfStudentOnClass($userId, $classId){
        $this->setResponse(array(
            "status" => "success",
            "error" => false,
            "dataOnClass"=> $this->getDataOfStudentOnXClass($userId, $classId ),
            "student"=>$this->getStudentName($userId)
        ));
        return $this->getResponse();
    }


    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }


    /**
     * @return mixed|PDO
     */
    public function getConn(): mixed
    {
        return $this->conn;
    }

    /**
     * @param mixed|PDO $conn
     */
    public function setConn(mixed $conn): void
    {
        $this->conn = $conn;
    }

}


