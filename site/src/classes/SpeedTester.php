<?php


namespace src\classes;


class SpeedTester {
    private $speedValues = array();

    public function startSpeedTest($nameOfSubject) {
        if(array_key_exists($nameOfSubject, $this->speedValues)) {
            $this->speedValues[$nameOfSubject]["startTime"] = microtime(true) * 1000;
        } else {
            $this->speedValues[$nameOfSubject] = array(
                "startTime" => microtime(true) * 1000
            );
        }
    }

    public function endSpeedTest($nameOfSubject) {
        if(array_key_exists($nameOfSubject, $this->speedValues)) {
            $this->speedValues[$nameOfSubject]["endTime"] = microtime(true) * 1000;
        } else {
            $this->speedValues[$nameOfSubject] = array(
                "endTime" => microtime(true) * 1000
            );
        }
    }

    public function printResults() {
        foreach ($this->speedValues as $subject => $result) {
            echo "$subject: ". ($result["endTime"] - $result["startTime"])."<br/>";
        }
    }
}