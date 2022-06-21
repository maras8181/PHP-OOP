<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="">
</head>

<body>

     <?php
    
     class Database {
        private $localhost = "";
        private $username = "";
        private $password = "";
        private $db_name = "";
        public $conn = null;
        public function setConn(string $localhost, string $username, string $password, string $db_name)
        {
            $this->localhost = $localhost;
            $this->username = $username;
            $this->password = $password;
            $this->db_name = $db_name;
        }
        public function getConn(): object
        {
            $this->conn = new mysqli($this->localhost, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error){
                die("Connection failed: " . $this->conn->connect_error);
            } else {
                return $this->conn;
            }
            
        }    
    }
    
    class Date {
        private $sql = "";
        private $row = null;
        private $result = null;
        public function setDate(object $connection)
        {
            $this->sql = "SELECT created_at from transactions ORDER BY created_at DESC LIMIT 1";
            $this->result = $connection->query($this->sql);
            $this->row = $this->result->fetch_assoc();
        }
        public function getDate(): string
        {
            return date("Y-m-d", strtotime('-1 month', strtotime($this->row["created_at"])));
        }
    }
    
    class Users {
        private $sql = "";
        private $result = null;
        private $row = null;
        private $key = "";
        public $users = array();
        public function setFetchedData(string $date, object $connection)
        {
            $this->sql = "SELECT user_id, name, address, phone, email, password, SUM(price), currency FROM users JOIN transactions ON users.id=transactions.user_id WHERE transactions.created_at >= '$date' GROUP BY user_id, currency";
            $this->result = $connection->query($this->sql);
        }
        public function getFetchedData(): array
        {
            if ($this->result->num_rows > 0){
                while ($this->row = $this->result->fetch_assoc()){
                    $key = $this->row["user_id"];
                    $user_data = ["name"=>$this->row["name"], "address"=>$this->row["address"], "phone"=>$this->row["phone"], "email"=>$this->row["email"], "password"=>$this->row["password"], "price_czk"=>"0,00", "price_eur"=>"0,00"];
                    if (!array_key_exists($key, $this->users)){
                        $this->users[$key] = $user_data;
                    }
                    $this->users[$key]["price_".$this->row["currency"]] = $this->row["SUM(price)"];
                }
            }
            return $this->users;
        }
    }
    
    class Csv {
        private $delimiter = "";
        private $filename = "";
        private $f = null;
        private $fields = [];
        private $csv_fields = [];
        private $id = 1;   
        public function save_to_csv(array $users)
        {
            $this->delimiter = ";";
            $this->filename = "users.csv";
            $this->f = fopen($this->filename, "w");
            array_push($this->fields, "ID", "NAME", "ADDRESS", "PHONE", "EMAIL", "PASSWORD", "PRICE OF ALL TRANSACTIONS (CZK)", "PRICE OF ALL TRANSACTIONS (EUR)");
            fputcsv($this->f, $this->fields, $this->delimiter);
            foreach($users as $user){
                array_unshift($user, $this->id++);
                fputcsv($this->f, $user, $this->delimiter);
             }
        }
    }
    
    $database = new Database();
    $database->setConn("localhost", "root", "", "feo_database");
    $connection = $database->getConn();

    $datum = new Date();
    $datum->setDate($connection);
    $datum = $datum->getDate();
    
    $usersinfo = new Users();
    $usersinfo->setFetchedData($datum, $connection);
    $usersinfo = $usersinfo->getFetchedData();
    
    $csv_file = new Csv();
    $csv_file->save_to_csv($usersinfo);
    
?>

</body>
</html>
