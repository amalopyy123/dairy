<?php
date_default_timezone_set('PRC');


class DbOperation
{
    protected $db_user = 'root';
    protected $db_password = '';
    protected $db_host = '127.0.0.1';
    protected $db_port = 3306;
    protected $db_name = 'dairy';
    protected $db_table = 'note';

    public function selectByFields($fields, $value)
    {
        if (empty($fields)) {
            return false;
        }
        //connect
        $link = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($link->connect_errno) {
            throw new Exception("Connect failed: {$link->connect_error}");
        }
        $ret = array();
        $whereArray = [];
        foreach ($fields as $field) {
            if (in_array($field, ["nId", "day", "content", "memo"])) {
                $whereArray[] = "`" . $field . "` like '%" . $value . "%'";
            }
        }
        $whereClause = implode(' or ', $whereArray);
        $sql = <<<SQL
select * from `note` where {$whereClause}
SQL;
        $result = $link->query($sql);
        if ($result) {
            // Cycle through results
            while ($row = $result->fetch_object()) {
                array_push($ret, (array)$row);
            }
            // Free result set
            $result->close();
        }
        //close
        $link->close();
        return $ret;
    }

    public function searchByDay($fromDay, $toDay)
    {
        //connect
        $link = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($link->connect_errno) {
            throw new Exception("Connect failed: {$link->connect_error}");
        }
        $ret = array();
        $whereArray = [];
        if ($fromDay) {
            $whereArray[] = "day >=" . $fromDay;
        }
        if ($toDay) {
            $whereArray[] = "day <=" . $toDay;
        }
        $whereClause = implode(' and ', $whereArray);
        $sql = <<<SQL
select * from `note` where {$whereClause}
SQL;
        $result = $link->query($sql);
        if ($result) {
            // Cycle through results
            while ($row = $result->fetch_object()) {
                array_push($ret, (array)$row);
            }
            // Free result set
            $result->close();
        }
        //close
        $link->close();
        return $ret;
    }

    public function updateFields($nId, $day, $content, $memo, $today)
    {
        //connect
        $link = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($link->connect_errno) {
            throw new Exception("Connect failed: {$link->connect_error}");
        }
        $ret = array();

        $sql = <<<SQL
update `note` set `day`={$day},`content`='{$content}',`memo`='{$memo}',`updated_at`='{$today}' where `nid`={$nId}
SQL;
        $result = $link->query($sql);

        //close
        $link->close();
        return $result;
    }

    public function newRecord($day, $content, $memo, $today)
    {
        //connect
        $link = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($link->connect_errno) {
            throw new Exception("Connect failed: {$link->connect_error}");
        }
        $ret = array();

        $sql = <<<SQL
insert into `note`(`day`,`content`,`memo`,`updated_at`) values ({$day},'{$content}','{$memo}','{$today}');
SQL;
        $result = $link->query($sql);
        if ($result) {
            //必需在close之前获取
            $linkId = $link->insert_id;
            //close
            $link->close();
            return $linkId;
        }
        //close
        $link->close();
        return $result;
    }

    public function getDays()
    {
        //connect
        $link = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($link->connect_errno) {
            throw new Exception("Connect failed: {$link->connect_error}");
        }
        $ret = array();
        $sql = <<<SQL
select day from `note` order by day desc
SQL;
        $result = $link->query($sql);
        if ($result) {
            // Cycle through results
            while ($row = $result->fetch_object()) {
                array_push($ret, (array)$row);
            }
            // Free result set
            $result->close();
        }
        //close
        $link->close();
        return $ret;
    }
}


function handleRequest()
{
    $postData = $_POST;
    $retJson = ['status' => false, "message" => 'aaa', 'data' => []];
    $dbOpration = new DbOperation();
    $today = date('Y-m-d');
    switch ($_POST['method']) {
        case 'search':
        {
            $queryData = $dbOpration->selectByFields(explode(',', $_POST['fields']), $_POST['value']);
            $retJson['status'] = true;
            $retJson['data'] = $queryData;
            $retJson['message'] = 'success';
            return json_encode($retJson);;
        }
        case 'searchByDay':
        {
            $queryData = $dbOpration->searchByDay($_POST['from_day'], $_POST['to_day']);
            $retJson['status'] = true;
            $retJson['data'] = $queryData;
            $retJson['message'] = 'success';
            return json_encode($retJson);;
        }
        case 'update':
        {
            $result = $dbOpration->updateFields($_POST['nId'], $_POST['day'], $_POST['content'], $_POST['memo'], $today);
            if ($result) {
                $retJson['status'] = true;
                $retJson['updated_at'] = $today;
                $retJson['message'] = 'Success to update';
                $retJson['days'] = $dbOpration->getDays();
            } else {
                $retJson['status'] = false;
                $retJson['message'] = 'Failed to update';
            }
            return json_encode($retJson);;
        }
        case 'insert':
        {
            $result = $dbOpration->newRecord($_POST['day'], $_POST['content'], $_POST['memo'], $today);
            if ($result) {
                $retJson['status'] = true;
                $retJson['updated_at'] = $today;
                $retJson['message'] = 'Success to insert';
                $retJson['nId'] = $result;
                $retJson['days'] = $dbOpration->getDays();
            } else {
                $retJson['status'] = false;
                $retJson['message'] = 'Failed to insert';
            }
            return json_encode($retJson);;
        }
        case 'getDays':
        {
            $queryData = $dbOpration->getDays();
            $retJson['status'] = true;
            $retJson['data'] = $queryData;
            $retJson['message'] = 'success';
            return json_encode($retJson);;
        }
        default:
        {
            return json_encode($retJson);;
        }
    }
}

$retJson = handleRequest();
echo $retJson;
