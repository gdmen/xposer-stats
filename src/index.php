<?php
date_default_timezone_set("UTC");

$PingInterval=5;
$Tolerance=21;

include('config.php');

$mysqli = new mysqli($MySQLhost, $MySQLuser, $MySQLpass, $MySQLdatabase, $MySQLport);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$res = $mysqli->query("SELECT received_at FROM pings ORDER BY received_at DESC");

class Event {
  public $type, $start, $end;
  public function __construct($end=null) {
    $this->setEnd(is_null($end) ? time() : $end);
    $this->type = "up";
  }
  public function down() {
    $this->type = "down";
  }
  public function setStart($t) {
    $this->start = $t;
  }
  public function setEnd($t) {
    $this->end = $t;
  }
  public function getDur() {
    return $this->end - $this->start;
  }
}

$misses = 0;
$e = new Event();
$events = [$e];
$t = $e->end;
while ($row = $res->fetch_assoc()) {
    $rt = strtotime($row['received_at']);
    $e->setStart($rt);
    $diff = $t - $rt;
    if ($diff > $PingInterval * $Tolerance) {
      $e->down();
      $e = new Event($rt);
      $events[] = $e;
    }
    $t = $rt;
}

date_default_timezone_set("America/Los_Angeles");
?>

<html>
  <head>
    <title>PHP Test</title>
  </head>
  <body>
    <table>
      <tr><th>Event</th><th>Date-Time</th><th>Duration</th></tr>
      <?php foreach ($events as $e) {
      $dur = $e->getDur();
      ?>
      <tr>
      <td><?php echo $e->type; ?></td>
      <td><?php echo date("F j, Y, g:i a", $e->start); ?></td>
      <td><?php echo sprintf("%d hrs, %d mins", intdiv($dur, 3600), intdiv($dur % 3600, 60)); ?></td>
      </tr>
      <?php } ?>
    </table>
  </body>
</html>
